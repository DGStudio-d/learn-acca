<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendBulkNotificationJob;
use App\Jobs\SendNotificationJob;
use App\Models\Program;
use App\Models\User;
use App\Notifications\EnrollmentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendBulkNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_dispatches_individual_notification_jobs()
    {
        // Arrange
        Queue::fake();
        
        $users = User::factory()->count(3)->create([
            'notify_email' => true
        ]);
        
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($users->first(), $program);
        
        $job = new SendBulkNotificationJob($users, $notification);

        // Act
        $job->handle();

        // Assert
        Queue::assertPushed(SendNotificationJob::class, 3);
        
        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($users) {
            return $users->contains('id', $job->user->id);
        });
    }

    public function test_handle_works_with_collection_of_users()
    {
        // Arrange
        Queue::fake();
        
        $users = collect([
            User::factory()->create(['notify_email' => true]),
            User::factory()->create(['notify_email' => true]),
        ]);
        
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($users->first(), $program);
        
        $job = new SendBulkNotificationJob($users, $notification);

        // Act
        $job->handle();

        // Assert
        Queue::assertPushed(SendNotificationJob::class, 2);
    }

    public function test_handle_works_with_empty_collection()
    {
        // Arrange
        Queue::fake();
        
        $users = collect();
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification(User::factory()->create(), $program);
        
        $job = new SendBulkNotificationJob($users, $notification);

        // Act
        $job->handle();

        // Assert
        Queue::assertNotPushed(SendNotificationJob::class);
    }

    public function test_handle_dispatches_correct_notification_type()
    {
        // Arrange
        Queue::fake();
        
        $users = User::factory()->count(2)->create();
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($users->first(), $program);
        
        $job = new SendBulkNotificationJob($users, $notification);

        // Act
        $job->handle();

        // Assert
        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($notification) {
            return get_class($job->notification) === get_class($notification);
        });
    }

    public function test_job_has_correct_properties()
    {
        // Arrange
        $users = User::factory()->count(2)->create();
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($users->first(), $program);
        
        // Act
        $job = new SendBulkNotificationJob($users, $notification);

        // Assert
        $this->assertInstanceOf(Collection::class, $job->users);
        $this->assertEquals(2, $job->users->count());
        $this->assertEquals($notification, $job->notification);
    }

    public function test_job_converts_array_to_collection()
    {
        // Arrange
        $users = User::factory()->count(2)->create()->toArray();
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification(User::factory()->create(), $program);
        
        // Act
        $job = new SendBulkNotificationJob($users, $notification);

        // Assert
        $this->assertInstanceOf(Collection::class, $job->users);
    }

    public function test_handle_preserves_user_order()
    {
        // Arrange
        Queue::fake();
        
        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        $user3 = User::factory()->create(['name' => 'User 3']);
        
        $users = collect([$user1, $user2, $user3]);
        
        $program = Program::factory()->create();
        $notification = new EnrollmentNotification($user1, $program);
        
        $job = new SendBulkNotificationJob($users, $notification);

        // Act
        $job->handle();

        // Assert
        Queue::assertPushed(SendNotificationJob::class, 3);
        
        // Verify each user gets their own job
        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($user1) {
            return $job->user->id === $user1->id;
        });
        
        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($user2) {
            return $job->user->id === $user2->id;
        });
        
        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($user3) {
            return $job->user->id === $user3->id;
        });
    }
}