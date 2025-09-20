<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $assignedAt = fake()->dateTimeBetween('-3 months', 'now');
        $hasAccess = fake()->boolean(70); // 70% chance of having access granted
        
        return [
            'user_id' => User::factory()->state(['role' => 'student']),
            'program_id' => Program::factory(),
            'assigned_at' => $assignedAt,
            'access_granted_at' => $hasAccess ? fake()->dateTimeBetween($assignedAt, 'now') : null,
            'approved_by' => $hasAccess ? User::factory()->state(['role' => 'admin']) : null,
        ];
    }

    /**
     * Create an approved enrollment.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $assignedAt = $attributes['assigned_at'] ?? fake()->dateTimeBetween('-3 months', 'now');
            
            return [
                'access_granted_at' => fake()->dateTimeBetween($assignedAt, 'now'),
                'approved_by' => User::factory()->state(['role' => 'admin']),
            ];
        });
    }

    /**
     * Create a pending enrollment.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_granted_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Set specific user and program.
     */
    public function forUserAndProgram(User $user, Program $program): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'program_id' => $program->id,
        ]);
    }
}