<?php

namespace Tests\Feature\Api;

use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuizControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private User $admin;
    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        \Spatie\Permission\Models\Role::create(['name' => 'student']);
        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        // Create users
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->teacher->assignRole('teacher');

        $this->student = User::factory()->create(['role' => 'student']);
        $this->student->assignRole('student');

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
        $this->teacher->teacherLanguages()->attach($language->id, [
            'assigned_at' => now(),
            'assigned_by' => $this->admin->id ?? null
        ]);

        Storage::fake('public');
    }

    public function test_teacher_can_create_inline_quiz()
    {
        // Arrange
        $quizData = [
            'program_id' => $this->program->id,
            'title' => 'Test Quiz',
            'description' => 'Test Description',
            'type' => 'inline',
            'pass_score' => 80,
            'allow_guest_access' => false,
            'active' => true,
            'quiz_questions' => [
                [
                    'question' => 'What is 2+2?',
                    'choices' => ['3', '4', '5', '6'],
                    'correct_answer' => '4'
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($this->teacher, 'sanctum')
                        ->postJson('/api/teacher/quizzes', $quizData);

        // Assert
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'title',
                        'type',
                        'pass_score',
                        'quiz_questions'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('quizzes', [
            'title' => 'Test Quiz',
            'type' => 'inline',
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher->id
        ]);

        $this->assertDatabaseHas('quiz_questions', [
            'question' => 'What is 2+2?',
            'correct_answer' => '4'
        ]);
    }

    public function test_teacher_can_create_file_quiz()
    {
        // Arrange
        $file = UploadedFile::fake()->create('quiz.pdf', 100, 'application/pdf');
        $quizData = [
            'program_id' => $this->program->id,
            'title' => 'File Quiz',
            'description' => 'File Description',
            'type' => 'file',
            'pass_score' => 70
        ];

        // Act
        $response = $this->actingAs($this->teacher, 'sanctum')
                        ->postJson('/api/teacher/quizzes', array_merge($quizData, [
                            'file' => $file
                        ]));

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('quizzes', [
            'title' => 'File Quiz',
            'type' => 'file',
            'program_id' => $this->program->id
        ]);

        $quiz = Quiz::where('title', 'File Quiz')->first();
        $this->assertNotNull($quiz->file_path);
        Storage::disk('public')->assertExists($quiz->file_path);
    }

    public function test_teacher_cannot_create_quiz_for_unassigned_program()
    {
        // Arrange
        $otherLanguage = Language::factory()->create();
        $otherLevel = Level::factory()->create(['language_id' => $otherLanguage->id]);
        $otherProgram = Program::factory()->create([
            'language_id' => $otherLanguage->id,
            'level_id' => $otherLevel->id
        ]);

        $quizData = [
            'program_id' => $otherProgram->id,
            'title' => 'Unauthorized Quiz',
            'type' => 'inline',
            'quiz_questions' => [
                [
                    'question' => 'Sample question?',
                    'choices' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A'
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($this->teacher, 'sanctum')
                        ->postJson('/api/teacher/quizzes', $quizData);

        // Assert
        $response->assertStatus(403);
    }

    public function test_student_cannot_create_quiz()
    {
        // Arrange
        $quizData = [
            'program_id' => $this->program->id,
            'title' => 'Student Quiz',
            'type' => 'inline'
        ];

        // Act
        $response = $this->actingAs($this->student, 'sanctum')
                        ->postJson('/api/teacher/quizzes', $quizData);

        // Assert
        $response->assertStatus(403);
    }

    public function test_teacher_can_get_their_quizzes()
    {
        // Arrange
        $quiz1 = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Quiz 1'
        ]);

        $quiz2 = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Quiz 2'
        ]);

        // Create quiz by another teacher
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $otherTeacher->id,
            'title' => 'Other Quiz'
        ]);

        // Act
        $response = $this->actingAs($this->teacher, 'sanctum')
                        ->getJson('/api/teacher/quizzes');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'title',
                                'type',
                                'program'
                            ]
                        ]
                    ]
                ]);

        $quizTitles = collect($response->json('data.data'))->pluck('title');
        $this->assertContains('Quiz 1', $quizTitles);
        $this->assertContains('Quiz 2', $quizTitles);
        $this->assertNotContains('Other Quiz', $quizTitles);
    }

    public function test_teacher_can_update_quiz()
    {
        // Arrange
        $quiz = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Original Title',
            'type' => 'inline'
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'pass_score' => 85
        ];

        // Act
        $response = $this->actingAs($this->teacher, 'sanctum')
                        ->putJson("/api/teacher/quizzes/{$quiz->id}", $updateData);

        // Assert
        if ($response->status() !== 200) {
            dump('Response status:', $response->status());
            dump('Response body:', $response->json());
        }
        $response->assertStatus(200);

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'pass_score' => 85
        ]);
    }

    public function test_teacher_cannot_update_other_teacher_quiz()
    {
        // Arrange
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $quiz = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $otherTeacher->id
        ]);

        $updateData = ['title' => 'Hacked Title'];

        // Act
        $response = $this->actingAs($this->teacher, 'sanctum')
                        ->putJson("/api/teacher/quizzes/{$quiz->id}", $updateData);

        // Assert
        $response->assertStatus(403);
    }

    public function test_teacher_can_delete_quiz()
    {
        // Arrange
        $quiz = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher->id
        ]);

        // Act
        $response = $this->actingAs($this->teacher, 'sanctum')
                        ->deleteJson("/api/teacher/quizzes/{$quiz->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
    }

    public function test_quiz_creation_validates_required_fields()
    {
        // Act
        $response = $this->actingAs($this->teacher, 'sanctum')
                        ->postJson('/api/teacher/quizzes', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['program_id', 'title', 'type']);
    }

    public function test_quiz_creation_validates_file_type_for_file_quiz()
    {
        // Arrange
        $invalidFile = UploadedFile::fake()->create('quiz.exe', 100, 'application/exe');
        $quizData = [
            'program_id' => $this->program->id,
            'title' => 'File Quiz',
            'type' => 'file',
            'file' => $invalidFile
        ];

        // Act
        $response = $this->actingAs($this->teacher, 'sanctum')
                        ->postJson('/api/teacher/quizzes', $quizData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
    }

    public function test_unauthenticated_user_cannot_access_quiz_endpoints()
    {
        // Act & Assert
        $this->getJson('/api/teacher/quizzes')->assertStatus(401);
        $this->postJson('/api/teacher/quizzes', [])->assertStatus(401);
        $this->putJson('/api/teacher/quizzes/1', [])->assertStatus(401);
        $this->deleteJson('/api/teacher/quizzes/1')->assertStatus(401);
    }

    public function test_admin_can_view_all_quizzes()
    {
        // Arrange
        $quiz1 = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher->id
        ]);

        $quiz2 = Quiz::factory()->create([
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher->id
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
                        ->getJson('/api/admin/quizzes');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'title',
                                'type',
                                'program',
                                'teacher'
                            ]
                        ]
                    ]
                ]);
    }
}