<?php

namespace Tests\Feature\Api;

use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private User $admin;
    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        \Spatie\Permission\Models\Role::create(['name' => 'student']);
        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        // Create users with roles
        $this->student = User::factory()->create(['role' => 'student']);
        $this->student->assignRole('student');

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->teacher->assignRole('teacher');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        // Create program structure
        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $this->program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id
        ]);

        // Assign teacher to language
        $this->teacher->teacherLanguages()->attach($language->id);
    }

    public function test_student_can_only_access_student_endpoints()
    {
        // Act & Assert - Student endpoints (should work)
        $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/student/programs')
            ->assertStatus(200);

        $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/student/quizzes')
            ->assertStatus(200);

        // Teacher endpoints (should fail)
        $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/teacher/quizzes')
            ->assertStatus(403);

        $this->actingAs($this->student, 'sanctum')
            ->postJson('/api/teacher/quizzes', [])
            ->assertStatus(403);

        // Admin endpoints (should fail)
        $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/admin/programs')
            ->assertStatus(403);

        $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertStatus(403);
    }

    public function test_teacher_can_only_access_teacher_endpoints()
    {
        // Act & Assert - Teacher endpoints (should work)
        $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/teacher/quizzes')
            ->assertStatus(200);

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/teacher/meetings')
            ->assertStatus(200);

        // Student endpoints (should fail)
        $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/student/programs')
            ->assertStatus(403);

        // Admin endpoints (should fail)
        $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/admin/programs')
            ->assertStatus(403);

        $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/admin/users', [])
            ->assertStatus(403);
    }

    public function test_admin_can_access_all_endpoints()
    {
        // Act & Assert - Admin endpoints (should work)
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/programs')
            ->assertStatus(200);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertStatus(200);

        // Teacher endpoints (should work for admin)
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/teacher/quizzes')
            ->assertStatus(200);

        // Student endpoints (should work for admin)
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/student/programs')
            ->assertStatus(200);
    }

    public function test_teacher_can_only_manage_assigned_language_content()
    {
        // Arrange
        $otherLanguage = Language::factory()->create();
        $otherLevel = Level::factory()->create(['language_id' => $otherLanguage->id]);
        $otherProgram = Program::factory()->create([
            'language_id' => $otherLanguage->id,
            'level_id' => $otherLevel->id
        ]);

        // Act & Assert - Teacher can create quiz for assigned language
        $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/teacher/quizzes', [
                'program_id' => $this->program->id,
                'title' => 'Test Quiz',
                'type' => 'inline',
                'quiz_questions' => [
                    [
                        'question' => 'What is 2+2?',
                        'choices' => ['3', '4', '5', '6'],
                        'correct_answer' => '4'
                    ]
                ]
            ])
            ->assertStatus(201);

        // Teacher cannot create quiz for unassigned language
        $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/teacher/quizzes', [
                'program_id' => $otherProgram->id,
                'title' => 'Unauthorized Quiz',
                'type' => 'inline',
                'quiz_questions' => [
                    [
                        'question' => 'What is 1+1?',
                        'choices' => ['1', '2', '3', '4'],
                        'correct_answer' => '2'
                    ]
                ]
            ])
            ->assertStatus(403);
    }

    public function test_student_can_only_access_enrolled_programs()
    {
        // Arrange
        $this->student->enrollments()->create([
            'program_id' => $this->program->id,
            'access_granted_at' => now()
        ]);

        $otherProgram = Program::factory()->create();

        // Act & Assert - Student can access enrolled program
        $response = $this->actingAs($this->student, 'sanctum')
                        ->getJson('/api/student/programs');

        $response->assertStatus(200);
        $programIds = collect($response->json('data'))->pluck('id');
        $this->assertContains($this->program->id, $programIds);
        $this->assertNotContains($otherProgram->id, $programIds);
    }

    public function test_student_can_only_attempt_quizzes_from_enrolled_programs()
    {
        // Arrange
        $this->student->enrollments()->create([
            'program_id' => $this->program->id,
            'access_granted_at' => now()
        ]);

        $quiz = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher->id,
            'type' => 'inline'
        ]);

        // Create quiz questions for inline quiz
        $quiz->quizQuestions()->createMany([
            [
                'question' => 'What is 2+2?',
                'choices' => json_encode(['2', '3', '4', '5']),
                'correct_answer' => '4',
                'order' => 1
            ],
            [
                'question' => 'What is 3+3?',
                'choices' => json_encode(['5', '6', '7', '8']),
                'correct_answer' => '6',
                'order' => 2
            ]
        ]);

        $otherProgram = Program::factory()->create();
        $otherQuiz = Quiz::factory()->create([
            'program_id' => $otherProgram->id,
            'teacher_id' => $this->teacher->id,
            'type' => 'inline'
        ]);

        // Create quiz questions for other quiz
        $otherQuiz->quizQuestions()->createMany([
            [
                'question' => 'What is 1+1?',
                'choices' => json_encode(['1', '2', '3', '4']),
                'correct_answer' => '2',
                'order' => 1
            ],
            [
                'question' => 'What is 5+5?',
                'choices' => json_encode(['8', '9', '10', '11']),
                'correct_answer' => '10',
                'order' => 2
            ]
        ]);

        // Act & Assert - Student can attempt quiz from enrolled program
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/student/quizzes/{$quiz->id}/attempt", [
                'answers' => ['4', '6']
            ])
            ->assertStatus(201);

        // Student cannot attempt quiz from non-enrolled program
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/student/quizzes/{$otherQuiz->id}/attempt", [
                'answers' => ['2', '10']
            ])
            ->assertStatus(403);
    }

    public function test_teacher_can_only_edit_own_quizzes()
    {
        // Arrange
        $teacherQuiz = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher->id
        ]);

        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher->assignRole('teacher');
        $otherTeacher->teacherLanguages()->attach($this->program->language_id);

        $otherQuiz = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $otherTeacher->id
        ]);

        // Act & Assert - Teacher can edit own quiz
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/teacher/quizzes/{$teacherQuiz->id}", [
                'title' => 'Updated Title'
            ]);
        
        $response->assertStatus(200);

        // Teacher cannot edit other teacher's quiz
        $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/teacher/quizzes/{$otherQuiz->id}", [
                'title' => 'Hacked Title'
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_manage_all_content()
    {
        // Arrange
        $quiz = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher->id
        ]);

        // Act & Assert - Admin can manage any quiz
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/quizzes/{$quiz->id}", [
                'title' => 'Admin Updated Title'
            ])
            ->assertStatus(200);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/quizzes/{$quiz->id}")
            ->assertStatus(200);
    }

    public function test_unauthenticated_users_cannot_access_protected_endpoints()
    {
        // Act & Assert
        $this->getJson('/api/student/programs')->assertStatus(401);
        $this->getJson('/api/teacher/quizzes')->assertStatus(401);
        $this->getJson('/api/admin/users')->assertStatus(401);
        $this->postJson('/api/teacher/quizzes', [])->assertStatus(401);
        $this->putJson('/api/admin/programs/1', [])->assertStatus(401);
        $this->deleteJson('/api/teacher/quizzes/1')->assertStatus(401);
    }

    public function test_role_middleware_blocks_cross_role_access()
    {
        // Arrange
        $endpoints = [
            'student' => [
                '/api/student/programs',
                '/api/student/quizzes',
                '/api/student/meetings'
            ],
            'teacher' => [
                '/api/teacher/quizzes',
                '/api/teacher/meetings',
                '/api/teacher/profile'
            ],
            'admin' => [
                '/api/admin/users',
                '/api/admin/programs',
                '/api/admin/settings/guest-access'
            ]
        ];

        // Act & Assert - Each role should only access their own endpoints
        foreach ($endpoints as $role => $roleEndpoints) {
            foreach ($endpoints as $testRole => $testEndpoints) {
                $user = $role === 'student' ? $this->student : 
                       ($role === 'teacher' ? $this->teacher : $this->admin);

                foreach ($testEndpoints as $endpoint) {
                    $expectedStatus = ($role === $testRole || $role === 'admin') ? 200 : 403;
                    
                    $this->actingAs($user, 'sanctum')
                        ->getJson($endpoint)
                        ->assertStatus($expectedStatus);
                }
            }
        }
    }

    public function test_permission_based_access_control()
    {
        // Arrange - Create specific permissions
        \Spatie\Permission\Models\Permission::create(['name' => 'create quizzes']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage users']);
        \Spatie\Permission\Models\Permission::create(['name' => 'view reports']);

        // Assign permissions to roles
        $teacherRole = \Spatie\Permission\Models\Role::findByName('teacher');
        $adminRole = \Spatie\Permission\Models\Role::findByName('admin');

        $teacherRole->givePermissionTo('create quizzes');
        $adminRole->givePermissionTo(['create quizzes', 'manage users', 'view reports']);

        // Act & Assert - Check permission-based access
        $this->assertTrue($this->teacher->can('create quizzes'));
        $this->assertFalse($this->teacher->can('manage users'));
        $this->assertFalse($this->student->can('create quizzes'));
        
        $this->assertTrue($this->admin->can('create quizzes'));
        $this->assertTrue($this->admin->can('manage users'));
        $this->assertTrue($this->admin->can('view reports'));
    }
}