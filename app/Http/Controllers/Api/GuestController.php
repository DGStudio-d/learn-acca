<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Quiz;
use App\Models\User;
use App\Services\QuizAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GuestController extends Controller
{
    /**
     * Get all available languages for guests.
     */
    public function getLanguages(): JsonResponse
    {
        try {
            $languages = Language::where('active', true)
                ->with(['levels' => function ($query) {
                    $query->orderBy('order');
                }])
                ->orderBy('name')
                ->get()
                ->map(function ($language) {
                    return [
                        'id' => $language->id,
                        'code' => $language->code,
                        'name' => $language->name,
                        'active' => $language->active,
                        'levels' => $language->levels->map(function ($level) {
                            return [
                                'id' => $level->id,
                                'name' => $level->name,
                                'order' => $level->order,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'languages' => $languages,
                    'total' => $languages->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'LANGUAGES_RETRIEVAL_FAILED',
                    'message' => 'Failed to retrieve languages',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Get all teachers for guests.
     */
    public function getTeachers(): JsonResponse
    {
        try {
            $teachers = User::role('teacher')
                ->with(['teacherLanguages'])
                ->get()
                ->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                        'profile_image' => $teacher->profile_image,
                        'languages' => $teacher->teacherLanguages->map(function ($language) {
                            return [
                                'id' => $language->id,
                                'code' => $language->code,
                                'name' => $language->name,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'teachers' => $teachers,
                    'total' => $teachers->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TEACHERS_RETRIEVAL_FAILED',
                    'message' => 'Failed to retrieve teachers',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Get teachers by language for guests.
     */
    public function getTeachersByLanguage($languageId): JsonResponse
    {
        try {
            $language = Language::findOrFail($languageId);
            
            $teachers = User::role('teacher')
                ->whereHas('teacherLanguages', function ($query) use ($language) {
                    $query->where('language_id', $language->id);
                })
                ->get()
                ->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                        'profile_image' => $teacher->profile_image,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'language' => [
                        'id' => $language->id,
                        'code' => $language->code,
                        'name' => $language->name,
                    ],
                    'teachers' => $teachers,
                    'total' => $teachers->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TEACHERS_BY_LANGUAGE_RETRIEVAL_FAILED',
                    'message' => 'Failed to retrieve teachers for language',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Get all available quizzes for guests.
     */
    public function getQuizzes(): JsonResponse
    {
        try {
            $quizzes = Quiz::active()
                ->guestAccessible()
                ->with(['program.language', 'program.level'])
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
                        'questions_count' => $quiz->quizQuestions->count(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'quizzes' => $quizzes,
                    'total' => $quizzes->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'QUIZZES_RETRIEVAL_FAILED',
                    'message' => 'Failed to retrieve quizzes',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Get a specific quiz for guests.
     */
    public function getQuiz($quizId): JsonResponse
    {
        try {
            $quiz = Quiz::active()
                ->guestAccessible()
                ->with(['program.language', 'program.level', 'quizQuestions'])
                ->findOrFail($quizId);

            $quizData = [
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
                'questions' => $quiz->quizQuestions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question' => $question->question,
                        'type' => $question->type,
                        'choices' => $question->choices,
                        'order' => $question->order,
                        // Don't include correct_answer for guests
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'quiz' => $quizData,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'QUIZ_RETRIEVAL_FAILED',
                    'message' => 'Failed to retrieve quiz',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Submit a quiz attempt as a guest.
     */
    public function submitQuizAttempt(Request $request, $quizId): JsonResponse
    {
        try {
            $quiz = Quiz::active()
                ->guestAccessible()
                ->findOrFail($quizId);

            $request->validate([
                'answers' => 'required|array',
                'answers.*' => 'required|string',
            ]);

            $quizAttemptService = app(QuizAttemptService::class);
            $attempt = $quizAttemptService->submitAttempt(
                $quiz,
                null, // No user for guest attempts
                $request->input('answers')
            );

            return response()->json([
                'success' => true,
                'message' => 'Quiz attempt submitted successfully.',
                'data' => [
                    'score' => $attempt->score,
                    'passed' => $attempt->passed,
                    'total_questions' => $attempt->total_questions,
                    'correct_answers' => $attempt->correct_answers,
                    'attempt' => [
                        'id' => $attempt->id,
                        'score' => $attempt->score,
                        'passed' => $attempt->passed,
                        'submitted_at' => $attempt->submitted_at,
                        'quiz' => [
                            'id' => $quiz->id,
                            'title' => $quiz->title,
                            'pass_score' => $quiz->pass_score,
                        ],
                    ],
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'QUIZ_ATTEMPT_FAILED',
                    'message' => 'Failed to submit quiz attempt',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }
}