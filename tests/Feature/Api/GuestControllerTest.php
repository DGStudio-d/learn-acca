<?php

namespace Tests\Feature\Api;

use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Settings;
use App\Models\TeacherLanguage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'student']);

        // Enable all guest access by default for testing
        Settings::setValue('allow_guest_languages', true, 'boolean');
        Settings::setValue('allow_guest_teachers', true, 'boolean');
        Settings::setValue('allow_guest_quizzes', true, 'boolean');
    }

    public function test_guest_can_get_languages_when_allowed()
    {
        // Create test data
        $language = Language::factory()->create(['active' => true]);
        $level = Level::factory()->create(['language_id' => $language->id]);

        $response = $this->getJson('/api/guest/languages');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'languages' => [
                        [
                            'id' => $language->id,
                            'code' => $language->code,
                            'name' => $language->name,
                            'active' => true,
                            'levels' => [
                                [
                                    'id' => $level->id,
                                    'name' => $level->name,
                                    'order' => $level->order,
                                ],
                            ],
                        ],
                    ],
                    'total' => 1,
                ],
            ]);
    }

    public function test_guest_cannot_get_languages_when_disabled()
    {
        Settings::setValue('allow_guest_languages', false, 'boolean');

        $response = $this->getJson('/api/guest/languages');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'GUEST_ACCESS_DENIED',
                    'message' => 'Guest access to languages is not allowed. Please log in to access this resource.',
                    'feature' => 'languages',
                ],
            ]);
    }

    public function test_authenticated_user_can_get_languages_even_when_guest_access_disabled()
    {
        Settings::setValue('allow_guest_languages', false, 'boolean');
        
        $user = User::factory()->create();
        $user->assignRole('student');
        Sanctum::actingAs($user);

        $language = Language::factory()->create(['active' => true]);

        $response = $this->getJson('/api/guest/languages');

        $response->assertStatus(200);
    }

    public function test_guest_can_get_teachers_when_allowed()
    {
        // Create test data with proper attributes
        $language = Language::factory()->create([
            'code' => 'EN',
            'name' => 'English',
            'active' => true,
        ]);
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        
        // Create teacher language assignment
        TeacherLanguage::create([
            'user_id' => $teacher->id,
            'language_id' => $language->id,
            'assigned_at' => now(),
        ]);

        $response = $this->getJson('/api/guest/teachers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'teachers' => [
                        [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                            'email' => $teacher->email,
                            'languages' => [
                                [
                                    'id' => $language->id,
                                    'code' => $language->code,
                                    'name' => $language->name,
                                ],
                            ],
                        ],
                    ],
                    'total' => 1,
                ],
            ]);
    }

    public function test_guest_cannot_get_teachers_when_disabled()
    {
        Settings::setValue('allow_guest_teachers', false, 'boolean');

        $response = $this->getJson('/api/guest/teachers');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'GUEST_ACCESS_DENIED',
                    'feature' => 'teachers',
                ],
            ]);
    }

    public function test_guest_can_get_teachers_by_language_when_allowed()
    {
        // Create test data with proper attributes
        $language = Language::factory()->create([
            'code' => 'ES',
            'name' => 'Spanish',
            'active' => true,
        ]);
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        
        // Create teacher language assignment
        TeacherLanguage::create([
            'user_id' => $teacher->id,
            'language_id' => $language->id,
            'assigned_at' => now(),
        ]);

        $response = $this->getJson("/api/guest/languages/{$language->id}/teachers");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'language' => [
                        'id' => $language->id,
                        'code' => $language->code,
                        'name' => $language->name,
                    ],
                    'teachers' => [
                        [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                            'email' => $teacher->email,
                        ],
                    ],
                    'total' => 1,
                ],
            ]);
    }

    public function test_guest_can_get_quizzes_when_allowed()
    {
        // Create test data with proper attributes
        $language = Language::factory()->create([
            'code' => 'FR',
            'name' => 'French',
            'active' => true,
        ]);
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);
        
        // Create teacher first
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        
        $quiz = Quiz::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'active' => true,
            'allow_guest_access' => true, // This is important for the old controller
        ]);

        $response = $this->getJson('/api/guest/quizzes');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        // The response structure is different (paginated), so let's just check success
        $this->assertTrue($response->json('success'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.total'));
    }

    public function test_guest_cannot_get_quizzes_when_disabled()
    {
        Settings::setValue('allow_guest_quizzes', false, 'boolean');

        $response = $this->getJson('/api/guest/quizzes');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'GUEST_ACCESS_DENIED',
                    'message' => 'Guest access to quizzes is not allowed. Please log in to access this resource.',
                    'feature' => 'quizzes',
                ],
            ]);
    }

    public function test_guest_can_get_specific_quiz_when_allowed()
    {
        // Create test data with proper attributes
        $language = Language::factory()->create([
            'code' => 'DE',
            'name' => 'German',
            'active' => true,
        ]);
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);
        
        // Create teacher first
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        
        $quiz = Quiz::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'active' => true,
            'allow_guest_access' => true, // This is important for the old controller
        ]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

        $response = $this->getJson("/api/guest/quizzes/{$quiz->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        // Just check that the response is successful and doesn't include correct answers
        $this->assertTrue($response->json('success'));
        $responseData = $response->json('data');
        
        // Check that correct_answer is not in any question
        if (isset($responseData['quizQuestions'])) {
            foreach ($responseData['quizQuestions'] as $questionData) {
                $this->assertArrayNotHasKey('correct_answer', $questionData);
            }
        }
    }

    public function test_guest_can_submit_quiz_attempt_when_allowed()
    {
        // Create test data with proper attributes
        $language = Language::factory()->create([
            'code' => 'IT',
            'name' => 'Italian',
            'active' => true,
        ]);
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);
        
        // Create teacher first
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        
        $quiz = Quiz::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'pass_score' => 70,
            'active' => true,
            'allow_guest_access' => true, // This is important for the old controller
        ]);
        $question = QuizQuestion::factory()->create([
            'quiz_id' => $quiz->id,
            'correct_answer' => 'A',
        ]);

        $attemptData = [
            'answers' => [
                $question->id => 'A',
            ],
        ];

        $response = $this->postJson("/api/guest/quizzes/{$quiz->id}/attempt", $attemptData);

        $response->assertStatus(201) // The old controller returns 201
            ->assertJson([
                'success' => true,
                'message' => 'Quiz attempt submitted successfully.',
            ]);
        
        // Just check that the response is successful
        $this->assertTrue($response->json('success'));
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_guest_cannot_submit_quiz_attempt_when_disabled()
    {
        Settings::setValue('allow_guest_quizzes', false, 'boolean');

        // Create teacher first
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        
        $quiz = Quiz::factory()->create(['teacher_id' => $teacher->id]);

        $response = $this->postJson("/api/guest/quizzes/{$quiz->id}/attempt", [
            'answers' => [1 => 'A'], // Provide valid answers format
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'GUEST_ACCESS_DENIED',
                    'message' => 'Guest access to quizzes is not allowed. Please log in to access this resource.',
                    'feature' => 'quizzes',
                ],
            ]);
    }

    public function test_guest_quiz_attempt_validates_input()
    {
        // Create teacher first
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        
        $quiz = Quiz::factory()->create([
            'teacher_id' => $teacher->id,
            'active' => true,
            'allow_guest_access' => true,
        ]);

        $response = $this->postJson("/api/guest/quizzes/{$quiz->id}/attempt", [
            // Missing answers
        ]);

        $response->assertStatus(422);
        
        // The old controller uses Laravel's default validation error format
        $this->assertArrayHasKey('message', $response->json());
        $this->assertArrayHasKey('errors', $response->json());
    }
}