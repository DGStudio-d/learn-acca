<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Program;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\EnrollmentService;
use App\Services\TeacherLanguageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private AccessControlService $accessControlService,
        private EnrollmentService $enrollmentService,
        private TeacherLanguageService $teacherLanguageService
    ) {}

    /**
     * Get all pending enrollments.
     */
    public function getPendingEnrollments(): JsonResponse
    {
        try {
            $pendingEnrollments = $this->enrollmentService->getPendingEnrollments();

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_enrollments' => $pendingEnrollments->map(function ($enrollment) {
                        return [
                            'id' => $enrollment->id,
                            'student' => [
                                'id' => $enrollment->user->id,
                                'name' => $enrollment->user->name,
                                'email' => $enrollment->user->email,
                                'phone' => $enrollment->user->phone,
                            ],
                            'program' => [
                                'id' => $enrollment->program->id,
                                'title' => $enrollment->program->title,
                                'language' => $enrollment->program->language->name,
                                'level' => $enrollment->program->level->name,
                            ],
                            'assigned_at' => $enrollment->assigned_at,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending enrollments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Grant access to a student for a specific program.
     */
    public function grantProgramAccess(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|integer|exists:users,id',
            'program_id' => 'required|integer|exists:programs,id',
        ]);

        try {
            $student = User::findOrFail($request->student_id);
            $program = Program::findOrFail($request->program_id);
            $admin = $request->user();

            $success = $this->accessControlService->grantProgramAccess($student, $program, $admin);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Access granted successfully',
                    'data' => [
                        'student_name' => $student->name,
                        'program_title' => $program->title,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to grant access. Enrollment not found.',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to grant access',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk grant access to multiple students for a program.
     */
    public function bulkGrantProgramAccess(Request $request): JsonResponse
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'integer|exists:users,id',
            'program_id' => 'required|integer|exists:programs,id',
        ]);

        try {
            $program = Program::findOrFail($request->program_id);
            $admin = $request->user();

            $approvedCount = $this->accessControlService->bulkGrantProgramAccess(
                collect($request->student_ids),
                $program,
                $admin
            );

            return response()->json([
                'success' => true,
                'message' => "Access granted to {$approvedCount} students",
                'data' => [
                    'approved_count' => $approvedCount,
                    'program_title' => $program->title,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk grant access',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get students for a specific program.
     */
    public function getProgramStudents(Program $program): JsonResponse
    {
        try {
            $enrollments = $this->enrollmentService->getProgramEnrollments($program);

            return response()->json([
                'success' => true,
                'data' => [
                    'program' => [
                        'id' => $program->id,
                        'title' => $program->title,
                        'language' => $program->language->name,
                        'level' => $program->level->name,
                    ],
                    'students' => $enrollments->map(function ($enrollment) {
                        return [
                            'enrollment_id' => $enrollment->id,
                            'student' => [
                                'id' => $enrollment->user->id,
                                'name' => $enrollment->user->name,
                                'email' => $enrollment->user->email,
                                'phone' => $enrollment->user->phone,
                            ],
                            'assigned_at' => $enrollment->assigned_at,
                            'access_granted_at' => $enrollment->access_granted_at,
                            'has_access' => $enrollment->access_granted_at !== null,
                            'approved_by' => $enrollment->approved_by,
                        ];
                    }),
                    'statistics' => [
                        'total_students' => $enrollments->count(),
                        'approved_students' => $enrollments->where('access_granted_at', '!=', null)->count(),
                        'pending_students' => $enrollments->where('access_granted_at', null)->count(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve program students',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revoke access from a student for a specific program.
     */
    public function revokeProgramAccess(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|integer|exists:users,id',
            'program_id' => 'required|integer|exists:programs,id',
        ]);

        try {
            $student = User::findOrFail($request->student_id);
            $program = Program::findOrFail($request->program_id);

            $success = $this->accessControlService->revokeProgramAccess($student, $program);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Access revoked successfully',
                    'data' => [
                        'student_name' => $student->name,
                        'program_title' => $program->title,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to revoke access. Enrollment not found.',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke access',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign a teacher to a language.
     */
    public function assignTeacherToLanguage(Request $request): JsonResponse
    {
        $request->validate([
            'teacher_id' => 'required|integer|exists:users,id',
            'language_id' => 'required|integer|exists:languages,id',
        ]);

        try {
            $teacher = User::findOrFail($request->teacher_id);
            $language = Language::findOrFail($request->language_id);
            $admin = $request->user();

            $assignment = $this->teacherLanguageService->assignTeacherToLanguage($teacher, $language, $admin);

            return response()->json([
                'success' => true,
                'message' => 'Teacher assigned to language successfully',
                'data' => [
                    'assignment' => [
                        'id' => $assignment->id,
                        'teacher' => [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                            'email' => $teacher->email,
                        ],
                        'language' => [
                            'id' => $language->id,
                            'name' => $language->name,
                            'code' => $language->code,
                        ],
                        'assigned_at' => $assignment->assigned_at,
                        'assigned_by' => $admin->name,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign teacher to language',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign a teacher to multiple languages.
     */
    public function assignTeacherToMultipleLanguages(Request $request): JsonResponse
    {
        $request->validate([
            'teacher_id' => 'required|integer|exists:users,id',
            'language_ids' => 'required|array',
            'language_ids.*' => 'integer|exists:languages,id',
        ]);

        try {
            $teacher = User::findOrFail($request->teacher_id);
            $admin = $request->user();

            $assignments = $this->teacherLanguageService->assignTeacherToMultipleLanguages(
                $teacher,
                $request->language_ids,
                $admin
            );

            return response()->json([
                'success' => true,
                'message' => "Teacher assigned to {$assignments->count()} languages successfully",
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                    ],
                    'assignments_count' => $assignments->count(),
                    'assignments' => $assignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'language' => [
                                'id' => $assignment->language->id,
                                'name' => $assignment->language->name,
                                'code' => $assignment->language->code,
                            ],
                            'assigned_at' => $assignment->assigned_at,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign teacher to languages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a teacher from a language.
     */
    public function removeTeacherFromLanguage(Request $request): JsonResponse
    {
        $request->validate([
            'teacher_id' => 'required|integer|exists:users,id',
            'language_id' => 'required|integer|exists:languages,id',
        ]);

        try {
            $teacher = User::findOrFail($request->teacher_id);
            $language = Language::findOrFail($request->language_id);
            $admin = $request->user();

            $success = $this->teacherLanguageService->removeTeacherFromLanguage($teacher, $language, $admin);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Teacher removed from language successfully',
                    'data' => [
                        'teacher' => [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                        ],
                        'language' => [
                            'id' => $language->id,
                            'name' => $language->name,
                            'code' => $language->code,
                        ],
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove teacher from language',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove teacher from language',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get teachers assigned to a specific language.
     */
    public function getTeachersByLanguage($languageId): JsonResponse
    {
        try {
            $language = Language::findOrFail($languageId);
            $teachers = $this->teacherLanguageService->getTeachersByLanguage($language);

            return response()->json([
                'success' => true,
                'data' => [
                    'language' => [
                        'id' => $language->id,
                        'name' => $language->name,
                        'code' => $language->code,
                    ],
                    'teachers' => $teachers->map(function ($teacher) {
                        $assignment = $teacher->teacherLanguages->first();
                        return [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                            'email' => $teacher->email,
                            'phone' => $teacher->phone,
                            'image_path' => $teacher->image_path,
                            'assigned_at' => $assignment->assigned_at,
                            'assigned_by' => $assignment->assignedBy ? $assignment->assignedBy->name : 'Unknown',
                        ];
                    }),
                    'total' => $teachers->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve teachers for language',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all teacher-language assignments.
     */
    public function getAllTeacherLanguageAssignments(): JsonResponse
    {
        try {
            $assignments = $this->teacherLanguageService->getAllAssignments();

            return response()->json([
                'success' => true,
                'data' => [
                    'assignments' => $assignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'teacher' => [
                                'id' => $assignment->teacher->id,
                                'name' => $assignment->teacher->name,
                                'email' => $assignment->teacher->email,
                                'phone' => $assignment->teacher->phone,
                                'image_path' => $assignment->teacher->image_path,
                            ],
                            'language' => [
                                'id' => $assignment->language->id,
                                'name' => $assignment->language->name,
                                'code' => $assignment->language->code,
                                'active' => $assignment->language->active,
                            ],
                            'assigned_at' => $assignment->assigned_at,
                            'assigned_by' => $assignment->assignedBy->name,
                        ];
                    }),
                    'total' => $assignments->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve teacher-language assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available languages for a teacher (not yet assigned).
     */
    public function getAvailableLanguagesForTeacher(User $teacher): JsonResponse
    {
        try {
            $availableLanguages = $this->teacherLanguageService->getAvailableLanguagesForTeacher($teacher);

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                    ],
                    'available_languages' => $availableLanguages,
                    'total' => $availableLanguages->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available languages for teacher',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get enrollment statistics across all programs.
     */
    public function getEnrollmentStatistics(): JsonResponse
    {
        try {
            $statistics = $this->enrollmentService->getSystemEnrollmentStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve enrollment statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all pending enrollments across all programs with filtering.
     */
    public function getAllPendingEnrollments(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['language_id', 'level_id', 'program_id', 'days_pending']);
            $pendingEnrollments = $this->enrollmentService->getAllPendingEnrollments($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_enrollments' => $pendingEnrollments->map(function ($enrollment) {
                        return [
                            'id' => $enrollment->id,
                            'student' => [
                                'id' => $enrollment->user->id,
                                'name' => $enrollment->user->name,
                                'email' => $enrollment->user->email,
                                'phone' => $enrollment->user->phone,
                            ],
                            'program' => [
                                'id' => $enrollment->program->id,
                                'title' => $enrollment->program->title,
                                'language' => $enrollment->program->language->name,
                                'level' => $enrollment->program->level->name,
                            ],
                            'assigned_at' => $enrollment->assigned_at,
                            'days_pending' => $enrollment->assigned_at->diffInDays(now()),
                        ];
                    }),
                    'total' => $pendingEnrollments->count(),
                    'filters_applied' => $filters,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve all pending enrollments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk approve enrollments by enrollment IDs.
     */
    public function bulkApproveEnrollments(Request $request): JsonResponse
    {
        $request->validate([
            'enrollment_ids' => 'required|array',
            'enrollment_ids.*' => 'integer|exists:enrollments,id',
        ]);

        try {
            $admin = $request->user();
            $approvedCount = $this->accessControlService->bulkApproveEnrollmentsByIds(
                collect($request->enrollment_ids),
                $admin
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully approved {$approvedCount} enrollments",
                'data' => [
                    'approved_count' => $approvedCount,
                    'total_requested' => count($request->enrollment_ids),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk approve enrollments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all users for admin management
     */
    public function getUsers(): JsonResponse
    {
        try {
            $users = User::with('roles')->paginate(20);
            
            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new user
     */
    public function createUser(Request $request): JsonResponse
    {
        try {
            $user = User::create($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific user
     */
    public function getUser(User $user): JsonResponse
    {
        try {
            $user->load('roles', 'permissions');
            
            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a user
     */
    public function updateUser(Request $request, User $user): JsonResponse
    {
        try {
            $user->update($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a user
     */
    public function deleteUser(User $user): JsonResponse
    {
        try {
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}