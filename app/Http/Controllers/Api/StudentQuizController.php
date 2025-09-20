<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitQuizAttemptRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizAttemptService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentQuizController extends Controller
{
    public function __construct(
        private QuizAttemptService $quizAttemptService
    ) {}

    /**
     * Get available quizzes for the authenticated student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get quizzes from programs the student has access to
        $quizzes = Quiz::with(['program.language', 'program.level'])
            ->whereHas('program.enrollments', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->whereNotNull('access_granted_at');
            })
            ->where('active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $quizzes,
        ]);
    }

    /**
     * Get a specific quiz for taking (student view).
     */
    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        $user = $request->user();

        // Check if student has access to this quiz
        $hasAccess = $quiz->program->enrollments()
            ->where('user_id', $user->id)
            ->whereNotNull('access_granted_at')
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this quiz.',
            ], 403);
        }

        // Load quiz with questions (for inline quizzes)
        $quiz->load([
            'program.language',
            'program.level',
            'quizQuestions' => function ($query) {
                $query->ordered()->select('id', 'quiz_id', 'question', 'choices', 'order');
            }
        ]);

        // Remove correct answers from the response
        if ($quiz->quizQuestions) {
            $quiz->quizQuestions->makeHidden(['correct_answer']);
        }

        return response()->json([
            'success' => true,
            'data' => $quiz,
        ]);
    }

    /**
     * Submit a quiz attempt.
     */
    public function submitAttempt(SubmitQuizAttemptRequest $request, $quizId): JsonResponse
    {
        try {
            $quiz = \App\Models\Quiz::findOrFail($quizId);
            $attempt = $this->quizAttemptService->submitAttempt(
                $quiz,
                $request->user(),
                $request->validated()['answers']
            );

            return response()->json([
                'success' => true,
                'message' => 'Quiz attempt submitted successfully.',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'score' => $attempt->score,
                    'percentage' => $attempt->percentage_score,
                    'passed' => $attempt->passed,
                    'submitted_at' => $attempt->submitted_at,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit quiz attempt.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get student's quiz attempts.
     */
    public function getMyAttempts(Request $request, ?Quiz $quiz = null): JsonResponse
    {
        try {
            $attempts = $this->quizAttemptService->getStudentAttempts(
                $request->user(),
                $quiz
            );

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
     * Get detailed results for student's own attempt.
     */
    public function getMyAttemptResults(Request $request, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        $user = $request->user();

        // Verify attempt belongs to this user
        if ($attempt->student_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only view your own quiz results.',
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
}