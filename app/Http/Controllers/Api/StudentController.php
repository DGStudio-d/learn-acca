<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EnrollmentService;
use App\Services\AccessControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService,
        private AccessControlService $accessControlService
    ) {}

    /**
     * Get student's enrollments and access status.
     */
    public function getMyEnrollments(Request $request): JsonResponse
    {
        try {
            $student = $request->user();
            $enrollments = $this->enrollmentService->getStudentEnrollments($student);

            return response()->json([
                'success' => true,
                'data' => [
                    'enrollments' => $enrollments->map(function ($enrollment) {
                        return [
                            'id' => $enrollment->id,
                            'program' => [
                                'id' => $enrollment->program->id,
                                'title' => $enrollment->program->title,
                                'description' => $enrollment->program->description,
                                'language' => $enrollment->program->language->name,
                                'level' => $enrollment->program->level->name,
                            ],
                            'assigned_at' => $enrollment->assigned_at,
                            'access_granted_at' => $enrollment->access_granted_at,
                            'has_access' => $enrollment->access_granted_at !== null,
                            'status' => $enrollment->access_granted_at ? 'approved' : 'pending',
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve enrollments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get student's approved programs only.
     */
    public function getMyApprovedPrograms(Request $request): JsonResponse
    {
        try {
            $student = $request->user();
            $approvedPrograms = $student->approvedPrograms()->with(['language', 'level'])->get();

            return response()->json([
                'success' => true,
                'data' => $approvedPrograms->map(function ($program) {
                    return [
                        'id' => $program->id,
                        'title' => $program->title,
                        'description' => $program->description,
                        'language' => $program->language->name,
                        'level' => $program->level->name,
                        'access_granted_at' => $program->pivot->access_granted_at,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve approved programs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}