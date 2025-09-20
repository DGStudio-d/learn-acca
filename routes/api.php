<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\StudentQuizController;
use App\Http\Controllers\Api\GuestQuizController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\TranslationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Learn Academy API is running',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
});

// Translation routes with locale detection middleware
Route::middleware(['locale'])->prefix('translations')->group(function () {
    // Public translation routes
    Route::get('/locales', [TranslationController::class, 'getSupportedLocales']);
    Route::get('/{locale}', [TranslationController::class, 'getTranslations']);
    Route::get('/{locale}/keys', [TranslationController::class, 'getAvailableKeys']);
    Route::post('/{locale}/translate', [TranslationController::class, 'getTranslation']);
    Route::post('/validate-key', [TranslationController::class, 'validateKey']);
    
    // Admin-only translation management routes
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::put('/{locale}', [TranslationController::class, 'updateTranslation']);
        Route::post('/cache/clear', [TranslationController::class, 'clearCache']);
    });
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Protected routes with role-based access
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Admin only routes
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/pending-enrollments', [AdminController::class, 'getPendingEnrollments']);
        Route::get('/all-pending-enrollments', [AdminController::class, 'getAllPendingEnrollments']);
        Route::get('/enrollment-statistics', [AdminController::class, 'getEnrollmentStatistics']);
        Route::post('/grant-access', [AdminController::class, 'grantProgramAccess']);
        Route::post('/bulk-grant-access', [AdminController::class, 'bulkGrantProgramAccess']);
        Route::post('/bulk-approve-enrollments', [AdminController::class, 'bulkApproveEnrollments']);
        Route::post('/revoke-access', [AdminController::class, 'revokeProgramAccess']);
        Route::get('/programs/{program}/students', [AdminController::class, 'getProgramStudents']);
        
        // User management routes
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::get('/users/{user}', [AdminController::class, 'getUser']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
        
        // Admin quiz management routes
        Route::prefix('quizzes')->group(function () {
            Route::get('/', [QuizController::class, 'adminIndex']);
            Route::get('/{quiz}', [QuizController::class, 'adminShow']);
            Route::put('/{quiz}', [QuizController::class, 'adminUpdate']);
            Route::delete('/{quiz}', [QuizController::class, 'adminDestroy']);
        });
        
        // Teacher-Language assignment routes
        Route::post('/assign-teacher-language', [AdminController::class, 'assignTeacherToLanguage']);
        Route::post('/assign-teacher-multiple-languages', [AdminController::class, 'assignTeacherToMultipleLanguages']);
        Route::delete('/remove-teacher-language', [AdminController::class, 'removeTeacherFromLanguage']);
        Route::get('/languages/{language}/teachers', [AdminController::class, 'getTeachersByLanguage']);
        Route::get('/teacher-language-assignments', [AdminController::class, 'getAllTeacherLanguageAssignments']);
        Route::get('/teachers/{teacher}/available-languages', [AdminController::class, 'getAvailableLanguagesForTeacher']);
        
        // Language management routes
        Route::apiResource('languages', LanguageController::class);
        Route::get('/languages/{language}/statistics', [LanguageController::class, 'getProgramStatistics']);
        Route::get('/languages/{language}/programs', [LanguageController::class, 'getPrograms']);
        
        // Program management routes
        Route::apiResource('programs', ProgramController::class)->except(['store']);
        Route::get('/programs/{program}/students', [ProgramController::class, 'getStudents']);
        Route::get('/programs/{program}/statistics', [ProgramController::class, 'getStatistics']);
        Route::get('/programs/{program}/pending-enrollments', [ProgramController::class, 'getPendingEnrollments']);
        Route::get('/programs/{program}/approved-enrollments', [ProgramController::class, 'getApprovedEnrollments']);
        
        // Settings management routes
        Route::prefix('settings')->group(function () {
            Route::get('/guest-access', [SettingsController::class, 'getGuestAccessSettings']);
            Route::put('/guest-access', [SettingsController::class, 'updateGuestAccessSettings']);
            Route::get('/{key}', [SettingsController::class, 'getSetting']);
            Route::put('/{key}', [SettingsController::class, 'setSetting']);
            Route::post('/initialize-defaults', [SettingsController::class, 'initializeDefaults']);
        });
    });

    // Teacher routes
    Route::middleware(['role:teacher,admin'])->prefix('teacher')->group(function () {
        Route::get('/profile', [TeacherController::class, 'getProfile']);
        Route::put('/profile', [TeacherController::class, 'updateProfile']);
        Route::delete('/profile/image', [TeacherController::class, 'removeImage']);
        Route::get('/my-languages', [TeacherController::class, 'getMyLanguages']);
        Route::get('/languages/{language}/access', [TeacherController::class, 'checkLanguageAccess']);
        
        // Routes that require teacher language access validation
        Route::middleware(['teacher.language.access'])->group(function () {
            Route::get('/languages/{language}/content', [TeacherController::class, 'getLanguageContent']);
        });
        
        // Quiz management routes
        Route::prefix('quizzes')->group(function () {
            Route::get('/', [QuizController::class, 'index']);
            Route::post('/', [QuizController::class, 'store']);
            Route::get('/{quiz}', [QuizController::class, 'show']);
            Route::put('/{quiz}', [QuizController::class, 'update']);
            Route::delete('/{quiz}', [QuizController::class, 'destroy']);
            Route::get('/{quiz}/download', [QuizController::class, 'downloadFile']);
            
            // Quiz question management
            Route::post('/{quiz}/questions', [QuizController::class, 'addQuestion']);
            Route::put('/{quiz}/questions/{question}', [QuizController::class, 'updateQuestion']);
            Route::delete('/{quiz}/questions/{question}', [QuizController::class, 'deleteQuestion']);
            Route::post('/{quiz}/questions/reorder', [QuizController::class, 'reorderQuestions']);
            
            // Quiz statistics and attempts (for teachers)
            Route::get('/{quiz}/statistics', [QuizController::class, 'getStatistics']);
            Route::get('/{quiz}/attempts', [QuizController::class, 'getAttempts']);
            Route::get('/{quiz}/attempts/{attempt}/results', [QuizController::class, 'getAttemptResults']);
        });
        
        // Meeting management routes
        Route::prefix('meetings')->group(function () {
            Route::get('/', [MeetingController::class, 'index']);
            Route::post('/', [MeetingController::class, 'store']);
            Route::get('/upcoming', [MeetingController::class, 'upcoming']);
            Route::get('/{meeting}', [MeetingController::class, 'show']);
            Route::put('/{meeting}', [MeetingController::class, 'update']);
            Route::delete('/{meeting}', [MeetingController::class, 'destroy']);
        });
        
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Teacher dashboard']);
        });
    });

    // Admin routes for teacher management
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/teachers', [TeacherController::class, 'getAllTeachers']);
        Route::get('/teachers/{teacherId}/image', [TeacherController::class, 'getImage']);
    });

    // Student routes
    Route::middleware(['role:student,admin'])->prefix('student')->group(function () {
        Route::get('/enrollments', [StudentController::class, 'getMyEnrollments']);
        Route::get('/programs', [StudentController::class, 'getMyApprovedPrograms']);
        
        // Student quiz routes
        Route::prefix('quizzes')->group(function () {
            Route::get('/', [StudentQuizController::class, 'index']);
            Route::get('/{quiz}', [StudentQuizController::class, 'show']);
            Route::post('/{quiz}/attempt', [StudentQuizController::class, 'submitAttempt']);
            Route::get('/attempts/my', [StudentQuizController::class, 'getMyAttempts']);
            Route::get('/{quiz}/attempts/my', [StudentQuizController::class, 'getMyAttempts']);
            Route::get('/{quiz}/attempts/{attempt}/results', [StudentQuizController::class, 'getMyAttemptResults']);
        });
        
        // Student meeting routes
        Route::prefix('meetings')->group(function () {
            Route::get('/', [MeetingController::class, 'index']);
            Route::get('/upcoming', [MeetingController::class, 'upcoming']);
            Route::get('/{meeting}', [MeetingController::class, 'show']);
        });
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        // User notification routes
        Route::get('/history', [NotificationController::class, 'getUserHistory']);
        Route::get('/stats', [NotificationController::class, 'getUserStats']);
        Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
        
        // Admin notification routes
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/system/stats', [NotificationController::class, 'getSystemStats']);
            Route::get('/users/{user}/history', [NotificationController::class, 'getUserHistoryByAdmin']);
            Route::post('/retry-failed', [NotificationController::class, 'retryFailedNotifications']);
            Route::post('/cleanup', [NotificationController::class, 'cleanupOldLogs']);
        });
    });

    // Permission-based routes
    Route::middleware(['permission:manage-quizzes'])->group(function () {
        Route::get('/quizzes/manage', function () {
            return response()->json(['message' => 'Quiz management endpoint']);
        });
    });
});

// Guest access routes with conditional middleware
Route::prefix('guest')->group(function () {
    // Guest language routes
    Route::middleware(['guest.access:languages'])->group(function () {
        Route::get('/languages', [GuestController::class, 'getLanguages']);
    });

    // Guest teacher routes
    Route::middleware(['guest.access:teachers'])->group(function () {
        Route::get('/teachers', [GuestController::class, 'getTeachers']);
        Route::get('/languages/{language}/teachers', [GuestController::class, 'getTeachersByLanguage']);
    });

    // Guest quiz routes
    Route::middleware(['guest.access:quizzes'])->group(function () {
        Route::get('/quizzes', [GuestController::class, 'getQuizzes']);
        Route::get('/quizzes/{quizId}', [GuestController::class, 'getQuiz']);
        Route::post('/quizzes/{quizId}/attempt', [GuestController::class, 'submitQuizAttempt']);
    });
});

// Legacy public routes (for backward compatibility)
Route::get('/languages/{language}/teachers', [TeacherController::class, 'getTeachersByLanguage']);

// Legacy guest quiz routes (for backward compatibility)
Route::prefix('guest/quizzes')->group(function () {
    Route::get('/', [GuestQuizController::class, 'index']);
    Route::get('/{quiz}', [GuestQuizController::class, 'show']);
    Route::post('/{quiz}/attempt', [GuestQuizController::class, 'submitAttempt']);
});