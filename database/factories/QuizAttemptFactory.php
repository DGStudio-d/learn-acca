<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $score = fake()->numberBetween(0, 10);
        $passScore = fake()->numberBetween(6, 8);
        
        return [
            'quiz_id' => Quiz::factory(),
            'student_id' => User::factory()->state(['role' => 'student']),
            'answers' => $this->generateSampleAnswers(),
            'score' => $score,
            'passed' => $score >= $passScore,
            'submitted_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Generate sample answers.
     */
    private function generateSampleAnswers(): array
    {
        $answers = [];
        $questionCount = fake()->numberBetween(5, 15);

        for ($i = 1; $i <= $questionCount; $i++) {
            $answers["question_{$i}"] = fake()->randomElement(['A', 'B', 'C', 'D']);
        }

        return $answers;
    }

    /**
     * Create a guest attempt (no student_id).
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => null,
        ]);
    }

    /**
     * Create a passed attempt.
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(8, 10),
            'passed' => true,
        ]);
    }

    /**
     * Create a failed attempt.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(0, 5),
            'passed' => false,
        ]);
    }

    /**
     * Set specific quiz and student.
     */
    public function forQuizAndStudent(Quiz $quiz, ?User $student = null): static
    {
        return $this->state(fn (array $attributes) => [
            'quiz_id' => $quiz->id,
            'student_id' => $student?->id,
        ]);
    }
}