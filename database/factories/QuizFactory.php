<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['file', 'inline']);
        
        return [
            'program_id' => Program::factory(),
            'teacher_id' => \App\Models\User::factory(['role' => 'teacher']),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => $type,
            'file_path' => $type === 'file' ? 'quizzes/' . fake()->uuid() . '.pdf' : null,
            'pass_score' => fake()->numberBetween(60, 80),
            'allow_guest_access' => fake()->boolean(30), // 30% chance
            'active' => fake()->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Generate sample questions for file-type quizzes.
     */
    private function generateSampleQuestions(): array
    {
        $questions = [];
        $questionCount = fake()->numberBetween(5, 15);

        for ($i = 1; $i <= $questionCount; $i++) {
            $questions[] = [
                'question' => fake()->sentence() . '?',
                'choices' => [
                    fake()->word(),
                    fake()->word(),
                    fake()->word(),
                    fake()->word(),
                ],
                'correct_answer' => fake()->randomElement(['A', 'B', 'C', 'D']),
            ];
        }

        return $questions;
    }

    /**
     * Create a file-type quiz.
     */
    public function fileType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'file',
            'file_path' => 'quizzes/' . fake()->uuid() . '.pdf',
        ]);
    }

    /**
     * Create an inline-type quiz.
     */
    public function inlineType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'inline',
            'file_path' => null,
        ]);
    }

    /**
     * Create a quiz that allows guest access.
     */
    public function guestAccessible(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_guest_access' => true,
        ]);
    }

    /**
     * Create an active quiz.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }
}