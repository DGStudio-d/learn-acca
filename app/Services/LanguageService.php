<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LanguageService
{
    /**
     * Create a language with automatic level and program generation.
     */
    public function createLanguageWithLevelsAndPrograms(array $languageData, array $levelsData = []): Language
    {
        return DB::transaction(function () use ($languageData, $levelsData) {
            // Set default active status if not provided
            $languageData['active'] = $languageData['active'] ?? true;

            // Create the language
            $language = Language::create($languageData);

            // If no levels provided, create default levels
            if (empty($levelsData)) {
                $levelsData = $this->getDefaultLevels();
            }

            // Create levels and programs
            foreach ($levelsData as $levelData) {
                $level = $this->createLevel($language, $levelData);
                $this->createProgramForLevel($language, $level);
            }

            // Load relationships for return
            $language->load(['levels.programs']);

            return $language;
        });
    }

    /**
     * Create a level for a language.
     */
    private function createLevel(Language $language, array $levelData): Level
    {
        return Level::create([
            'language_id' => $language->id,
            'name' => $levelData['name'],
            'order' => $levelData['order'],
        ]);
    }

    /**
     * Create a program for a level.
     */
    private function createProgramForLevel(Language $language, Level $level): Program
    {
        return Program::create([
            'language_id' => $language->id,
            'level_id' => $level->id,
            'title' => "{$language->name} - {$level->name}",
            'description' => "Learn {$language->name} at {$level->name} level",
            'active' => true,
        ]);
    }

    /**
     * Get default levels structure.
     */
    private function getDefaultLevels(): array
    {
        return [
            ['name' => 'Beginner', 'order' => 1],
            ['name' => 'Intermediate', 'order' => 2],
            ['name' => 'Advanced', 'order' => 3],
        ];
    }

    /**
     * Get all languages with their statistics.
     */
    public function getAllLanguagesWithStatistics(): Collection
    {
        return Language::with(['levels', 'programs.enrollments'])
            ->withCount(['programs', 'teacherLanguages'])
            ->get()
            ->map(function ($language) {
                $totalEnrollments = $language->programs->sum(function ($program) {
                    return $program->enrollments->count();
                });

                $approvedEnrollments = $language->programs->sum(function ($program) {
                    return $program->enrollments->whereNotNull('access_granted_at')->count();
                });

                return [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                    'active' => $language->active,
                    'levels_count' => $language->levels->count(),
                    'programs_count' => $language->programs_count,
                    'teachers_count' => $language->teacher_languages_count,
                    'total_enrollments' => $totalEnrollments,
                    'approved_enrollments' => $approvedEnrollments,
                    'pending_enrollments' => $totalEnrollments - $approvedEnrollments,
                    'created_at' => $language->created_at,
                    'updated_at' => $language->updated_at,
                ];
            });
    }

    /**
     * Get program statistics for a language.
     */
    public function getLanguageProgramStatistics(Language $language): array
    {
        $programs = $language->programs()->with(['level', 'enrollments'])->get();

        $statistics = $programs->map(function ($program) {
            $enrollments = $program->enrollments;
            $approvedEnrollments = $enrollments->whereNotNull('access_granted_at');

            return [
                'program_id' => $program->id,
                'program_title' => $program->title,
                'level_name' => $program->level->name,
                'level_order' => $program->level->order,
                'active' => $program->active,
                'total_enrollments' => $enrollments->count(),
                'approved_enrollments' => $approvedEnrollments->count(),
                'pending_enrollments' => $enrollments->whereNull('access_granted_at')->count(),
                'approval_rate' => $enrollments->count() > 0 
                    ? round(($approvedEnrollments->count() / $enrollments->count()) * 100, 2) 
                    : 0,
            ];
        });

        return [
            'language' => [
                'id' => $language->id,
                'name' => $language->name,
                'code' => $language->code,
                'active' => $language->active,
            ],
            'summary' => [
                'total_programs' => $programs->count(),
                'active_programs' => $programs->where('active', true)->count(),
                'total_enrollments' => $programs->sum(fn($p) => $p->enrollments->count()),
                'total_approved_enrollments' => $programs->sum(fn($p) => $p->enrollments->whereNotNull('access_granted_at')->count()),
            ],
            'program_statistics' => $statistics,
        ];
    }

    /**
     * Add a new level to an existing language.
     */
    public function addLevelToLanguage(Language $language, array $levelData): Level
    {
        return DB::transaction(function () use ($language, $levelData) {
            $level = $this->createLevel($language, $levelData);
            $this->createProgramForLevel($language, $level);
            
            return $level->load('programs');
        });
    }

    /**
     * Update language status and cascade to programs.
     */
    public function updateLanguageStatus(Language $language, bool $active): Language
    {
        return DB::transaction(function () use ($language, $active) {
            $language->update(['active' => $active]);
            
            // Update all programs under this language
            $language->programs()->update(['active' => $active]);
            
            return $language->fresh();
        });
    }

    /**
     * Check if language can be safely deleted.
     */
    public function canDeleteLanguage(Language $language): array
    {
        $hasEnrollments = $language->programs()
            ->whereHas('enrollments')
            ->exists();

        $hasTeachers = $language->teacherLanguages()->exists();

        $hasQuizzes = $language->programs()
            ->whereHas('quizzes')
            ->exists();

        $hasMeetings = $language->programs()
            ->whereHas('meetings')
            ->exists();

        return [
            'can_delete' => !($hasEnrollments || $hasTeachers || $hasQuizzes || $hasMeetings),
            'reasons' => [
                'has_enrollments' => $hasEnrollments,
                'has_teachers' => $hasTeachers,
                'has_quizzes' => $hasQuizzes,
                'has_meetings' => $hasMeetings,
            ],
        ];
    }
}