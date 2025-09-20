<?php

namespace Tests\Unit\Services;

use App\Models\Program;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuizServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuizService $quizService;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->quizService = new QuizService();
        Storage::fake('public');
        
        // Create a default teacher for tests
        $this->teacher = User::factory()->create(['role' => 'teacher']);
    }

    public function test_create_quiz_with_inline_type()
    {
        // Arrange
        $program = Program::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $data = [
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'title' => 'Test Quiz',
            'description' => 'Test Description',
            'type' => 'inline',
            'pass_score' => 80,
            'allow_guest_access' => true,
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
        $quiz = $this->quizService->createQuiz($data);

        // Assert
        $this->assertInstanceOf(Quiz::class, $quiz);
        $this->assertEquals('Test Quiz', $quiz->title);
        $this->assertEquals('inline', $quiz->type);
        $this->assertEquals(80, $quiz->pass_score);
        $this->assertTrue($quiz->allow_guest_access);
        $this->assertCount(1, $quiz->quizQuestions);
    }

    public function test_create_quiz_with_file_type()
    {
        // Arrange
        $program = Program::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $file = UploadedFile::fake()->create('quiz.pdf', 100, 'application/pdf');
        
        $data = [
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'title' => 'File Quiz',
            'type' => 'file',
            'pass_score' => 70
        ];

        // Act
        $quiz = $this->quizService->createQuiz($data, $file);

        // Assert
        $this->assertInstanceOf(Quiz::class, $quiz);
        $this->assertEquals('file', $quiz->type);
        $this->assertNotNull($quiz->file_path);
        Storage::disk('public')->assertExists($quiz->file_path);
    }

    public function test_create_quiz_rejects_invalid_file_type()
    {
        // Arrange
        $program = Program::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $file = UploadedFile::fake()->create('quiz.exe', 100, 'application/exe');
        
        $data = [
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'title' => 'File Quiz',
            'type' => 'file'
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->quizService->createQuiz($data, $file);
    }

    public function test_update_quiz_updates_basic_fields()
    {
        // Arrange
        $quiz = Quiz::factory()->create([
            'type' => 'inline',
            'title' => 'Original Title',
            'teacher_id' => $this->teacher->id
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'pass_score' => 85,
            'active' => false
        ];

        // Act
        $updatedQuiz = $this->quizService->updateQuiz($quiz, $updateData);

        // Assert
        $this->assertEquals('Updated Title', $updatedQuiz->title);
        $this->assertEquals(85, $updatedQuiz->pass_score);
        $this->assertFalse($updatedQuiz->active);
    }

    public function test_update_quiz_replaces_file_for_file_type()
    {
        // Arrange
        $quiz = Quiz::factory()->create([
            'type' => 'file',
            'file_path' => 'quizzes/old_file.pdf',
            'teacher_id' => $this->teacher->id
        ]);
        
        // Create the old file in storage
        Storage::disk('public')->put($quiz->file_path, 'old content');

        $newFile = UploadedFile::fake()->create('new_quiz_file.pdf', 150, 'application/pdf');
        $updateData = ['title' => 'Updated Quiz'];
        
        $originalFilePath = $quiz->file_path;

        // Act
        $updatedQuiz = $this->quizService->updateQuiz($quiz, $updateData, $newFile);

        // Assert
        $this->assertNotEquals($originalFilePath, $updatedQuiz->file_path);
        $this->assertStringContainsString('quizzes/', $updatedQuiz->file_path);
        Storage::disk('public')->assertExists($updatedQuiz->file_path);
        // Verify old file was deleted
        Storage::disk('public')->assertMissing($originalFilePath);
    }

    public function test_delete_quiz_removes_file_and_questions()
    {
        // Arrange
        $file = UploadedFile::fake()->create('quiz.pdf', 100, 'application/pdf');
        $quiz = Quiz::factory()->create([
            'type' => 'file',
            'file_path' => $file->store('quizzes', 'public'),
            'teacher_id' => $this->teacher->id
        ]);

        QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

        // Act
        $result = $this->quizService->deleteQuiz($quiz);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseMissing('quiz_questions', ['quiz_id' => $quiz->id]);
    }

    public function test_get_teacher_quizzes_filters_by_teacher_languages()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $program = Program::factory()->create();
        
        // Create teacher-language relationship
        $teacher->teacherLanguages()->attach($program->language_id);
        
        $quiz1 = Quiz::factory()->create(['program_id' => $program->id, 'teacher_id' => $teacher->id]);
        $quiz2 = Quiz::factory()->create(['teacher_id' => $teacher->id]); // Different program

        // Act
        $result = $this->quizService->getTeacherQuizzes($teacher);

        // Assert
        $this->assertCount(1, $result->items());
        $this->assertEquals($quiz1->id, $result->items()[0]->id);
    }

    public function test_can_teacher_manage_quiz_returns_true_for_assigned_language()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $program = Program::factory()->create();
        $quiz = Quiz::factory()->create(['program_id' => $program->id, 'teacher_id' => $teacher->id]);
        
        $teacher->teacherLanguages()->attach($program->language_id);

        // Act
        $result = $this->quizService->canTeacherManageQuiz($teacher, $quiz);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_teacher_manage_quiz_returns_false_for_unassigned_language()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $program = Program::factory()->create();
        $quiz = Quiz::factory()->create(['program_id' => $program->id, 'teacher_id' => $teacher->id]);

        // Act
        $result = $this->quizService->canTeacherManageQuiz($teacher, $quiz);

        // Assert
        $this->assertFalse($result);
    }

    public function test_add_question_creates_question_for_inline_quiz()
    {
        // Arrange
        $quiz = Quiz::factory()->create(['type' => 'inline', 'teacher_id' => $this->teacher->id]);
        $questionData = [
            'question' => 'What is the capital of France?',
            'choices' => ['London', 'Berlin', 'Paris', 'Madrid'],
            'correct_answer' => 'Paris'
        ];

        // Act
        $question = $this->quizService->addQuestion($quiz, $questionData);

        // Assert
        $this->assertInstanceOf(QuizQuestion::class, $question);
        $this->assertEquals('What is the capital of France?', $question->question);
        $this->assertEquals(['London', 'Berlin', 'Paris', 'Madrid'], $question->choices);
        $this->assertEquals('Paris', $question->correct_answer);
        $this->assertEquals(1, $question->order);
    }

    public function test_add_question_throws_exception_for_file_quiz()
    {
        // Arrange
        $quiz = Quiz::factory()->create(['type' => 'file', 'teacher_id' => $this->teacher->id]);
        $questionData = [
            'question' => 'Test question',
            'choices' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A'
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->quizService->addQuestion($quiz, $questionData);
    }

    public function test_update_question_modifies_existing_question()
    {
        // Arrange
        $question = QuizQuestion::factory()->create([
            'question' => 'Original question',
            'correct_answer' => 'A'
        ]);

        $updateData = [
            'question' => 'Updated question',
            'correct_answer' => 'B'
        ];

        // Act
        $updatedQuestion = $this->quizService->updateQuestion($question, $updateData);

        // Assert
        $this->assertEquals('Updated question', $updatedQuestion->question);
        $this->assertEquals('B', $updatedQuestion->correct_answer);
    }

    public function test_delete_question_removes_question()
    {
        // Arrange
        $question = QuizQuestion::factory()->create();

        // Act
        $result = $this->quizService->deleteQuestion($question);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('quiz_questions', ['id' => $question->id]);
    }

    public function test_reorder_questions_updates_order()
    {
        // Arrange
        $quiz = Quiz::factory()->create(['type' => 'inline', 'teacher_id' => $this->teacher->id]);
        $question1 = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'order' => 1]);
        $question2 = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'order' => 2]);

        $questionOrders = [
            $question1->id => 2,
            $question2->id => 1
        ];

        // Act
        $this->quizService->reorderQuestions($quiz, $questionOrders);

        // Assert
        $question1->refresh();
        $question2->refresh();
        
        $this->assertEquals(2, $question1->order);
        $this->assertEquals(1, $question2->order);
    }
}