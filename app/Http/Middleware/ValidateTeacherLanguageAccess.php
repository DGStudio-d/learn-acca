<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Services\TeacherLanguageService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTeacherLanguageAccess
{
    public function __construct(
        private TeacherLanguageService $teacherLanguageService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Allow admins to access any language content
        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        // Only apply to teachers
        if (!$user || !$user->isTeacher()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. User is not a teacher.',
            ], 403);
        }

        // Get language from route parameter
        $languageId = $request->route('language');
        
        if ($languageId) {
            $language = Language::find($languageId);

            if ($language && !$this->teacherLanguageService->validateTeacherLanguageAccess($user, $language)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. You are not assigned to this language.',
                ], 403);
            }
        }

        return $next($request);
    }
}