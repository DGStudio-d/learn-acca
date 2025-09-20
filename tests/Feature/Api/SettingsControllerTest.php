<?php

namespace Tests\Feature\Api;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'student']);

        // Create users
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->studentUser = User::factory()->create();
        $this->studentUser->assignRole('student');
    }

    public function test_admin_can_get_guest_access_settings()
    {
        Sanctum::actingAs($this->adminUser);

        Settings::setValue('allow_guest_languages', true, 'boolean');
        Settings::setValue('allow_guest_teachers', false, 'boolean');

        $response = $this->getJson('/api/admin/settings/guest-access');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'settings' => [
                        'allow_guest_languages' => true,
                        'allow_guest_teachers' => false,
                        'allow_guest_quizzes' => false,
                    ],
                    'available_features' => [
                        'languages' => 'Allow guests to view available languages',
                        'teachers' => 'Allow guests to view teacher profiles',
                        'quizzes' => 'Allow guests to attempt quizzes anonymously',
                    ],
                ],
            ]);
    }

    public function test_admin_can_update_guest_access_settings()
    {
        Sanctum::actingAs($this->adminUser);

        $updateData = [
            'allow_guest_languages' => true,
            'allow_guest_teachers' => true,
            'allow_guest_quizzes' => false,
        ];

        $response = $this->putJson('/api/admin/settings/guest-access', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Guest access settings updated successfully',
                'data' => [
                    'settings' => $updateData,
                ],
            ]);

        // Verify settings were saved
        $this->assertTrue(Settings::getValue('allow_guest_languages'));
        $this->assertTrue(Settings::getValue('allow_guest_teachers'));
        $this->assertFalse(Settings::getValue('allow_guest_quizzes'));
    }

    public function test_admin_can_get_specific_setting()
    {
        Sanctum::actingAs($this->adminUser);

        Settings::setValue('test_setting', 'test_value', 'string');

        $response = $this->getJson('/api/admin/settings/test_setting');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'key' => 'test_setting',
                    'value' => 'test_value',
                ],
            ]);
    }

    public function test_admin_can_set_specific_setting()
    {
        Sanctum::actingAs($this->adminUser);

        $settingData = [
            'value' => 'new_value',
            'type' => 'string',
            'description' => 'Test setting description',
        ];

        $response = $this->putJson('/api/admin/settings/new_setting', $settingData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => [
                    'setting' => [
                        'key' => 'new_setting',
                        'value' => 'new_value',
                        'type' => 'string',
                        'description' => 'Test setting description',
                    ],
                ],
            ]);

        $this->assertEquals('new_value', Settings::getValue('new_setting'));
    }

    public function test_admin_can_initialize_default_settings()
    {
        Sanctum::actingAs($this->adminUser);

        // Clear existing settings
        Settings::query()->delete();

        $response = $this->postJson('/api/admin/settings/initialize-defaults');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Default settings initialized successfully',
            ]);

        // Verify default settings were created
        $this->assertFalse(Settings::getValue('allow_guest_languages'));
        $this->assertFalse(Settings::getValue('allow_guest_teachers'));
        $this->assertFalse(Settings::getValue('allow_guest_quizzes'));
    }

    public function test_non_admin_cannot_access_settings_endpoints()
    {
        Sanctum::actingAs($this->studentUser);

        $response = $this->getJson('/api/admin/settings/guest-access');
        $response->assertStatus(403);

        $response = $this->putJson('/api/admin/settings/guest-access', []);
        $response->assertStatus(403);

        $response = $this->getJson('/api/admin/settings/test_key');
        $response->assertStatus(403);

        $response = $this->putJson('/api/admin/settings/test_key', ['value' => 'test']);
        $response->assertStatus(403);

        $response = $this->postJson('/api/admin/settings/initialize-defaults');
        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_settings_endpoints()
    {
        $response = $this->getJson('/api/admin/settings/guest-access');
        $response->assertStatus(401);

        $response = $this->putJson('/api/admin/settings/guest-access', []);
        $response->assertStatus(401);
    }

    public function test_validation_error_when_updating_guest_settings_with_invalid_data()
    {
        Sanctum::actingAs($this->adminUser);

        $invalidData = [
            'allow_guest_languages' => 'invalid_boolean',
            'allow_guest_teachers' => 123,
        ];

        $response = $this->putJson('/api/admin/settings/guest-access', $invalidData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid settings data provided',
                ],
            ]);
    }

    public function test_validation_error_when_setting_invalid_setting_type()
    {
        Sanctum::actingAs($this->adminUser);

        $invalidData = [
            'value' => 'test_value',
            'type' => 'invalid_type',
        ];

        $response = $this->putJson('/api/admin/settings/test_key', $invalidData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid setting data provided',
                ],
            ]);
    }

    public function test_get_nonexistent_setting_returns_404()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/settings/nonexistent_key');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'SETTING_NOT_FOUND',
                    'message' => "Setting 'nonexistent_key' not found",
                ],
            ]);
    }
}