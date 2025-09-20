<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Find user by ID.
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Assign user to programs based on their preferred language and level.
     */
    public function assignToPrograms(User $user, Collection $programs): void
    {
        $enrollmentData = [];
        $now = now();

        foreach ($programs as $program) {
            // Only add if not already enrolled
            if (!$user->programs()->where('program_id', $program->id)->exists()) {
                $enrollmentData[$program->id] = [
                    'assigned_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($enrollmentData)) {
            $user->programs()->attach($enrollmentData);
        }
    }

    /**
     * Get programs matching user's preferred language and level.
     */
    public function getMatchingPrograms(string $preferredLanguage, int $levelId): Collection
    {
        return Program::whereHas('language', function ($query) use ($preferredLanguage) {
            $query->where('code', $preferredLanguage);
        })->where('level_id', $levelId)->get();
    }
}