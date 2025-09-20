<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherLanguageAssignmentTest extends TestCase
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

    public function test_admin_can_assign_teacher_to_language(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        
        $language = Language::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
                         ->postJson('/api/admin/assign-teacher-language', [
                             'teacher_id' => $teacher->id,
                             'language_id' => $language->id,
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Teacher assigned to language successfully'
                 ]);

        $this->assertDatabaseHas('teacher_languages', [
            'user_id' => $teacher->id,
            'language_id' => $language->id,
            'assigned_by' => $admin->id,
        ]);
    }

    public function test_admin_can_assign_teacher_to_multiple_languages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        
        $language1 = Language::factory()->create();
        $language2 = Language::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
                         ->postJson('/api/admin/assign-teacher-multiple-languages', [
                             'teacher_id' => $teacher->id,
                             'language_ids' => [$language1->id, $language2->id],
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'assignments_count' => 2
                     ]
                 ]);

        $this->assertDatabaseHas('teacher_languages', [
            'user_id' => $teacher->id,
            'language_id' => $language1->id,
        ]);

        $this->assertDatabaseHas('teacher_languages', [
            'user_id' => $teacher->id,
            'language_id' => $language2->id,
        ]);
    }

    public function test_admin_can_remove_teacher_from_language(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        
        $language = Language::factory()->create();

        // First assign the teacher
        $this->actingAs($admin, 'sanctum')
             ->postJson('/api/admin/assign-teacher-language', [
                 'teacher_id' => $teacher->id,
                 'language_id' => $language->id,
             ]);

        // Then remove the assignment
        $response = $this->actingAs($admin, 'sanctum')
                         ->deleteJson('/api/admin/remove-teacher-language', [
                             'teacher_id' => $teacher->id,
                             'language_id' => $language->id,
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Teacher removed from language successfully'
                 ]);

        $this->assertDatabaseMissing('teacher_languages', [
            'user_id' => $teacher->id,
            'language_id' => $language->id,
        ]);
    }

    public function test_teacher_can_view_assigned_languages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        
        $language1 = Language::factory()->create(['name' => 'English']);
        $language2 = Language::factory()->create(['name' => 'Spanish']);

        // Assign languages to teacher
        $this->actingAs($admin, 'sanctum')
             ->postJson('/api/admin/assign-teacher-multiple-languages', [
                 'teacher_id' => $teacher->id,
                 'language_ids' => [$language1->id, $language2->id],
             ]);

        // Teacher views their languages
        $response = $this->actingAs($teacher, 'sanctum')
                         ->getJson('/api/teacher/my-languages');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'total' => 2
                     ]
                 ])
                 ->assertJsonCount(2, 'data.languages');
    }

    public function test_teacher_can_check_language_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        
        $assignedLanguage = Language::factory()->create();
        $unassignedLanguage = Language::factory()->create();

        // Assign one language to teacher
        $this->actingAs($admin, 'sanctum')
             ->postJson('/api/admin/assign-teacher-language', [
                 'teacher_id' => $teacher->id,
                 'language_id' => $assignedLanguage->id,
             ]);

        // Check access to assigned language
        $response = $this->actingAs($teacher, 'sanctum')
                         ->getJson("/api/teacher/languages/{$assignedLanguage->id}/access");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'has_access' => true
                     ]
                 ]);

        // Check access to unassigned language
        $response = $this->actingAs($teacher, 'sanctum')
                         ->getJson("/api/teacher/languages/{$unassignedLanguage->id}/access");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'has_access' => false
                     ]
                 ]);
    }

    public function test_admin_can_get_teachers_by_language(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $teacher1 = User::factory()->create(['role' => 'teacher']);
        $teacher1->assignRole('teacher');
        
        $teacher2 = User::factory()->create(['role' => 'teacher']);
        $teacher2->assignRole('teacher');
        
        $language = Language::factory()->create();

        // Assign both teachers to the language
        $this->actingAs($admin, 'sanctum')
             ->postJson('/api/admin/assign-teacher-multiple-languages', [
                 'teacher_id' => $teacher1->id,
                 'language_ids' => [$language->id],
             ]);

        $this->actingAs($admin, 'sanctum')
             ->postJson('/api/admin/assign-teacher-language', [
                 'teacher_id' => $teacher2->id,
                 'language_id' => $language->id,
             ]);

        // Get teachers for the language
        $response = $this->actingAs($admin, 'sanctum')
                         ->getJson("/api/admin/languages/{$language->id}/teachers");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'total' => 2
                     ]
                 ])
                 ->assertJsonCount(2, 'data.teachers');
    }

    public function test_public_can_get_teachers_by_language(): void
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

        // Public access (no authentication)
        $response = $this->getJson("/api/languages/{$language->id}/teachers");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'total' => 1
                     ]
                 ])
                 ->assertJsonCount(1, 'data.teachers');
    }

    public function test_non_admin_cannot_assign_teachers(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        
        $anotherTeacher = User::factory()->create(['role' => 'teacher']);
        $anotherTeacher->assignRole('teacher');
        
        $language = Language::factory()->create();

        $response = $this->actingAs($teacher, 'sanctum')
                         ->postJson('/api/admin/assign-teacher-language', [
                             'teacher_id' => $anotherTeacher->id,
                             'language_id' => $language->id,
                         ]);

        $response->assertStatus(403);
    }

    public function test_cannot_assign_non_teacher_to_language(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $student = User::factory()->create(['role' => 'student']);
        $student->assignRole('student');
        
        $language = Language::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
                         ->postJson('/api/admin/assign-teacher-language', [
                             'teacher_id' => $student->id,
                             'language_id' => $language->id,
                         ]);

        $response->assertStatus(500)
                 ->assertJson([
                     'success' => false,
                     'error' => 'User must be a teacher to be assigned to a language.'
                 ]);
    }
}