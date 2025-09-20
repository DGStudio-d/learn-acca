<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\ProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function __construct(
        private ProgramService $programService
    ) {}

    /**
     * Display a listing of all programs.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['language_id', 'level_id', 'active']);
            $programs = $this->programService->getAllProgramsWithStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'programs' => $programs,
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

    /**
     * Display the specified program with detailed information.
     */
    public function show(Program $program): JsonResponse
    {
        try {
            $programDetails = $this->programService->getProgramDetails($program);

            return response()->json([
                'success' => true,
                'data' => $programDetails,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve program details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified program.
     */
    public function update(Request $request, Program $program): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'active' => 'sometimes|boolean',
        ]);

        try {
            $updatedProgram = $this->programService->updateProgram($program, $request->only(['title', 'description', 'active']));

            return response()->json([
                'success' => true,
                'message' => 'Program updated successfully',
                'data' => [
                    'program' => [
                        'id' => $updatedProgram->id,
                        'title' => $updatedProgram->title,
                        'description' => $updatedProgram->description,
                        'active' => $updatedProgram->active,
                        'updated_at' => $updatedProgram->updated_at,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update program',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all students enrolled in a specific program.
     */
    public function getStudents(Program $program): JsonResponse
    {
        try {
            $students = $this->programService->getProgramStudents($program);

            return response()->json([
                'success' => true,
                'data' => [
                    'program' => [
                        'id' => $program->id,
                        'title' => $program->title,
                        'language' => $program->language->name,
                        'level' => $program->level->name,
                    ],
                    'students' => $students,
                    'statistics' => [
                        'total_students' => $students->count(),
                        'approved_students' => $students->where('has_access', true)->count(),
                        'pending_students' => $students->where('has_access', false)->count(),
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
     * Get program statistics including enrollment data.
     */
    public function getStatistics(Program $program): JsonResponse
    {
        try {
            $statistics = $this->programService->getProgramStatistics($program);

            return response()->json([
                'success' => true,
                'data' => $statistics,
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
     * Get pending enrollments for a specific program.
     */
    public function getPendingEnrollments(Program $program): JsonResponse
    {
        try {
            $pendingEnrollments = $this->programService->getProgramPendingEnrollments($program);

            return response()->json([
                'success' => true,
                'data' => [
                    'program' => [
                        'id' => $program->id,
                        'title' => $program->title,
                        'language' => $program->language->name,
                        'level' => $program->level->name,
                    ],
                    'pending_enrollments' => $pendingEnrollments,
                    'total' => $pendingEnrollments->count(),
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
     * Get approved enrollments for a specific program.
     */
    public function getApprovedEnrollments(Program $program): JsonResponse
    {
        try {
            $approvedEnrollments = $this->programService->getProgramApprovedEnrollments($program);

            return response()->json([
                'success' => true,
                'data' => [
                    'program' => [
                        'id' => $program->id,
                        'title' => $program->title,
                        'language' => $program->language->name,
                        'level' => $program->level->name,
                    ],
                    'approved_enrollments' => $approvedEnrollments,
                    'total' => $approvedEnrollments->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve approved enrollments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified program.
     */
    public function destroy(Program $program): JsonResponse
    {
        try {
            $canDelete = $this->programService->canDeleteProgram($program);

            if (!$canDelete['can_delete']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete program',
                    'reasons' => $canDelete['reasons'],
                ], 422);
            }

            $programTitle = $program->title;
            $program->delete();

            return response()->json([
                'success' => true,
                'message' => "Program '{$programTitle}' deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete program',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}