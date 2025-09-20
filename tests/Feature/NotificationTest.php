<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_user_can_get_notification_history(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'notify_email' => true,
            'notify_whatsapp' => true,
        ]);

        // Create some notification logs
        NotificationLog::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'notifications' => [
                        '*' => [
                            'id',
                            'type',
                            'channel',
                            'status',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                        'has_more_pages',
                    ]
                ]
            ]);
    }

    public function test_user_can_get_notification_stats(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        // Create some notification logs with different statuses
        NotificationLog::factory()->create([
            'user_id' => $user->id,
            'status' => 'sent',
            'channel' => 'email',
        ]);
        
        NotificationLog::factory()->create([
            'user_id' => $user->id,
            'status' => 'failed',
            'channel' => 'whatsapp',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'sent',
                    'failed',
                    'pending',
                    'by_channel' => [
                        'email',
                        'whatsapp',
                    ],
                    'by_type',
                ]
            ]);
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/notifications/preferences', [
                'notify_email' => false,
                'notify_whatsapp' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'data' => [
                    'notify_email' => false,
                    'notify_whatsapp' => true,
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'notify_email' => false,
            'notify_whatsapp' => true,
        ]);
    }

    public function test_admin_can_get_system_notification_stats(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/notifications/system/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'sent',
                    'failed',
                    'pending',
                    'by_channel',
                    'by_type',
                    'recent_failures',
                ]
            ]);
    }

    public function test_admin_can_retry_failed_notifications(): void
    {
        Queue::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $admin->assignRole('admin');

        // Create some failed notifications
        NotificationLog::factory()->count(2)->create([
            'status' => 'failed',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/notifications/retry-failed', [
                'limit' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'retry_count',
                ]
            ]);
    }

    public function test_non_admin_cannot_access_admin_notification_endpoints(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/system/stats');

        $response->assertStatus(403);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/notifications/retry-failed');

        $response->assertStatus(403);
    }
}