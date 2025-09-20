<?php

namespace App\Services;

use App\Models\Language;
use App\Models\TeacherLanguage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TeacherLanguageService
{
    /**
     * Assign a teacher to a language.
     *
     * @param User $teacher
     * @param Language $language
     * @param User $assignedBy
     * @return TeacherLanguage
     * @throws InvalidArgumentException
     */
    public function assignTeacherToLanguage(User $teacher, Language $language, User $assignedBy): TeacherLanguage
    {
        // Validate that the user is actually a teacher
        if (!$teacher->isTeacher()) {
            throw new InvalidArgumentException('User must be a teacher to be assigned to a language.');
        }

        // Validate that the assigner is an admin
        if (!$assignedBy->isAdmin()) {
            throw new InvalidArgumentException('Only admins can assign teachers to languages.');
        }

        // Check if assignment already exists
        $existingAssignment = TeacherLanguage::where('user_id', $teacher->id)
                                           ->where('language_id', $language->id)
                                           ->first();

        if ($existingAssignment) {
            throw new InvalidArgumentException('Teacher is already assigned to this language.');
        }

        return TeacherLanguage::create([
            'user_id' => $teacher->id,
            'language_id' => $language->id,
            'assigned_at' => now(),
            'assigned_by' => $assignedBy->id,
        ]);
    }

    /**
     * Assign a teacher to multiple languages.
     *
     * @param User $teacher
     * @param array $languageIds
     * @param User $assignedBy
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function assignTeacherToMultipleLanguages(User $teacher, array $languageIds, User $assignedBy): SupportCollection
    {
        // Validate that the user is actually a teacher
        if (!$teacher->isTeacher()) {
            throw new InvalidArgumentException('User must be a teacher to be assigned to languages.');
        }

        // Validate that the assigner is an admin
        if (!$assignedBy->isAdmin()) {
            throw new InvalidArgumentException('Only admins can assign teachers to languages.');
        }

        $assignments = collect();

        DB::transaction(function () use ($teacher, $languageIds, $assignedBy, &$assignments) {
            foreach ($languageIds as $languageId) {
                $language = Language::findOrFail($languageId);
                
                // Check if assignment already exists
                $existingAssignment = TeacherLanguage::where('user_id', $teacher->id)
                                                   ->where('language_id', $language->id)
                                                   ->first();

                if (!$existingAssignment) {
                    $assignment = TeacherLanguage::create([
                        'user_id' => $teacher->id,
                        'language_id' => $language->id,
                        'assigned_at' => now(),
                        'assigned_by' => $assignedBy->id,
                    ]);
                    $assignments->push($assignment);
                }
            }
        });

        return $assignments;
    }

    /**
     * Remove a teacher from a language.
     *
     * @param User $teacher
     * @param Language $language
     * @param User $removedBy
     * @return bool
     * @throws InvalidArgumentException
     */
    public function removeTeacherFromLanguage(User $teacher, Language $language, User $removedBy): bool
    {
        // Validate that the remover is an admin
        if (!$removedBy->isAdmin()) {
            throw new InvalidArgumentException('Only admins can remove teachers from languages.');
        }

        $assignment = TeacherLanguage::where('user_id', $teacher->id)
                                   ->where('language_id', $language->id)
                                   ->first();

        if (!$assignment) {
            throw new InvalidArgumentException('Teacher is not assigned to this language.');
        }

        return $assignment->delete();
    }

    /**
     * Remove a teacher from multiple languages.
     *
     * @param User $teacher
     * @param array $languageIds
     * @param User $removedBy
     * @return int Number of assignments removed
     * @throws InvalidArgumentException
     */
    public function removeTeacherFromMultipleLanguages(User $teacher, array $languageIds, User $removedBy): int
    {
        // Validate that the remover is an admin
        if (!$removedBy->isAdmin()) {
            throw new InvalidArgumentException('Only admins can remove teachers from languages.');
        }

        return TeacherLanguage::where('user_id', $teacher->id)
                             ->whereIn('language_id', $languageIds)
                             ->delete();
    }

    /**
     * Get all teachers assigned to a specific language.
     *
     * @param Language $language
     * @return Collection
     */
    public function getTeachersByLanguage(Language $language): Collection
    {
        return User::whereHas('teacherLanguages', function ($query) use ($language) {
            $query->where('language_id', $language->id);
        })
        ->where('role', 'teacher')
        ->select('id', 'name', 'email', 'phone', 'image_path')
        ->get();
    }

    /**
     * Get all languages assigned to a specific teacher.
     *
     * @param User $teacher
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function getLanguagesByTeacher(User $teacher): Collection
    {
        if (!$teacher->isTeacher() && !$teacher->isAdmin()) {
            throw new InvalidArgumentException('User must be a teacher or admin.');
        }

        // If admin, return empty collection as admins don't have assigned languages
        if ($teacher->isAdmin()) {
            return collect();
        }

        return Language::whereHas('teacherLanguages', function ($query) use ($teacher) {
            $query->where('user_id', $teacher->id);
        })
        ->with(['teacherLanguages' => function ($query) use ($teacher) {
            $query->where('user_id', $teacher->id)
                  ->with('assignedBy:id,name');
        }])
        ->select('id', 'code', 'name', 'active')
        ->get();
    }

    /**
     * Check if a teacher is assigned to a specific language.
     *
     * @param User $teacher
     * @param Language $language
     * @return bool
     */
    public function isTeacherAssignedToLanguage(User $teacher, Language $language): bool
    {
        return TeacherLanguage::where('user_id', $teacher->id)
                             ->where('language_id', $language->id)
                             ->exists();
    }

    /**
     * Get all teacher-language assignments with details.
     *
     * @return Collection
     */
    public function getAllAssignments(): Collection
    {
        return TeacherLanguage::with([
            'teacher:id,name,email,phone,image_path',
            'language:id,code,name,active',
            'assignedBy:id,name'
        ])->get();
    }

    /**
     * Validate teacher access to a language.
     *
     * @param User $teacher
     * @param Language $language
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateTeacherLanguageAccess(User $teacher, Language $language): bool
    {
        if (!$teacher->isTeacher() && !$teacher->isAdmin()) {
            throw new InvalidArgumentException('User must be a teacher or admin.');
        }

        // Admins have access to all languages
        if ($teacher->isAdmin()) {
            return true;
        }

        return $this->isTeacherAssignedToLanguage($teacher, $language);
    }

    /**
     * Get available languages for assignment (not yet assigned to teacher).
     *
     * @param User $teacher
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function getAvailableLanguagesForTeacher(User $teacher): Collection
    {
        if (!$teacher->isTeacher() && !$teacher->isAdmin()) {
            throw new InvalidArgumentException('User must be a teacher or admin.');
        }

        // If admin, return all languages as they can be assigned to any language
        if ($teacher->isAdmin()) {
            return Language::where('active', true)->get();
        }

        $assignedLanguageIds = TeacherLanguage::where('user_id', $teacher->id)
                                            ->pluck('language_id')
                                            ->toArray();

        return Language::whereNotIn('id', $assignedLanguageIds)
                      ->where('active', true)
                      ->select('id', 'code', 'name')
                      ->orderBy('name')
                      ->get();
    }
}