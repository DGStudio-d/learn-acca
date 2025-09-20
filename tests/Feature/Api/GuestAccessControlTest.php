<?php

namespace Tests\Feature\Api;

use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use App\Models\Quiz;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        \Spatie\Permission\Models\Role::create(['name' => 'student']);
        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        // Initialize default settings
        Settings::create(['key' => 'allow_guest_languages', 'value' => 'false', 'type' => 'boolean']);
        Settings::create(['key' => 'allow_guest_teachers', 'value' => 'false', 'type' => 'boolean']);
        Settings::create(['key' => 'allow_guest_quizzes', 'value' => 'false', 'type' => 'boolean']);
    }

    public function test_guest_cannot_access_languages_when_disabled()
    {
        // Arrange
        Language::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/guest/languages');

        // Assert
        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'GUEST_ACCESS_DENIED',
                        'message' => 'Guest access to languages is not allowed. Please log in to access this resource.',
                        'feature' => 'languages'
                    ]
                ]);
    }

    public function test_guest_can_access_languages_when_enabled()
    {
        // Arrange
        Settings::where('key', 'allow_guest_languages')->update(['value' => 'true']);
        $languages = Language::factory()->count(3)->active()->create();

        // Act
        $response = $this->getJson('/api/guest/languages');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'languages' => [
                            '*' => [
                                'id',
                                'name',
                                'code'
                            ]
                        ],
                        'total'
                    ]
                ]);

        $this->assertCount(3, $response->json('data.languages'));
    }

    public function test_guest_cannot_access_teachers_when_disabled()
    {
        // Arrange
        User::factory()->count(2)->create(['role' => 'teacher']);

        // Act
        $response = $this->getJson('/api/guest/teachers');

        // Assert
        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'GUEST_ACCESS_DENIED',
                        'message' => 'Guest access to teachers is not allowed. Please log in to access this resource.',
                        'feature' => 'teachers'
                    ]
                ]);
    }

    public function test_guest_can_access_teachers_when_enabled()
    {
        // Arrange
        Settings::where('key', 'allow_guest_teachers')->update(['value' => 'true']);
        
        $language = Language::factory()->create();
        $teacher1 = User::factory()->create(['role' => 'teacher']);
        $teacher2 = User::factory()->create(['role' => 'teacher']);
        
        $teacher1->assignRole('teacher');
        $teacher2->assignRole('teacher');
        
        $teacher1->teacherLanguages()->attach($language->id);
        $teacher2->teacherLanguages()->attach($language->id);

        // Act
        $response = $this->getJson('/api/guest/teachers');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'teachers' => [
                            '*' => [
                                'id',
                                'name',
                                'email'
                            ]
                        ],
                        'total'
                    ]
                ]);

        $this->assertCount(2, $response->json('data.teachers'));
    }

    public function test_guest_cannot_access_quizzes_when_disabled()
    {
        // Arrange
        $program = Program::factory()->create();
        Quiz::factory()->create([
            'program_id' => $program->id,
            'allow_guest_access' => true
        ]);

        // Act
        $response = $this->getJson('/api/guest/quizzes');

        // Assert
        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'GUEST_ACCESS_DENIED',
                        'message' => 'Guest access to quizzes is not allowed. Please log in to access this resource.',
                        'feature' => 'quizzes'
                    ]
                ]);
    }

    public function test_guest_can_access_quizzes_when_enabled()
    {
        // Arrange
        Settings::where('key', 'allow_guest_quizzes')->update(['value' => 'true']);
        
        $program = Program::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $quiz1 = Quiz::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'allow_guest_access' => true,
            'active' => true
        ]);

        $quiz2 = Quiz::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'allow_guest_access' => false,
            'active' => true
        ]);

        // Act
        $response = $this->getJson('/api/guest/quizzes');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'quizzes' => [
                            '*' => [
                                'id',
                                'title',
                                'description',
                                'type',
                                'program'
                            ]
                        ],
                        'total'
                    ]
                ]);

        // Should only return quiz with guest access enabled
        $this->assertCount(1, $response->json('data.quizzes'));
        $this->assertEquals($quiz1->id, $response->json('data.quizzes.0.id'));
    }

    public function test_guest_can_attempt_quiz_when_enabled()
    {
        // Arrange
        Settings::where('key', 'allow_guest_quizzes')->update(['value' => 'true']);
        
        $program = Program::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $quiz = Quiz::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'allow_guest_access' => true,
            'active' => true,
            'type' => 'inline'
        ]);



        $question = \App\Models\QuizQuestion::factory()->create([
            'quiz_id' => $quiz->id,
            'question' => 'What is 2+2?',
            'choices' => ['3', '4', '5', '6'],
            'correct_answer' => '4'
        ]);

        $attemptData = [
            'answers' => [
                $question->id => '4'
            ]
        ];

        // Act
        $response = $this->postJson("/api/guest/quizzes/{$quiz->id}/attempt", $attemptData);



        // Assert
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'score',
                        'passed',
                        'total_questions',
                        'correct_answers'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'student_id' => null, // Guest attempt
            'score' => 100
        ]);
    }

    public function test_guest_cannot_attempt_quiz_when_disabled()
    {
        // Arrange
        $program = Program::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $quiz = Quiz::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'allow_guest_access' => true
        ]);

        $attemptData = ['answers' => []];

        // Act
        $response = $this->postJson("/api/guest/quizzes/{$quiz->id}/attempt", $attemptData);

        // Assert
        $response->assertStatus(403);
    }

    public function test_admin_can_update_guest_settings()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $settingsData = [
            'allow_guest_languages' => true,
            'allow_guest_teachers' => true,
            'allow_guest_quizzes' => false
        ];

        // Act
        $response = $this->actingAs($admin, 'sanctum')
                        ->putJson('/api/admin/settings/guest-access', $settingsData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Guest access settings updated successfully'
                ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'allow_guest_languages',
            'value' => '1'
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'allow_guest_teachers',
            'value' => '1'
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'allow_guest_quizzes',
            'value' => ''
        ]);
    }

    public function test_non_admin_cannot_update_guest_settings()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');

        $settingsData = [
            'allow_guest_languages' => true
        ];

        // Act
        $response = $this->actingAs($teacher, 'sanctum')
                        ->putJson('/api/admin/settings/guest-access', $settingsData);

        // Assert
        $response->assertStatus(403);
    }

    public function test_guest_access_middleware_blocks_unauthorized_endpoints()
    {
        // Arrange - All guest access disabled by default

        // Act & Assert
        $this->getJson('/api/guest/languages')->assertStatus(403);
        $this->getJson('/api/guest/teachers')->assertStatus(403);
        $this->getJson('/api/guest/quizzes')->assertStatus(403);
    }

    public function test_guest_access_settings_are_cached()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        // Enable guest languages
        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/settings/guest-access', [
                'allow_guest_languages' => true
            ]);

        // Act - First request should work
        $response1 = $this->getJson('/api/guest/languages');
        $response1->assertStatus(200);

        // Manually disable in database (bypassing cache)
        Settings::where('key', 'allow_guest_languages')->update(['value' => 'false']);

        // Second request should still work due to caching
        $response2 = $this->getJson('/api/guest/languages');
        $response2->assertStatus(200);
    }

    public function test_guest_endpoints_return_appropriate_data_structure()
    {
        // Arrange
        Settings::where('key', 'allow_guest_languages')->update(['value' => 'true']);
        Settings::where('key', 'allow_guest_teachers')->update(['value' => 'true']);
        Settings::where('key', 'allow_guest_quizzes')->update(['value' => 'true']);

        $language = Language::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->teacherLanguages()->attach($language->id);

        $program = Program::factory()->create(['language_id' => $language->id]);
        Quiz::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'allow_guest_access' => true
        ]);

        // Act & Assert
        $this->getJson('/api/guest/languages')
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'languages' => [
                        '*' => ['id', 'name', 'code']
                    ],
                    'total'
                ]
            ]);

        $this->getJson('/api/guest/teachers')
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'teachers' => [
                        '*' => ['id', 'name', 'email']
                    ],
                    'total'
                ]
            ]);

        $this->getJson('/api/guest/quizzes')
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'quizzes' => [
                        '*' => ['id', 'title', 'type', 'program']
                    ],
                    'total'
                ]
            ]);
    }
}