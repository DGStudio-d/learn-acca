<?php

namespace Tests\Unit\Models;

use App\Models\Program;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_has_fillable_attributes()
    {
        // Arrange
        $fillable = [
            'program_id',
            'teacher_id',
            'title',
            'description',
            'type',
            'file_path',
            'pass_score',
            'allow_guest_access',
            'active',
        ];

        // Act
        $quiz = new Quiz();

        // Assert
        $this->assertEquals($fillable, $quiz->getFillable());
    }

    public function test_quiz_casts_attributes_correctly()
    {
        // Arrange
        $quiz = Quiz::factory()->create([
            'pass_score' => 75,
            'allow_guest_access' => true,
            'active' => false,
        ]);

        // Act & Assert
        $this->assertIsInt($quiz->pass_score);
        $this->assertIsBool($quiz->allow_guest_access);
        $this->assertIsBool($quiz->active);
    }

    public function test_quiz_belongs_to_program()
    {
        // Arrange
        $program = Program::factory()->create();
        $quiz = Quiz::factory()->create(['program_id' => $program->id]);

        // Act
        $quizProgram = $quiz->program;

        // Assert
        $this->assertInstanceOf(Program::class, $quizProgram);
        $this->assertEquals($program->id, $quizProgram->id);
    }

    public function test_quiz_has_many_quiz_questions()
    {
        // Arrange
        $quiz = Quiz::factory()->create(['type' => 'inline']);
        QuizQuestion::factory()->count(3)->create(['quiz_id' => $quiz->id]);

        // Act
        $questions = $quiz->quizQuestions;

        // Assert
        $this->assertCount(3, $questions);
        $this->assertInstanceOf(QuizQuestion::class, $questions->first());
    }

    public function test_quiz_has_many_attempts()
    {
        // Arrange
        $quiz = Quiz::factory()->create();
        QuizAttempt::factory()->count(2)->create(['quiz_id' => $quiz->id]);

        // Act
        $attempts = $quiz->attempts;

        // Assert
        $this->assertCount(2, $attempts);
        $this->assertInstanceOf(QuizAttempt::class, $attempts->first());
    }

    public function test_active_scope_returns_only_active_quizzes()
    {
        // Arrange
        Quiz::factory()->create(['active' => true]);
        Quiz::factory()->create(['active' => false]);

        // Act
        $activeQuizzes = Quiz::active()->get();

        // Assert
        $this->assertCount(1, $activeQuizzes);
        $this->assertTrue($activeQuizzes->first()->active);
    }

    public function test_guest_accessible_scope_returns_guest_accessible_quizzes()
    {
        // Arrange
        Quiz::factory()->create(['allow_guest_access' => true]);
        Quiz::factory()->create(['allow_guest_access' => false]);

        // Act
        $guestQuizzes = Quiz::guestAccessible()->get();

        // Assert
        $this->assertCount(1, $guestQuizzes);
        $this->assertTrue($guestQuizzes->first()->allow_guest_access);
    }

    public function test_is_file_type_returns_true_for_file_quiz()
    {
        // Arrange
        $fileQuiz = Quiz::factory()->create(['type' => 'file']);
        $inlineQuiz = Quiz::factory()->create(['type' => 'inline']);

        // Act & Assert
        $this->assertTrue($fileQuiz->isFileType());
        $this->assertFalse($inlineQuiz->isFileType());
    }

    public function test_is_inline_type_returns_true_for_inline_quiz()
    {
        // Arrange
        $fileQuiz = Quiz::factory()->create(['type' => 'file']);
        $inlineQuiz = Quiz::factory()->create(['type' => 'inline']);

        // Act & Assert
        $this->assertTrue($inlineQuiz->isInlineType());
        $this->assertFalse($fileQuiz->isInlineType());
    }

    public function test_get_total_questions_attribute_for_inline_quiz()
    {
        // Arrange
        $quiz = Quiz::factory()->create(['type' => 'inline']);
        QuizQuestion::factory()->count(5)->create(['quiz_id' => $quiz->id]);

        // Act
        $totalQuestions = $quiz->total_questions;

        // Assert
        $this->assertEquals(5, $totalQuestions);
    }

    public function test_quiz_belongs_to_teacher()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $quiz = Quiz::factory()->create(['teacher_id' => $teacher->id]);

        // Act
        $quizTeacher = $quiz->teacher;

        // Assert
        $this->assertInstanceOf(User::class, $quizTeacher);
        $this->assertEquals($teacher->id, $quizTeacher->id);
    }

    public function test_quiz_can_be_created_with_all_attributes()
    {
        // Arrange
        $program = Program::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $quizData = [
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'title' => 'Test Quiz',
            'description' => 'Test Description',
            'type' => 'inline',
            'file_path' => null,
            'pass_score' => 80,
            'allow_guest_access' => true,
            'active' => true,
        ];

        // Act
        $quiz = Quiz::create($quizData);

        // Assert
        $this->assertDatabaseHas('quizzes', $quizData);
        $this->assertEquals('Test Quiz', $quiz->title);
        $this->assertEquals(80, $quiz->pass_score);
        $this->assertTrue($quiz->allow_guest_access);
        $this->assertTrue($quiz->active);
    }
}