<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherLanguageDebugTest2 extends TestCase
{
    use RefreshDatabase;

    public function test_debug_get_teachers_by_language(): void
    {
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'teacher']);
        
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        
        $language = Language::factory()->create();

        // First assign the teacher
        $assignResponse = $this->actingAs($admin, 'sanctum')
                               ->postJson('/api/admin/assign-teacher-language', [
                                   'teacher_id' => $teacher->id,
                                   'language_id' => $language->id,
                               ]);

        dump('Assignment Response Status: ' . $assignResponse->status());
        dump('Assignment Response: ' . $assignResponse->getContent());

        // Check if assignment was created
        $this->assertDatabaseHas('teacher_languages', [
            'user_id' => $teacher->id,
            'language_id' => $language->id,
        ]);

        // Try to call the service method directly
        $teacherLanguageService = app(\App\Services\TeacherLanguageService::class);
        try {
            $teachers = $teacherLanguageService->getTeachersByLanguage($language);
            dump('Service method worked, teachers count: ' . $teachers->count());
        } catch (\Exception $e) {
            dump('Service method error: ' . $e->getMessage());
        }

        // Then try to get teachers for the language
        $response = $this->actingAs($admin, 'sanctum')
                         ->getJson("/api/admin/languages/{$language->id}/teachers");

        // Debug the response
        if ($response->status() !== 200) {
            dump('Get Teachers Response Status: ' . $response->status());
            dump('Get Teachers Response Content: ' . $response->getContent());
        }

        $response->assertStatus(200);
    }
}