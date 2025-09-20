<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Http\Requests\CreateQuizQuestionRequest;
use App\Http\Requests\UpdateQuizQuestionRequest;
use App\Http\Requests\SubmitQuizAttemptRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use App\Services\QuizAttemptService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class QuizController extends Controller
{
    public function __construct(
        private QuizService $quizService,
        private QuizAttemptService $quizAttemptService
    ) {}

    /**
     * Get quizzes for the authenticated teacher.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['program_id', 'type', 'active', 'search', 'per_page']);
        
        $quizzes = $this->quizService->getTeacherQuizzes(
            $request->user(),
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $quizzes,
        ]);
    }

    /**
     * Create a new quiz.
     */
    public function store(CreateQuizRequest $request): JsonResponse
    {
        try {
            $quiz = $this->quizService->createQuiz(
                $request->validated(),
                $request->file('file'),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Quiz created successfully.',
                'data' => $quiz,
            ], 201);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 403 ? 403 : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Get a specific quiz with questions.
     */
    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        // Check if teacher can access this quiz
        if (!$this->quizService->canTeacherManageQuiz($request->user(), $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this quiz.',
            ], 403);
        }

        $quizWithQuestions = $this->quizService->getQuizWithQuestions($quiz);

        return response()->json([
            'success' => true,
            'data' => $quizWithQuestions,
        ]);
    }

    /**
     * Update a quiz.
     */
    public function update(UpdateQuizRequest $request, $quizId): JsonResponse
    {
        try {
            // Manually resolve the quiz (temporary fix for route model binding issue)
            $quiz = Quiz::with('program.language')->findOrFail($quizId);
            
            // Check if teacher can manage this quiz
            $canManage = $this->quizService->canTeacherManageQuiz($request->user(), $quiz);
            
            if (!$canManage) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this quiz.',
                ], 403);
            }
            
            $updatedQuiz = $this->quizService->updateQuiz(
                $quiz,
                $request->validated(),
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'message' => 'Quiz updated successfully.',
                'data' => $updatedQuiz,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update quiz.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a quiz.
     */
    public function destroy(Request $request, Quiz $quiz): JsonResponse
    {
        // Handle route model binding issue - if quiz is not loaded, load it manually
        if (!$quiz->exists) {
            $quizId = $request->route('quiz');
            $quiz = Quiz::find($quizId);
            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found.',
                ], 404);
            }
        }
        
        // Load the necessary relationships for permission checking
        $quiz->load('program.language');
        
        // Check if teacher can manage this quiz
        $canManage = $this->quizService->canTeacherManageQuiz($request->user(), $quiz);
        
        if (!$canManage) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this quiz.',
            ], 403);
        }

        try {
            $this->quizService->deleteQuiz($quiz);

            return response()->json([
                'success' => true,
                'message' => 'Quiz deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete quiz.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download quiz file.
     */
    public function downloadFile(Request $request, Quiz $quiz): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        // Check if teacher can access this quiz
        if (!$this->quizService->canTeacherManageQuiz($request->user(), $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this quiz file.',
            ], 403);
        }

        if (!$quiz->file_path || !Storage::disk('public')->exists($quiz->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz file not found.',
            ], 404);
        }

        return Storage::disk('public')->download($quiz->file_path, $quiz->title . '.' . pathinfo($quiz->file_path, PATHINFO_EXTENSION));
    }

    /**
     * Add a question to an inline quiz.
     */
    public function addQuestion(CreateQuizQuestionRequest $request, Quiz $quiz): JsonResponse
    {
        // Check if teacher can manage this quiz
        if (!$this->quizService->canTeacherManageQuiz($request->user(), $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add questions to this quiz.',
            ], 403);
        }

        try {
            $question = $this->quizService->addQuestion($quiz, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Question added successfully.',
                'data' => $question,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add question.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a quiz question.
     */
    public function updateQuestion(UpdateQuizQuestionRequest $request, Quiz $quiz, QuizQuestion $question): JsonResponse
    {
        // Check if teacher can manage this quiz
        if (!$this->quizService->canTeacherManageQuiz($request->user(), $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update questions in this quiz.',
            ], 403);
        }

        // Verify question belongs to this quiz
        if ($question->quiz_id !== $quiz->id) {
            return response()->json([
                'success' => false,
                'message' => 'Question does not belong to this quiz.',
            ], 400);
        }

        try {
            $updatedQuestion = $this->quizService->updateQuestion($question, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully.',
                'data' => $updatedQuestion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update question.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a quiz question.
     */
    public function deleteQuestion(Request $request, Quiz $quiz, QuizQuestion $question): JsonResponse
    {
        // Check if teacher can manage this quiz
        if (!$this->quizService->canTeacherManageQuiz($request->user(), $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete questions from this quiz.',
            ], 403);
        }

        // Verify question belongs to this quiz
        if ($question->quiz_id !== $quiz->id) {
            return response()->json([
                'success' => false,
                'message' => 'Question does not belong to this quiz.',
            ], 400);
        }

        try {
            $this->quizService->deleteQuestion($question);

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder quiz questions.
     */
    public function reorderQuestions(Request $request, Quiz $quiz): JsonResponse
    {
        // Check if teacher can manage this quiz
        if (!$this->quizService->canTeacherManageQuiz($request->user(), $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to reorder questions in this quiz.',
            ], 403);
        }

        $request->validate([
            'questions' => ['required', 'array'],
            'questions.*' => ['required', 'integer', 'exists:quiz_questions,id'],
        ]);

        try {
            $questionOrders = [];
            foreach ($request->input('questions') as $index => $questionId) {
                $questionOrders[$questionId] = $index + 1;
            }

            $this->quizService->reorderQuestions($quiz, $questionOrders);

            return response()->json([
                'success' => true,
                'message' => 'Questions reordered successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder questions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit a quiz attempt.
     */
    public function submitAttempt(SubmitQuizAttemptRequest $request, Quiz $quiz): JsonResponse
    {
        try {
            $attempt = $this->quizAttemptService->submitAttempt(
                $quiz,
                $request->user(),
                $request->validated()['answers']
            );

            return response()->json([
                'success' => true,
                'message' => 'Quiz attempt submitted successfully.',
                'data' => $attempt,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit quiz attempt.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get quiz statistics (for teachers/admins).
     */
    public function getStatistics(Request $request, Quiz $quiz): JsonResponse
    {
        // Check if teacher can access this quiz
        if (!$this->quizService->canTeacherManageQuiz($request->user(), $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view statistics for this quiz.',
            ], 403);
        }

        try {
            $statistics = $this->quizAttemptService->getQuizStatistics($quiz);

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quiz statistics.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get quiz attempts (for teachers/admins).
     */
    public function getAttempts(Request $request, Quiz $quiz): JsonResponse
    {
        // Check if teacher can access this quiz
        if (!$this->quizService->canTeacherManageQuiz($request->user(), $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view attempts for this quiz.',
            ], 403);
        }

        try {
            $filters = $request->only(['passed', 'guest_only', 'student_only', 'student_id', 'date_from', 'date_to', 'per_page']);
            $attempts = $this->quizAttemptService->getQuizAttempts($quiz, $filters);

            return response()->json([
                'success' => true,
                'data' => $attempts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quiz attempts.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed results for a specific attempt.
     */
    public function getAttemptResults(Request $request, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        // Check if teacher can access this quiz
        if (!$this->quizService->canTeacherManageQuiz($request->user(), $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view results for this quiz.',
            ], 403);
        }

        // Verify attempt belongs to this quiz
        if ($attempt->quiz_id !== $quiz->id) {
            return response()->json([
                'success' => false,
                'message' => 'Attempt does not belong to this quiz.',
            ], 400);
        }

        try {
            $results = $this->quizAttemptService->getAttemptResults($attempt);

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attempt results.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Get all quizzes
     */
    public function adminIndex(Request $request): JsonResponse
    {
        try {
            $quizzes = Quiz::with(['teacher', 'program'])
                ->when($request->search, function ($query, $search) {
                    $query->where('title', 'like', "%{$search}%");
                })
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $quizzes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quizzes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Show a specific quiz
     */
    public function adminShow(Quiz $quiz): JsonResponse
    {
        try {
            $quiz->load(['teacher', 'program', 'questions']);

            return response()->json([
                'success' => true,
                'data' => $quiz,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quiz',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Update a quiz
     */
    public function adminUpdate(UpdateQuizRequest $request, $quizId): JsonResponse
    {
        try {
            $quiz = Quiz::findOrFail($quizId);
            $quiz = $this->quizService->updateQuiz($quiz, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $quiz,
                'message' => 'Quiz updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update quiz',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Delete a quiz
     */
    public function adminDestroy($quizId): JsonResponse
    {
        try {
            $quiz = Quiz::findOrFail($quizId);
            $this->quizService->deleteQuiz($quiz);

            return response()->json([
                'success' => true,
                'message' => 'Quiz deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete quiz',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}