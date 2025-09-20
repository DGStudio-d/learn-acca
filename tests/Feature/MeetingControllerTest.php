<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use App\Models\Meeting;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeetingControllerTest extends TestCase
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

    public function test_teacher_can_create_meeting()
    {
        // Create test data
        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        $teacher->teacherLanguages()->attach($language->id);

        Sanctum::actingAs($teacher);

        $meetingData = [
            'program_id' => $program->id,
            'title' => 'Test Meeting',
            'description' => 'This is a test meeting',
            'meeting_link' => 'https://zoom.us/j/123456789',
            'start_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'timezone' => 'UTC',
        ];

        $response = $this->postJson('/api/teacher/meetings', $meetingData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'title' => 'Test Meeting',
                        'description' => 'This is a test meeting',
                        'meeting_link' => 'https://zoom.us/j/123456789',
                    ],
                ]);

        $this->assertDatabaseHas('meetings', [
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'title' => 'Test Meeting',
        ]);
    }

    public function test_student_can_view_meetings_for_approved_programs()
    {
        // Create test data
        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');

        $student = User::factory()->create(['role' => 'student']);
        $student->assignRole('student');

        // Create enrollment with access granted
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
            'start_time' => now()->addHours(2),
            'active' => true,
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/meetings');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_can_view_all_meetings()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');

        Meeting::factory()->count(3)->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/teacher/meetings');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_teacher_cannot_create_meeting_for_unassigned_language()
    {
        $language1 = Language::factory()->create();
        $language2 = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language2->id]);
        $program = Program::factory()->create([
            'language_id' => $language2->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        // Teacher is only assigned to language1, not language2
        $teacher->teacherLanguages()->attach($language1->id);

        Sanctum::actingAs($teacher);

        $meetingData = [
            'program_id' => $program->id,
            'title' => 'Test Meeting',
            'meeting_link' => 'https://zoom.us/j/123456789',
            'start_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'timezone' => 'UTC',
        ];

        $response = $this->postJson('/api/teacher/meetings', $meetingData);

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'MEETING_CREATION_ERROR',
                    ],
                ]);
    }

    public function test_meeting_validation_requires_future_time()
    {
        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');
        $teacher->teacherLanguages()->attach($language->id);

        Sanctum::actingAs($teacher);

        $meetingData = [
            'program_id' => $program->id,
            'title' => 'Test Meeting',
            'meeting_link' => 'https://zoom.us/j/123456789',
            'start_time' => now()->subHours(1)->format('Y-m-d H:i:s'), // Past time
            'timezone' => 'UTC',
        ];

        $response = $this->postJson('/api/teacher/meetings', $meetingData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                    ],
                ]);
    }
}