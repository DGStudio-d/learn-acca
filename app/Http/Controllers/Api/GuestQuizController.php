<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Quiz;
use App\Models\Settings;
use App\Services\QuizAttemptService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GuestQuizController extends Controller
{
    public function __construct(
        private QuizAttemptService $quizAttemptService
    ) {}

    /**
     * Get available quizzes for guests.
     */
    public function index(): JsonResponse
    {
        // Check if guest quiz access is enabled
        if (!$this->isGuestQuizAccessEnabled()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GUEST_ACCESS_DENIED',
                    'message' => 'Guest access to quizzes is not allowed. Please log in to access this resource.',
                    'feature' => 'quizzes',
                ],
            ], 403);
        }

        $quizzes = Quiz::with(['program.language', 'program.level'])
            ->where('active', true)
            ->where('allow_guest_access', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($quiz) {
                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'type' => $quiz->type,
                    'pass_score' => $quiz->pass_score,
                    'time_limit' => $quiz->time_limit,
                    'program' => [
                        'id' => $quiz->program->id,
                        'language' => [
                            'id' => $quiz->program->language->id,
                            'code' => $quiz->program->language->code,
                            'name' => $quiz->program->language->name,
                        ],
                        'level' => [
                            'id' => $quiz->program->level->id,
                            'name' => $quiz->program->level->name,
                            'order' => $quiz->program->level->order,
                        ],
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'quizzes' => $quizzes,
                'total' => $quizzes->count(),
            ],
        ]);
    }

    /**
     * Get a specific quiz for guests.
     */
    public function show(Quiz $quiz): JsonResponse
    {
        // Check if guest quiz access is enabled
        if (!$this->isGuestQuizAccessEnabled()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GUEST_ACCESS_DENIED',
                    'message' => 'Guest access to quizzes is not allowed. Please log in to access this resource.',
                    'feature' => 'quizzes',
                ],
            ], 403);
        }

        // Check if this specific quiz allows guest access
        if (!$quiz->allow_guest_access) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'QUIZ_ACCESS_DENIED',
                    'message' => 'This quiz does not allow guest access.',
                ],
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
     * Submit a guest quiz attempt.
     */
    public function submitAttempt(Request $request, $quiz): JsonResponse
    {
        // Manually resolve the quiz if needed
        if (!$quiz instanceof Quiz) {
            $quiz = Quiz::findOrFail($quiz);
        }
        


        // Check if guest quiz access is enabled
        if (!$this->isGuestQuizAccessEnabled()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GUEST_ACCESS_DENIED',
                    'message' => 'Guest access to quizzes is not allowed. Please log in to access this resource.',
                    'feature' => 'quizzes',
                ],
            ], 403);
        }

        // Check if this specific quiz allows guest access
        if (!$quiz->allow_guest_access) {

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'QUIZ_ACCESS_DENIED',
                    'message' => 'This quiz does not allow guest access.',
                ],
            ], 403);
        }

        // Validate request data
        $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string'],
        ], [
            'answers.required' => 'Quiz answers are required.',
            'answers.array' => 'Answers must be provided as an array.',
            'answers.*.required' => 'All questions must be answered.',
            'answers.*.string' => 'Each answer must be a string.',
        ]);

        try {
            $attempt = $this->quizAttemptService->submitAttempt(
                $quiz,
                null, // No user for guest attempts
                $request->input('answers')
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
                    'total_questions' => $attempt->total_questions ?? $quiz->quizQuestions()->count(),
                    'correct_answers' => $attempt->correct_answers ?? round(($attempt->score / 100) * ($attempt->total_questions ?? $quiz->quizQuestions()->count())),
                ],
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
     * Check if guest quiz access is enabled globally.
     */
    private function isGuestQuizAccessEnabled(): bool
    {
        return Settings::isGuestAccessAllowed('quizzes');
    }
}