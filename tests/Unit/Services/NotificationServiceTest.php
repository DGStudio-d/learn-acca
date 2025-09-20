<?php

namespace Tests\Unit\Services;

use App\Jobs\SendBulkNotificationJob;
use App\Jobs\SendNotificationJob;
use App\Models\Meeting;
use App\Models\Program;
use App\Models\User;
use App\Notifications\AccessApprovalNotification;
use App\Notifications\EnrollmentNotification;
use App\Notifications\MeetingNotification;
use App\Services\NotificationLogger;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = Mockery::mock(NotificationLogger::class);
        $this->notificationService = new NotificationService($this->logger);
        
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_send_enrollment_notification_dispatches_to_admins()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $program = Program::factory()->create();
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);

        // Act
        $this->notificationService->sendEnrollmentNotification($student, $program);

        // Assert
        Queue::assertPushed(SendBulkNotificationJob::class, function ($job) use ($admin1, $admin2) {
            $users = $job->users;
            return $users->count() === 2 && 
                   $users->contains('id', $admin1->id) && 
                   $users->contains('id', $admin2->id) &&
                   $job->notification instanceof EnrollmentNotification;
        });
    }

    public function test_send_enrollment_notification_handles_no_admins()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $program = Program::factory()->create();
        // No admins created

        // Act
        $this->notificationService->sendEnrollmentNotification($student, $program);

        // Assert
        Queue::assertNotPushed(SendBulkNotificationJob::class);
    }

    public function test_send_access_approval_notification_dispatches_to_student()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $program = Program::factory()->create();

        // Act
        $this->notificationService->sendAccessApprovalNotification($student, $program);

        // Assert
        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($student) {
            return $job->user->id === $student->id &&
                   $job->notification instanceof AccessApprovalNotification;
        });
    }

    public function test_send_meeting_notification_dispatches_to_enrolled_students()
    {
        // Arrange
        $program = Program::factory()->create();
        $meeting = Meeting::factory()->create(['program_id' => $program->id]);
        
        $student1 = User::factory()->create([
            'role' => 'student',
            'notify_email' => true
        ]);
        $student2 = User::factory()->create([
            'role' => 'student',
            'notify_whatsapp' => true,
            'phone' => '+1234567890'
        ]);
        
        // Enroll students with approved access
        $student1->enrollments()->create([
            'program_id' => $program->id,
            'access_granted_at' => now()
        ]);
        $student2->enrollments()->create([
            'program_id' => $program->id,
            'access_granted_at' => now()
        ]);

        // Act
        $this->notificationService->sendMeetingNotification($meeting, 'created');

        // Assert
        Queue::assertPushed(SendBulkNotificationJob::class, function ($job) use ($student1, $student2) {
            $users = $job->users;
            return $users->count() === 2 && 
                   $users->contains('id', $student1->id) && 
                   $users->contains('id', $student2->id) &&
                   $job->notification instanceof MeetingNotification;
        });
    }

    public function test_send_meeting_notification_excludes_students_without_notification_preferences()
    {
        // Arrange
        $program = Program::factory()->create();
        $meeting = Meeting::factory()->create(['program_id' => $program->id]);
        
        $student = User::factory()->create([
            'role' => 'student',
            'notify_email' => false,
            'notify_whatsapp' => false
        ]);
        
        $student->enrollments()->create([
            'program_id' => $program->id,
            'access_granted_at' => now()
        ]);

        // Act
        $this->notificationService->sendMeetingNotification($meeting, 'created');

        // Assert
        Queue::assertNotPushed(SendBulkNotificationJob::class);
    }

    public function test_send_meeting_notification_excludes_students_without_access()
    {
        // Arrange
        $program = Program::factory()->create();
        $meeting = Meeting::factory()->create(['program_id' => $program->id]);
        
        $student = User::factory()->create([
            'role' => 'student',
            'notify_email' => true
        ]);
        
        // Enroll student without approved access
        $student->enrollments()->create([
            'program_id' => $program->id,
            'access_granted_at' => null
        ]);

        // Act
        $this->notificationService->sendMeetingNotification($meeting, 'created');

        // Assert
        Queue::assertNotPushed(SendBulkNotificationJob::class);
    }

    public function test_send_notification_to_user_dispatches_single_notification()
    {
        // Arrange
        $user = User::factory()->create();
        $notification = new AccessApprovalNotification($user, Program::factory()->create());

        // Act
        $this->notificationService->sendNotificationToUser($user, $notification);

        // Assert
        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($user, $notification) {
            return $job->user->id === $user->id &&
                   get_class($job->notification) === get_class($notification);
        });
    }

    public function test_send_notification_to_users_dispatches_bulk_notification()
    {
        // Arrange
        $users = User::factory()->count(3)->create();
        $notification = new EnrollmentNotification(
            User::factory()->create(),
            Program::factory()->create()
        );

        // Act
        $this->notificationService->sendNotificationToUsers($users, $notification);

        // Assert
        Queue::assertPushed(SendBulkNotificationJob::class, function ($job) use ($users, $notification) {
            return $job->users->count() === 3 &&
                   get_class($job->notification) === get_class($notification);
        });
    }

    public function test_send_notification_to_users_handles_empty_collection()
    {
        // Arrange
        $users = collect();
        $notification = new EnrollmentNotification(
            User::factory()->create(),
            Program::factory()->create()
        );

        // Act
        $this->notificationService->sendNotificationToUsers($users, $notification);

        // Assert
        Queue::assertNotPushed(SendBulkNotificationJob::class);
    }

    public function test_get_notification_stats_calls_logger()
    {
        // Arrange
        $expectedStats = ['total' => 100, 'sent' => 80, 'failed' => 20];
        $this->logger
            ->shouldReceive('getSystemNotificationStats')
            ->once()
            ->andReturn($expectedStats);

        // Act
        $result = $this->notificationService->getNotificationStats();

        // Assert
        $this->assertEquals($expectedStats, $result);
    }

    public function test_get_user_notification_history_calls_logger()
    {
        // Arrange
        $user = User::factory()->create();
        $expectedHistory = new \Illuminate\Pagination\LengthAwarePaginator(
            collect(['notification1', 'notification2']),
            2,
            50,
            1
        );
        
        $this->logger
            ->shouldReceive('getUserNotificationHistory')
            ->once()
            ->with($user, 50)
            ->andReturn($expectedHistory);

        // Act
        $result = $this->notificationService->getUserNotificationHistory($user);

        // Assert
        $this->assertEquals($expectedHistory, $result);
    }

    public function test_get_user_notification_stats_calls_logger()
    {
        // Arrange
        $user = User::factory()->create();
        $expectedStats = ['sent' => 10, 'failed' => 2];
        
        $this->logger
            ->shouldReceive('getUserNotificationStats')
            ->once()
            ->with($user)
            ->andReturn($expectedStats);

        // Act
        $result = $this->notificationService->getUserNotificationStats($user);

        // Assert
        $this->assertEquals($expectedStats, $result);
    }

    public function test_cleanup_old_notifications_calls_logger()
    {
        // Arrange
        $expectedCount = 50;
        $this->logger
            ->shouldReceive('cleanupOldLogs')
            ->once()
            ->with(90)
            ->andReturn($expectedCount);

        // Act
        $result = $this->notificationService->cleanupOldNotifications();

        // Assert
        $this->assertEquals($expectedCount, $result);
    }
}