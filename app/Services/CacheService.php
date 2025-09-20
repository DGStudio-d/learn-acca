<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Program;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    const CACHE_TTL = 3600; // 1 hour
    const LONG_CACHE_TTL = 86400; // 24 hours

    /**
     * Cache active languages with their levels and programs.
     */
    public function getActiveLanguages()
    {
        return Cache::remember('active_languages', self::LONG_CACHE_TTL, function () {
            return Language::active()
                ->with(['levels', 'programs'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Cache teachers by language.
     */
    public function getTeachersByLanguage(int $languageId)
    {
        return Cache::remember("teachers_language_{$languageId}", self::CACHE_TTL, function () use ($languageId) {
            return User::where('role', 'teacher')
                ->whereHas('teacherLanguages', function ($query) use ($languageId) {
                    $query->where('language_id', $languageId);
                })
                ->select('id', 'name', 'image_path')
                ->get();
        });
    }

    /**
     * Cache program statistics.
     */
    public function getProgramStats(int $programId)
    {
        return Cache::remember("program_stats_{$programId}", self::CACHE_TTL, function () use ($programId) {
            $program = Program::withCounts()->find($programId);
            
            return [
                'total_enrollments' => $program->enrollments_count,
                'approved_enrollments' => $program->approved_enrollments_count,
                'pending_enrollments' => $program->enrollments_count - $program->approved_enrollments_count,
            ];
        });
    }

    /**
     * Cache user's approved programs.
     */
    public function getUserApprovedPrograms(int $userId)
    {
        return Cache::remember("user_approved_programs_{$userId}", self::CACHE_TTL, function () use ($userId) {
            return User::find($userId)
                ->approvedPrograms()
                ->withProgramRelations()
                ->get();
        });
    }

    /**
     * Cache guest access settings.
     */
    public function getGuestSettings()
    {
        return Cache::remember('guest_settings', self::LONG_CACHE_TTL, function () {
            $settings = Settings::pluck('value', 'key');
            
            return [
                'allow_guest_languages' => $settings['allow_guest_languages'] ?? false,
                'allow_guest_teachers' => $settings['allow_guest_teachers'] ?? false,
                'allow_guest_quizzes' => $settings['allow_guest_quizzes'] ?? false,
            ];
        });
    }

    /**
     * Cache translations for a specific locale.
     */
    public function getTranslations(string $locale)
    {
        return Cache::remember("translations_{$locale}", self::LONG_CACHE_TTL, function () use ($locale) {
            $path = resource_path("lang/{$locale}.json");
            
            if (!file_exists($path)) {
                return [];
            }
            
            return json_decode(file_get_contents($path), true) ?? [];
        });
    }

    /**
     * Cache quiz with questions for performance.
     */
    public function getQuizWithQuestions(int $quizId)
    {
        return Cache::remember("quiz_questions_{$quizId}", self::CACHE_TTL, function () use ($quizId) {
            return \App\Models\Quiz::withQuizRelations()
                ->find($quizId);
        });
    }

    /**
     * Get user dashboard data (cached).
     */
    public function getUserDashboard(int $userId)
    {
        return Cache::remember("user_dashboard_{$userId}", self::CACHE_TTL, function () use ($userId) {
            return \App\Models\User::forDashboard()
                ->find($userId);
        });
    }

    /**
     * Get program statistics with enrollment data (cached).
     */
    public function getProgramWithStatistics(int $programId)
    {
        return Cache::remember("program_with_stats_{$programId}", self::CACHE_TTL, function () use ($programId) {
            return \App\Models\Program::withStatistics()
                ->withProgramRelations()
                ->find($programId);
        });
    }

    /**
     * Get teacher's content (cached).
     */
    public function getTeacherContent(int $teacherId, string $type = 'quizzes')
    {
        return Cache::remember("teacher_{$type}_{$teacherId}", self::CACHE_TTL, function () use ($teacherId, $type) {
            $model = match($type) {
                'quizzes' => \App\Models\Quiz::class,
                'meetings' => \App\Models\Meeting::class,
                default => \App\Models\Quiz::class
            };

            return $model::forTeacher($teacherId)
                ->with(['program.language', 'program.level'])
                ->get();
        });
    }

    /**
     * Get recent quiz attempts for a user (cached).
     */
    public function getUserRecentAttempts(int $userId, int $limit = 10)
    {
        return Cache::remember("user_recent_attempts_{$userId}_{$limit}", self::CACHE_TTL, function () use ($userId, $limit) {
            return \App\Models\QuizAttempt::where('student_id', $userId)
                ->with(['quiz:id,title,program_id', 'quiz.program:id,title,language_id', 'quiz.program.language:id,name'])
                ->orderBy('submitted_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get popular quizzes (cached).
     */
    public function getPopularQuizzes(int $limit = 10)
    {
        return Cache::remember("popular_quizzes_{$limit}", self::LONG_CACHE_TTL, function () use ($limit) {
            return \App\Models\Quiz::withCount('attempts')
                ->with(['program.language:id,name', 'program.level:id,name'])
                ->orderBy('attempts_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get system statistics (cached).
     */
    public function getSystemStats()
    {
        return Cache::remember('system_stats', self::LONG_CACHE_TTL, function () {
            return [
                'total_users' => \App\Models\User::count(),
                'total_students' => \App\Models\User::where('role', 'student')->count(),
                'total_teachers' => \App\Models\User::where('role', 'teacher')->count(),
                'total_programs' => \App\Models\Program::count(),
                'total_quizzes' => \App\Models\Quiz::count(),
                'total_attempts' => \App\Models\QuizAttempt::count(),
                'active_enrollments' => \App\Models\Enrollment::whereNotNull('access_granted_at')->count(),
            ];
        });
    }

    /**
     * Clear cache for a specific pattern.
     */
    public function clearCache(string $pattern)
    {
        if (config('cache.default') === 'redis') {
            $keys = Cache::getRedis()->keys("*{$pattern}*");
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        } else {
            // For other cache drivers, we'll need to clear all cache
            Cache::flush();
        }
    }

    /**
     * Clear user-specific cache.
     */
    public function clearUserCache(int $userId)
    {
        $this->clearCache("user_approved_programs_{$userId}");
    }

    /**
     * Clear program-specific cache.
     */
    public function clearProgramCache(int $programId)
    {
        $this->clearCache("program_stats_{$programId}");
    }

    /**
     * Clear language-specific cache.
     */
    public function clearLanguageCache(int $languageId)
    {
        $this->clearCache("teachers_language_{$languageId}");
        $this->clearCache('active_languages');
    }

    /**
     * Clear settings cache.
     */
    public function clearSettingsCache()
    {
        Cache::forget('guest_settings');
    }

    /**
     * Clear translations cache.
     */
    public function clearTranslationsCache(string $locale = null)
    {
        if ($locale) {
            Cache::forget("translations_{$locale}");
        } else {
            $this->clearCache('translations_');
        }
    }

    /**
     * Clear user dashboard cache.
     */
    public function clearUserDashboardCache(int $userId)
    {
        Cache::forget("user_dashboard_{$userId}");
        $this->clearCache("user_recent_attempts_{$userId}");
    }

    /**
     * Clear teacher content cache.
     */
    public function clearTeacherCache(int $teacherId)
    {
        $this->clearCache("teacher_quizzes_{$teacherId}");
        $this->clearCache("teacher_meetings_{$teacherId}");
    }

    /**
     * Clear quiz-related cache.
     */
    public function clearQuizCache(int $quizId)
    {
        Cache::forget("quiz_questions_{$quizId}");
        $this->clearCache('popular_quizzes_');
    }

    /**
     * Clear system statistics cache.
     */
    public function clearSystemStatsCache()
    {
        Cache::forget('system_stats');
    }

    /**
     * Clear all performance-related caches.
     */
    public function clearAllPerformanceCaches()
    {
        $this->clearCache('user_dashboard_');
        $this->clearCache('program_with_stats_');
        $this->clearCache('teacher_');
        $this->clearCache('popular_quizzes_');
        $this->clearCache('system_stats');
    }
}