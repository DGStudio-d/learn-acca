<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTeacherProfileRequest;
use App\Models\Language;
use App\Models\User;
use App\Services\ImageUploadService;
use App\Services\TeacherLanguageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function __construct(
        private ImageUploadService $imageUploadService,
        private TeacherLanguageService $teacherLanguageService
    ) {}

    /**
     * Get teacher profile.
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Ensure user is a teacher or admin
            if (!$user->isTeacher() && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. User is not a teacher or admin.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'image_path' => $user->image_path,
                        'image_url' => $this->imageUploadService->getImageUrl($user->image_path),
                        'languages' => $user->teacherLanguages()->select('languages.id', 'languages.name', 'languages.code')->get(),
                        'notify_email' => $user->notify_email,
                        'notify_whatsapp' => $user->notify_whatsapp,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve teacher profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update teacher profile.
     */
    public function updateProfile(UpdateTeacherProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Ensure user is a teacher or admin
            if (!$user->isTeacher() && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. User is not a teacher or admin.',
                ], 403);
            }

            DB::beginTransaction();

            $data = $request->validated();
            $oldImagePath = $user->image_path;

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $imagePath = $this->imageUploadService->uploadTeacherImage(
                    $request->file('image'),
                    $oldImagePath
                );
                $data['image_path'] = $imagePath;
            }

            // Update user profile
            $user->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Teacher profile updated successfully',
                'data' => [
                    'teacher' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'image_path' => $user->image_path,
                        'image_url' => $this->imageUploadService->getImageUrl($user->image_path),
                        'languages' => $user->teacherLanguages()->select('languages.id', 'languages.name', 'languages.code')->get(),
                        'notify_email' => $user->notify_email,
                        'notify_whatsapp' => $user->notify_whatsapp,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update teacher profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get teacher image.
     */
    public function getImage(Request $request, int $teacherId): JsonResponse
    {
        try {
            $teacher = User::where('id', $teacherId)
                          ->where('role', 'teacher')
                          ->first();

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found',
                ], 404);
            }

            $imageUrl = $this->imageUploadService->getImageUrl($teacher->image_path);

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher_id' => $teacher->id,
                    'name' => $teacher->name,
                    'image_path' => $teacher->image_path,
                    'image_url' => $imageUrl,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve teacher image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove teacher profile image.
     */
    public function removeImage(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Ensure user is a teacher or admin
            if (!$user->isTeacher() && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. User is not a teacher or admin.',
                ], 403);
            }

            if ($user->image_path) {
                $this->imageUploadService->deleteImage($user->image_path);
                $user->update(['image_path' => null]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Teacher profile image removed successfully',
                'data' => [
                    'teacher' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'image_path' => null,
                        'image_url' => null,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove teacher image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all teachers (for admin use).
     */
    public function getAllTeachers(Request $request): JsonResponse
    {
        try {
            $teachers = User::where('role', 'teacher')
                           ->with('teacherLanguages:languages.id,languages.name,languages.code')
                           ->select('id', 'name', 'email', 'phone', 'image_path')
                           ->get()
                           ->map(function ($teacher) {
                               return [
                                   'id' => $teacher->id,
                                   'name' => $teacher->name,
                                   'email' => $teacher->email,
                                   'phone' => $teacher->phone,
                                   'image_path' => $teacher->image_path,
                                   'image_url' => $this->imageUploadService->getImageUrl($teacher->image_path),
                                   'languages' => $teacher->teacherLanguages,
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
                'message' => 'Failed to retrieve teachers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get languages assigned to the authenticated teacher.
     */
    public function getMyLanguages(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            // Ensure user is a teacher or admin
            if (!$teacher->isTeacher() && !$teacher->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. User is not a teacher or admin.',
                ], 403);
            }

            $languages = $this->teacherLanguageService->getLanguagesByTeacher($teacher);

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                    ],
                    'languages' => $languages->map(function ($language) {
                        $assignment = $language->teacherLanguages->first();
                        return [
                            'id' => $language->id,
                            'code' => $language->code,
                            'name' => $language->name,
                            'active' => $language->active,
                            'assigned_at' => $assignment->assigned_at,
                            'assigned_by' => $assignment->assignedBy ? $assignment->assignedBy->name : 'Unknown',
                        ];
                    }),
                    'total' => $languages->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assigned languages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if teacher has access to a specific language.
     */
    public function checkLanguageAccess(Request $request, $languageId): JsonResponse
    {
        try {
            $teacher = $request->user();

            // Ensure user is a teacher or admin
            if (!$teacher->isTeacher() && !$teacher->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. User is not a teacher or admin.',
                ], 403);
            }

            $language = Language::findOrFail($languageId);
            $hasAccess = $this->teacherLanguageService->validateTeacherLanguageAccess($teacher, $language);

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher_id' => $teacher->id,
                    'language' => [
                        'id' => $language->id,
                        'code' => $language->code,
                        'name' => $language->name,
                    ],
                    'has_access' => $hasAccess,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check language access',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get teachers by language (public endpoint for guest access).
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
                        'code' => $language->code,
                        'name' => $language->name,
                    ],
                    'teachers' => $teachers->map(function ($teacher) {
                        return [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                            'email' => $teacher->email,
                            'phone' => $teacher->phone,
                            'image_path' => $teacher->image_path,
                            'image_url' => $this->imageUploadService->getImageUrl($teacher->image_path),
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
     * Get language content for teacher (requires language access).
     */
    public function getLanguageContent(Request $request, $languageId): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            // Get the language directly from the database
            $language = Language::findOrFail($languageId);

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                    ],
                    'language' => [
                        'id' => $language->id,
                        'code' => $language->code,
                        'name' => $language->name,
                        'active' => $language->active,
                    ],
                    'message' => 'You have access to this language content',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve language content',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}