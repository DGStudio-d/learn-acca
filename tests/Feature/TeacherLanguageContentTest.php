<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherLanguageContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'teacher']);
        Role::firstOrCreate(['name' => 'student']);
    }

    public function test_teacher_can_access_assigned_language_content(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        
        $language = Language::factory()->create();

        // Assign teacher to language
        $this->actingAs($admin, 'sanctum')
             ->postJson('/api/admin/assign-teacher-language', [
                 'teacher_id' => $teacher->id,
                 'language_id' => $language->id,
             ]);

        // Teacher should be able to access the language content
        $response = $this->actingAs($teacher, 'sanctum')
                         ->getJson("/api/teacher/languages/{$language->id}/content");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'teacher' => [
                             'id' => $teacher->id,
                             'name' => $teacher->name,
                         ],
                         'language' => [
                             'id' => $language->id,
                             'code' => $language->code,
                             'name' => $language->name,
                         ],
                         'message' => 'You have access to this language content',
                     ],
                 ]);
    }

    public function test_teacher_cannot_access_unassigned_language_content(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        
        $language = Language::factory()->create();

        // Teacher should not be able to access the language content
        $response = $this->actingAs($teacher, 'sanctum')
                         ->getJson("/api/teacher/languages/{$language->id}/content");

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Access denied. You are not assigned to this language.',
                 ]);
    }

    public function test_admin_can_access_any_language_content(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $language = Language::factory()->create();

        // Admin should be able to access any language content
        $response = $this->actingAs($admin, 'sanctum')
                         ->getJson("/api/teacher/languages/{$language->id}/content");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'teacher' => [
                             'id' => $admin->id,
                             'name' => $admin->name,
                         ],
                         'language' => [
                             'id' => $language->id,
                         ],
                         'message' => 'You have access to this language content',
                     ],
                 ]);
    }
}