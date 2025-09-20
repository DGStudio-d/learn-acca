<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherLanguage>
 */
class TeacherLanguageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'teacher']),
            'language_id' => Language::factory(),
            'assigned_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'assigned_by' => User::factory()->state(['role' => 'admin']),
        ];
    }

    /**
     * Set specific teacher and language.
     */
    public function forTeacherAndLanguage(User $teacher, Language $language): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $teacher->id,
            'language_id' => $language->id,
        ]);
    }

    /**
     * Set specific admin who assigned.
     */
    public function assignedBy(User $admin): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_by' => $admin->id,
        ]);
    }
}