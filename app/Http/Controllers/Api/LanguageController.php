<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use App\Services\LanguageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    public function __construct(
        private LanguageService $languageService
    ) {}

    /**
     * Display a listing of all languages.
     */
    public function index(): JsonResponse
    {
        try {
            $languages = Language::with(['levels', 'programs'])
                ->withCount(['programs', 'teacherLanguages'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'languages' => $languages->map(function ($language) {
                        return [
                            'id' => $language->id,
                            'code' => $language->code,
                            'name' => $language->name,
                            'active' => $language->active,
                            'levels_count' => $language->levels->count(),
                            'programs_count' => $language->programs_count,
                            'teachers_count' => $language->teacher_languages_count,
                            'created_at' => $language->created_at,
                            'updated_at' => $language->updated_at,
                        ];
                    }),
                    'total' => $languages->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve languages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created language with auto-generated levels and programs.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:languages,code',
            'name' => 'required|string|max:255',
            'active' => 'boolean',
            'levels' => 'array|min:1',
            'levels.*.name' => 'required|string|max:255',
            'levels.*.order' => 'required|integer|min:1',
        ]);

        try {
            $language = $this->languageService->createLanguageWithLevelsAndPrograms(
                $request->only(['code', 'name', 'active']),
                $request->input('levels', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Language created successfully with levels and programs',
                'data' => [
                    'language' => [
                        'id' => $language->id,
                        'code' => $language->code,
                        'name' => $language->name,
                        'active' => $language->active,
                        'levels' => $language->levels->map(function ($level) {
                            return [
                                'id' => $level->id,
                                'name' => $level->name,
                                'order' => $level->order,
                                'programs' => $level->programs->map(function ($program) {
                                    return [
                                        'id' => $program->id,
                                        'title' => $program->title,
                                        'description' => $program->description,
                                        'active' => $program->active,
                                    ];
                                }),
                            ];
                        }),
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create language',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified language with its levels and programs.
     */
    public function show(Language $language): JsonResponse
    {
        try {
            $language->load(['levels.programs', 'teacherLanguages.teacher']);

            return response()->json([
                'success' => true,
                'data' => [
                    'language' => [
                        'id' => $language->id,
                        'code' => $language->code,
                        'name' => $language->name,
                        'active' => $language->active,
                        'created_at' => $language->created_at,
                        'updated_at' => $language->updated_at,
                        'levels' => $language->levels->map(function ($level) {
                            return [
                                'id' => $level->id,
                                'name' => $level->name,
                                'order' => $level->order,
                                'programs' => $level->programs->map(function ($program) {
                                    return [
                                        'id' => $program->id,
                                        'title' => $program->title,
                                        'description' => $program->description,
                                        'active' => $program->active,
                                        'enrollment_count' => $program->enrollments()->count(),
                                        'approved_enrollment_count' => $program->enrollments()->whereNotNull('access_granted_at')->count(),
                                    ];
                                }),
                            ];
                        }),
                        'teachers' => $language->teacherLanguages->map(function ($teacherLanguage) {
                            return [
                                'id' => $teacherLanguage->teacher->id,
                                'name' => $teacherLanguage->teacher->name,
                                'email' => $teacherLanguage->teacher->email,
                                'image_path' => $teacherLanguage->teacher->image_path,
                                'assigned_at' => $teacherLanguage->assigned_at,
                            ];
                        }),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve language',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified language.
     */
    public function update(Request $request, Language $language): JsonResponse
    {
        $request->validate([
            'code' => [
                'sometimes',
                'string',
                'max:10',
                Rule::unique('languages', 'code')->ignore($language->id),
            ],
            'name' => 'sometimes|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        try {
            $language->update($request->only(['code', 'name', 'active']));

            return response()->json([
                'success' => true,
                'message' => 'Language updated successfully',
                'data' => [
                    'language' => [
                        'id' => $language->id,
                        'code' => $language->code,
                        'name' => $language->name,
                        'active' => $language->active,
                        'updated_at' => $language->updated_at,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update language',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified language.
     */
    public function destroy(Language $language): JsonResponse
    {
        try {
            // Check if language has any enrollments
            $hasEnrollments = $language->programs()
                ->whereHas('enrollments')
                ->exists();

            if ($hasEnrollments) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete language with existing enrollments',
                ], 422);
            }

            $languageName = $language->name;
            $language->delete();

            return response()->json([
                'success' => true,
                'message' => "Language '{$languageName}' deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete language',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get program statistics for a specific language.
     */
    public function getProgramStatistics(Language $language): JsonResponse
    {
        try {
            $programs = $language->programs()
                ->with(['level', 'enrollments.user'])
                ->get();

            $statistics = $programs->map(function ($program) {
                $enrollments = $program->enrollments;
                $approvedEnrollments = $enrollments->whereNotNull('access_granted_at');

                return [
                    'program' => [
                        'id' => $program->id,
                        'title' => $program->title,
                        'description' => $program->description,
                        'active' => $program->active,
                        'level' => [
                            'id' => $program->level->id,
                            'name' => $program->level->name,
                            'order' => $program->level->order,
                        ],
                    ],
                    'statistics' => [
                        'total_enrollments' => $enrollments->count(),
                        'approved_enrollments' => $approvedEnrollments->count(),
                        'pending_enrollments' => $enrollments->whereNull('access_granted_at')->count(),
                        'enrollment_rate' => $enrollments->count() > 0 
                            ? round(($approvedEnrollments->count() / $enrollments->count()) * 100, 2) 
                            : 0,
                    ],
                ];
            });

            $totalStats = [
                'total_programs' => $programs->count(),
                'active_programs' => $programs->where('active', true)->count(),
                'total_enrollments' => $programs->sum(fn($p) => $p->enrollments->count()),
                'total_approved_enrollments' => $programs->sum(fn($p) => $p->enrollments->whereNotNull('access_granted_at')->count()),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'language' => [
                        'id' => $language->id,
                        'name' => $language->name,
                        'code' => $language->code,
                    ],
                    'total_statistics' => $totalStats,
                    'program_statistics' => $statistics,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve program statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all programs for a specific language with enrollment counts.
     */
    public function getPrograms(Language $language): JsonResponse
    {
        try {
            $programs = $language->programs()
                ->with(['level', 'enrollments.user'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'language' => [
                        'id' => $language->id,
                        'name' => $language->name,
                        'code' => $language->code,
                    ],
                    'programs' => $programs->map(function ($program) {
                        return [
                            'id' => $program->id,
                            'title' => $program->title,
                            'description' => $program->description,
                            'active' => $program->active,
                            'level' => [
                                'id' => $program->level->id,
                                'name' => $program->level->name,
                                'order' => $program->level->order,
                            ],
                            'enrollment_count' => $program->enrollments->count(),
                            'approved_enrollment_count' => $program->enrollments->whereNotNull('access_granted_at')->count(),
                            'pending_enrollment_count' => $program->enrollments->whereNull('access_granted_at')->count(),
                            'created_at' => $program->created_at,
                            'updated_at' => $program->updated_at,
                        ];
                    }),
                    'total' => $programs->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve programs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}