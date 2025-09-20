<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'teacher']);
        Role::firstOrCreate(['name' => 'student']);
    }

    public function test_teacher_can_get_profile(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'image_path' => 'teacher-images/test-image.jpg'
        ]);
        $teacher->assignRole('teacher');

        $response = $this->actingAs($teacher, 'sanctum')
                         ->getJson('/api/teacher/profile');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'teacher' => [
                             'id' => $teacher->id,
                             'name' => $teacher->name,
                             'email' => $teacher->email,
                             'image_path' => 'teacher-images/test-image.jpg',
                         ]
                     ]
                 ]);
    }

    public function test_non_teacher_cannot_access_teacher_profile(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $student->assignRole('student');

        $response = $this->actingAs($student, 'sanctum')
                         ->getJson('/api/teacher/profile');

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthorized. Required roles: teacher, admin'
                 ]);
    }

    public function test_teacher_can_update_profile_with_image(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        $file = UploadedFile::fake()->image('profile.jpg', 100, 100);

        $response = $this->actingAs($teacher, 'sanctum')
                         ->putJson('/api/teacher/profile', [
                             'name' => 'Updated Teacher Name',
                             'image' => $file
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Teacher profile updated successfully'
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'name' => 'Updated Teacher Name'
        ]);

        // Check that image was stored
        $updatedTeacher = User::find($teacher->id);
        $this->assertNotNull($updatedTeacher->image_path);
        Storage::disk('public')->assertExists($updatedTeacher->image_path);
    }

    public function test_teacher_can_remove_profile_image(): void
    {
        Storage::disk('public')->put('teacher-images/existing-image.jpg', 'fake content');
        
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'image_path' => 'teacher-images/existing-image.jpg'
        ]);
        $teacher->assignRole('teacher');

        $response = $this->actingAs($teacher, 'sanctum')
                         ->deleteJson('/api/teacher/profile/image');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Teacher profile image removed successfully'
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'image_path' => null
        ]);
    }

    public function test_admin_can_get_all_teachers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        
        $teacher1 = User::factory()->create(['role' => 'teacher']);
        $teacher1->assignRole('teacher');
        
        $teacher2 = User::factory()->create(['role' => 'teacher']);
        $teacher2->assignRole('teacher');
        
        $student = User::factory()->create(['role' => 'student']);
        $student->assignRole('student');

        $response = $this->actingAs($admin, 'sanctum')
                         ->getJson('/api/teachers');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'total' => 2
                     ]
                 ])
                 ->assertJsonCount(2, 'data.teachers');
    }

    public function test_image_validation_rejects_invalid_files(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($teacher, 'sanctum')
                         ->putJson('/api/teacher/profile', [
                             'image' => $file
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }

    public function test_image_validation_rejects_oversized_files(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        $file = UploadedFile::fake()->image('large.jpg')->size(6000); // 6MB

        $response = $this->actingAs($teacher, 'sanctum')
                         ->putJson('/api/teacher/profile', [
                             'image' => $file
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }
}