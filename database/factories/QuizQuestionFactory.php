<?php

namespace Database\Factories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $choices = [
            fake()->word(),
            fake()->word(),
            fake()->word(),
            fake()->word(),
        ];

        return [
            'quiz_id' => Quiz::factory(),
            'question' => fake()->sentence() . '?',
            'choices' => $choices,
            'correct_answer' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'order' => fake()->numberBetween(1, 20),
        ];
    }

    /**
     * Set a specific order for the question.
     */
    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }

    /**
     * Set a specific quiz for the question.
     */
    public function forQuiz(Quiz $quiz): static
    {
        return $this->state(fn (array $attributes) => [
            'quiz_id' => $quiz->id,
        ]);
    }

    /**
     * Create a multiple choice question with specific choices.
     */
    public function withChoices(array $choices, string $correctAnswer): static
    {
        return $this->state(fn (array $attributes) => [
            'choices' => $choices,
            'correct_answer' => $correctAnswer,
        ]);
    }
}