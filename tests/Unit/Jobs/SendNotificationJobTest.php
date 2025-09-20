<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendNotificationJob;
use App\Models\Program;
use App\Models\User;
use App\Notifications\EnrollmentNotification;
use App\Services\NotificationLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class SendNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_sends_notification_to_user_with_email_preference()
    {
        // Arrange
        Notification::fake();
        
        $user = User::factory()->create([
            'notify_email' => true,
            'notify_whatsapp' => false,
            'phone' => null
        ]);
        
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($user, $program);
        
        $job = new SendNotificationJob($user, $notification);

        // Act
        $job->handle();

        // Assert
        Notification::assertSentTo($user, EnrollmentNotification::class);
    }

    public function test_handle_sends_notification_to_user_with_whatsapp_preference()
    {
        // Arrange
        Notification::fake();
        
        $user = User::factory()->create([
            'notify_email' => false,
            'notify_whatsapp' => true,
            'phone' => '+1234567890'
        ]);
        
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($user, $program);
        
        $job = new SendNotificationJob($user, $notification);

        // Act
        $job->handle();

        // Assert
        Notification::assertSentTo($user, EnrollmentNotification::class);
    }

    public function test_handle_sends_notification_to_user_with_both_preferences()
    {
        // Arrange
        Notification::fake();
        
        $user = User::factory()->create([
            'notify_email' => true,
            'notify_whatsapp' => true,
            'phone' => '+1234567890'
        ]);
        
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($user, $program);
        
        $job = new SendNotificationJob($user, $notification);

        // Act
        $job->handle();

        // Assert
        Notification::assertSentTo($user, EnrollmentNotification::class);
    }

    public function test_handle_does_not_send_notification_when_no_preferences_enabled()
    {
        // Arrange
        Notification::fake();
        
        $user = User::factory()->create([
            'notify_email' => false,
            'notify_whatsapp' => false,
            'phone' => null
        ]);
        
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($user, $program);
        
        $job = new SendNotificationJob($user, $notification);

        // Act
        $job->handle();

        // Assert
        Notification::assertNotSentTo($user, EnrollmentNotification::class);
    }

    public function test_handle_does_not_send_whatsapp_when_phone_is_missing()
    {
        // Arrange
        Notification::fake();
        
        $user = User::factory()->create([
            'notify_email' => false,
            'notify_whatsapp' => true,
            'phone' => null
        ]);
        
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($user, $program);
        
        $job = new SendNotificationJob($user, $notification);

        // Act
        $job->handle();

        // Assert
        Notification::assertNotSentTo($user, EnrollmentNotification::class);
    }

    public function test_handle_logs_notification_attempt()
    {
        // Arrange
        $logger = Mockery::mock(NotificationLogger::class);
        $this->app->instance(NotificationLogger::class, $logger);
        
        $user = User::factory()->create([
            'notify_email' => true,
            'notify_whatsapp' => false
        ]);
        
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($user, $program);
        
        $logger->shouldReceive('logNotificationAttempt')
            ->once()
            ->with($user, $notification, 'sent', Mockery::any());
        
        $job = new SendNotificationJob($user, $notification);

        // Act
        $job->handle();

        // Assert - Mockery will verify the expectation
    }

    public function test_handle_logs_notification_failure_on_exception()
    {
        // Arrange
        $logger = Mockery::mock(NotificationLogger::class);
        $this->app->instance(NotificationLogger::class, $logger);
        
        // Create a user that will cause notification to fail
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('notify_email')->andReturn(true);
        $user->shouldReceive('getAttribute')->with('notify_whatsapp')->andReturn(false);
        $user->shouldReceive('getAttribute')->with('phone')->andReturn(null);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $user->shouldReceive('notify')->andThrow(new \Exception('Notification failed'));
        
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification(User::factory()->create(), $program);
        
        $logger->shouldReceive('logNotificationAttempt')
            ->once()
            ->with($user, $notification, 'failed', Mockery::any());
        
        $job = new SendNotificationJob($user, $notification);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Notification failed');
        
        $job->handle();

        // Mockery will verify the expectation
    }

    public function test_job_has_correct_properties()
    {
        // Arrange
        $user = User::factory()->create();
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($user, $program);
        
        // Act
        $job = new SendNotificationJob($user, $notification);

        // Assert
        $this->assertEquals($user, $job->user);
        $this->assertEquals($notification, $job->notification);
    }
}