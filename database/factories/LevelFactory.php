<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Level>
 */
class LevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $levels = [
            'Beginner',
            'Elementary',
            'Intermediate',
            'Upper Intermediate',
            'Advanced',
            'Proficient',
        ];

        return [
            'language_id' => Language::factory(),
            'name' => fake()->randomElement($levels),
            'order' => fake()->numberBetween(1, 6),
        ];
    }

    /**
     * Set a specific order for the level.
     */
    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }

    /**
     * Set a specific language for the level.
     */
    public function forLanguage(Language $language): static
    {
        return $this->state(fn (array $attributes) => [
            'language_id' => $language->id,
        ]);
    }
}