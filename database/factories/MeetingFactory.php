<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'teacher_id' => User::factory()->state(['role' => 'teacher']),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'meeting_link' => fake()->url(),
            'start_time' => fake()->dateTimeBetween('now', '+1 month'),
            'timezone' => fake()->randomElement(['UTC', 'America/New_York', 'Europe/London', 'Asia/Dubai']),
            'active' => fake()->boolean(95), // 95% chance of being active
        ];
    }

    /**
     * Create an upcoming meeting.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => fake()->dateTimeBetween('+1 hour', '+1 month'),
        ]);
    }

    /**
     * Create a past meeting.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => fake()->dateTimeBetween('-1 month', '-1 hour'),
        ]);
    }

    /**
     * Create a meeting for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => fake()->dateTimeBetween('today', 'tomorrow'),
        ]);
    }

    /**
     * Set specific program and teacher.
     */
    public function forProgramAndTeacher(Program $program, User $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    /**
     * Create an active meeting.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }
}