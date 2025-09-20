<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use App\Models\Meeting;
use App\Models\Enrollment;
use App\Services\MeetingNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeetingNotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'student']);
    }

    public function test_meeting_creation_sends_notifications_to_enrolled_students()
    {
        // Mock the queue and notification systems
        Queue::fake();
        Notification::fake();

        // Create test data
        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');

        // Create students with different notification preferences
        $student1 = User::factory()->create([
            'role' => 'student',
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);
        $student1->assignRole('student');

        $student2 = User::factory()->create([
            'role' => 'student',
            'notify_email' => false,
            'notify_whatsapp' => true,
            'phone' => '+1234567890',
        ]);
        $student2->assignRole('student');

        $student3 = User::factory()->create([
            'role' => 'student',
            'notify_email' => true,
            'notify_whatsapp' => true,
            'phone' => '+0987654321',
        ]);
        $student3->assignRole('student');

        // Create enrollments with access granted
        foreach ([$student1, $student2, $student3] as $student) {
            Enrollment::create([
                'user_id' => $student->id,
                'program_id' => $program->id,
                'assigned_at' => now(),
                'access_granted_at' => now(),
                'approved_by' => 1,
            ]);
        }

        $meeting = Meeting::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);

        $notificationService = app(MeetingNotificationService::class);
        $notificationService->sendMeetingCreatedNotification($meeting);

        // Verify that bulk notification job was dispatched
        Queue::assertPushed(\App\Jobs\SendBulkNotificationJob::class, function ($job) use ($student1, $student2, $student3) {
            // Get the users from the job
            $users = $job->users ?? collect();
            return $users->contains($student1) && 
                   $users->contains($student2) && 
                   $users->contains($student3);
        });
    }

    public function test_meeting_notifications_only_sent_to_students_with_approved_access()
    {
        // Mock the queue and notification systems
        Queue::fake();
        Notification::fake();

        // Create test data
        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');

        // Create students - one with approved access, one without
        $approvedStudent = User::factory()->create([
            'role' => 'student',
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);
        $approvedStudent->assignRole('student');

        $pendingStudent = User::factory()->create([
            'role' => 'student',
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);
        $pendingStudent->assignRole('student');

        // Create enrollments - one approved, one pending
        Enrollment::create([
            'user_id' => $approvedStudent->id,
            'program_id' => $program->id,
            'assigned_at' => now(),
            'access_granted_at' => now(),
            'approved_by' => 1,
        ]);

        Enrollment::create([
            'user_id' => $pendingStudent->id,
            'program_id' => $program->id,
            'assigned_at' => now(),
            'access_granted_at' => null, // No access granted
            'approved_by' => null,
        ]);

        $meeting = Meeting::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);

        $notificationService = app(MeetingNotificationService::class);
        $notificationService->sendMeetingCreatedNotification($meeting);

        // Verify that bulk notification job was dispatched only for approved student
        Queue::assertPushed(\App\Jobs\SendBulkNotificationJob::class, function ($job) use ($approvedStudent, $pendingStudent) {
            $users = $job->users ?? collect();
            return $users->contains($approvedStudent) && !$users->contains($pendingStudent);
        });
    }

    public function test_meeting_update_notifications_are_sent()
    {
        // Mock the queue and notification systems
        Queue::fake();
        Notification::fake();

        // Create test data
        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');

        $student = User::factory()->create([
            'role' => 'student',
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);
        $student->assignRole('student');

        Enrollment::create([
            'user_id' => $student->id,
            'program_id' => $program->id,
            'assigned_at' => now(),
            'access_granted_at' => now(),
            'approved_by' => 1,
        ]);

        $meeting = Meeting::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);

        $notificationService = app(MeetingNotificationService::class);
        $notificationService->sendMeetingUpdatedNotification($meeting);

        // Verify that bulk notification job was dispatched
        Queue::assertPushed(\App\Jobs\SendBulkNotificationJob::class, function ($job) use ($student) {
            $users = $job->users ?? collect();
            $notification = $job->notification ?? null;
            return $users->contains($student) && 
                   $notification instanceof \App\Notifications\MeetingNotification &&
                   $notification->getNotificationType() === 'meeting_updated';
        });
    }

    public function test_meeting_cancellation_notifications_are_sent()
    {
        // Mock the queue and notification systems
        Queue::fake();
        Notification::fake();

        // Create test data
        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');

        $student = User::factory()->create([
            'role' => 'student',
            'notify_email' => false,
            'notify_whatsapp' => true,
            'phone' => '+1234567890',
        ]);
        $student->assignRole('student');

        Enrollment::create([
            'user_id' => $student->id,
            'program_id' => $program->id,
            'assigned_at' => now(),
            'access_granted_at' => now(),
            'approved_by' => 1,
        ]);

        $meeting = Meeting::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);

        $notificationService = app(MeetingNotificationService::class);
        $notificationService->sendMeetingCancelledNotification($meeting);

        // Verify that bulk notification job was dispatched
        Queue::assertPushed(\App\Jobs\SendBulkNotificationJob::class, function ($job) use ($student) {
            $users = $job->users ?? collect();
            $notification = $job->notification ?? null;
            return $users->contains($student) && 
                   $notification instanceof \App\Notifications\MeetingNotification &&
                   $notification->getNotificationType() === 'meeting_cancelled';
        });
    }

    public function test_no_notifications_sent_to_students_with_disabled_preferences()
    {
        // Mock the queue and notification systems
        Queue::fake();
        Notification::fake();

        // Create test data
        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');

        // Student with all notifications disabled
        $student = User::factory()->create([
            'role' => 'student',
            'notify_email' => false,
            'notify_whatsapp' => false,
        ]);
        $student->assignRole('student');

        Enrollment::create([
            'user_id' => $student->id,
            'program_id' => $program->id,
            'assigned_at' => now(),
            'access_granted_at' => now(),
            'approved_by' => 1,
        ]);

        $meeting = Meeting::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);

        $notificationService = app(MeetingNotificationService::class);
        $notificationService->sendMeetingCreatedNotification($meeting);

        // Should not dispatch any bulk notification jobs since no students have notifications enabled
        Queue::assertNotPushed(\App\Jobs\SendBulkNotificationJob::class);
    }
}