<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Services\MeetingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MeetingController extends Controller
{
    protected MeetingService $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    /**
     * Get meetings based on user role.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $filters = $request->only(['program_id', 'teacher_id', 'status', 'active']);

            if ($user->isAdmin()) {
                $meetings = $this->meetingService->getAllMeetings($filters);
            } elseif ($user->isTeacher()) {
                $meetings = $this->meetingService->getTeacherMeetings($user, $filters);
            } else {
                // Student
                $meetings = $this->meetingService->getStudentMeetings($user, $filters);
            }

            return response()->json([
                'success' => true,
                'data' => $meetings->map(function ($meeting) {
                    return [
                        'id' => $meeting->id,
                        'title' => $meeting->title,
                        'description' => $meeting->description,
                        'meeting_link' => $meeting->meeting_link,
                        'start_time' => $meeting->start_time->toISOString(),
                        'timezone' => $meeting->timezone,
                        'status' => $meeting->status,
                        'active' => $meeting->active,
                        'program' => [
                            'id' => $meeting->program->id,
                            'title' => $meeting->program->title,
                            'language' => $meeting->program->language->name,
                            'level' => $meeting->program->level->name,
                        ],
                        'teacher' => [
                            'id' => $meeting->teacher->id,
                            'name' => $meeting->teacher->name,
                        ],
                        'created_at' => $meeting->created_at->toISOString(),
                        'updated_at' => $meeting->updated_at->toISOString(),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MEETINGS_FETCH_ERROR',
                    'message' => 'Failed to fetch meetings',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Create a new meeting (teachers and admins only).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->isTeacher() && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Only teachers and admins can create meetings',
                    ],
                ], 403);
            }

            $meeting = $this->meetingService->createMeeting($request->all(), $user);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'description' => $meeting->description,
                    'meeting_link' => $meeting->meeting_link,
                    'start_time' => $meeting->start_time->toISOString(),
                    'timezone' => $meeting->timezone,
                    'status' => $meeting->status,
                    'active' => $meeting->active,
                    'program' => [
                        'id' => $meeting->program->id,
                        'title' => $meeting->program->title,
                        'language' => $meeting->program->language->name,
                        'level' => $meeting->program->level->name,
                    ],
                    'teacher' => [
                        'id' => $meeting->teacher->id,
                        'name' => $meeting->teacher->name,
                    ],
                    'created_at' => $meeting->created_at->toISOString(),
                    'updated_at' => $meeting->updated_at->toISOString(),
                ],
                'message' => 'Meeting created successfully',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => $e->errors(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MEETING_CREATION_ERROR',
                    'message' => 'Failed to create meeting',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Get a specific meeting.
     */
    public function show(Meeting $meeting): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user has access to this meeting
            if (!$this->canUserAccessMeeting($user, $meeting)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'You do not have access to this meeting',
                    ],
                ], 403);
            }

            $meeting->load(['program.language', 'program.level', 'teacher']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'description' => $meeting->description,
                    'meeting_link' => $meeting->meeting_link,
                    'start_time' => $meeting->start_time->toISOString(),
                    'timezone' => $meeting->timezone,
                    'status' => $meeting->status,
                    'active' => $meeting->active,
                    'program' => [
                        'id' => $meeting->program->id,
                        'title' => $meeting->program->title,
                        'language' => $meeting->program->language->name,
                        'level' => $meeting->program->level->name,
                    ],
                    'teacher' => [
                        'id' => $meeting->teacher->id,
                        'name' => $meeting->teacher->name,
                    ],
                    'created_at' => $meeting->created_at->toISOString(),
                    'updated_at' => $meeting->updated_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MEETING_FETCH_ERROR',
                    'message' => 'Failed to fetch meeting',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Update a meeting (teachers and admins only).
     */
    public function update(Request $request, Meeting $meeting): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user can update this meeting
            if (!$user->isAdmin() && $meeting->teacher_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'You can only update your own meetings',
                    ],
                ], 403);
            }

            $updatedMeeting = $this->meetingService->updateMeeting($meeting, $request->all(), $user);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $updatedMeeting->id,
                    'title' => $updatedMeeting->title,
                    'description' => $updatedMeeting->description,
                    'meeting_link' => $updatedMeeting->meeting_link,
                    'start_time' => $updatedMeeting->start_time->toISOString(),
                    'timezone' => $updatedMeeting->timezone,
                    'status' => $updatedMeeting->status,
                    'active' => $updatedMeeting->active,
                    'program' => [
                        'id' => $updatedMeeting->program->id,
                        'title' => $updatedMeeting->program->title,
                        'language' => $updatedMeeting->program->language->name,
                        'level' => $updatedMeeting->program->level->name,
                    ],
                    'teacher' => [
                        'id' => $updatedMeeting->teacher->id,
                        'name' => $updatedMeeting->teacher->name,
                    ],
                    'created_at' => $updatedMeeting->created_at->toISOString(),
                    'updated_at' => $updatedMeeting->updated_at->toISOString(),
                ],
                'message' => 'Meeting updated successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => $e->errors(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MEETING_UPDATE_ERROR',
                    'message' => 'Failed to update meeting',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Delete a meeting (teachers and admins only).
     */
    public function destroy(Meeting $meeting): JsonResponse
    {
        try {
            $user = Auth::user();

            $this->meetingService->deleteMeeting($meeting, $user);

            return response()->json([
                'success' => true,
                'message' => 'Meeting deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MEETING_DELETE_ERROR',
                    'message' => 'Failed to delete meeting',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Get upcoming meetings for the authenticated user.
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $filters = array_merge($request->only(['program_id']), ['status' => 'upcoming']);

            if ($user->isAdmin()) {
                $meetings = $this->meetingService->getAllMeetings($filters);
            } elseif ($user->isTeacher()) {
                $meetings = $this->meetingService->getTeacherMeetings($user, $filters);
            } else {
                $meetings = $this->meetingService->getStudentMeetings($user, $filters);
            }

            return response()->json([
                'success' => true,
                'data' => $meetings->map(function ($meeting) {
                    return [
                        'id' => $meeting->id,
                        'title' => $meeting->title,
                        'description' => $meeting->description,
                        'meeting_link' => $meeting->meeting_link,
                        'start_time' => $meeting->start_time->toISOString(),
                        'timezone' => $meeting->timezone,
                        'status' => $meeting->status,
                        'program' => [
                            'id' => $meeting->program->id,
                            'title' => $meeting->program->title,
                            'language' => $meeting->program->language->name,
                            'level' => $meeting->program->level->name,
                        ],
                        'teacher' => [
                            'id' => $meeting->teacher->id,
                            'name' => $meeting->teacher->name,
                        ],
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UPCOMING_MEETINGS_ERROR',
                    'message' => 'Failed to fetch upcoming meetings',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Check if user can access a specific meeting.
     */
    private function canUserAccessMeeting($user, Meeting $meeting): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            // Teachers can access meetings they created or meetings for programs in their assigned languages
            if ($meeting->teacher_id === $user->id) {
                return true;
            }

            $teacherLanguageIds = $user->teacherLanguages()->pluck('languages.id');
            return $teacherLanguageIds->contains($meeting->program->language_id);
        }

        // Students can access meetings for programs they are enrolled in and have access to
        $approvedProgramIds = $user->approvedPrograms()->pluck('programs.id');
        return $approvedProgramIds->contains($meeting->program_id) && $meeting->active;
    }
}