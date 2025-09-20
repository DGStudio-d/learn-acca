<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Language>
 */
class LanguageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('??'),
            'name' => fake()->unique()->sentence(2, false),
            'active' => fake()->boolean(85), // 85% chance of being active
        ];
    }

    /**
     * Create a language with a specific code and name.
     */
    public function withCodeAndName(string $code, string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
            'name' => $name,
        ]);
    }

    /**
     * Indicate that the language should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the language should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}