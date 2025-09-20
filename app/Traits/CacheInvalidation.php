<?php

namespace App\Traits;

use App\Services\CacheService;

trait CacheInvalidation
{
    /**
     * Boot the cache invalidation trait.
     */
    protected static function bootCacheInvalidation()
    {
        static::created(function ($model) {
            $model->invalidateRelatedCaches();
        });

        static::updated(function ($model) {
            $model->invalidateRelatedCaches();
        });

        static::deleted(function ($model) {
            $model->invalidateRelatedCaches();
        });
    }

    /**
     * Invalidate related caches based on model type.
     */
    public function invalidateRelatedCaches()
    {
        $cacheService = app(CacheService::class);

        switch (get_class($this)) {
            case \App\Models\User::class:
                $cacheService->clearUserCache($this->id);
                $cacheService->clearUserDashboardCache($this->id);
                if ($this->isTeacher()) {
                    $cacheService->clearTeacherCache($this->id);
                }
                // Only clear system stats on user creation/deletion, not updates
                if ($this->wasRecentlyCreated || (method_exists($this, 'trashed') && $this->trashed())) {
                    $cacheService->clearSystemStatsCache();
                }
                break;

            case \App\Models\Program::class:
                $cacheService->clearProgramCache($this->id);
                $cacheService->clearLanguageCache($this->language_id);
                $cacheService->clearSystemStatsCache();
                break;

            case \App\Models\Quiz::class:
                $cacheService->clearQuizCache($this->id);
                if ($this->teacher_id) {
                    $cacheService->clearTeacherCache($this->teacher_id);
                }
                $cacheService->clearSystemStatsCache();
                break;

            case \App\Models\Enrollment::class:
                $cacheService->clearUserCache($this->user_id);
                $cacheService->clearProgramCache($this->program_id);
                $cacheService->clearUserDashboardCache($this->user_id);
                $cacheService->clearSystemStatsCache();
                break;

            case \App\Models\QuizAttempt::class:
                if ($this->student_id) {
                    $cacheService->clearUserDashboardCache($this->student_id);
                }
                $cacheService->clearQuizCache($this->quiz_id);
                $cacheService->clearSystemStatsCache();
                break;

            case \App\Models\Language::class:
                $cacheService->clearLanguageCache($this->id);
                $cacheService->clearSystemStatsCache();
                break;
        }
    }
}