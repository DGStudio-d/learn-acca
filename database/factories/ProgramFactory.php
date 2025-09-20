<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'language_id' => Language::factory(),
            'level_id' => Level::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'active' => fake()->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the program should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the program should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Set specific language and level for the program.
     */
    public function forLanguageAndLevel(Language $language, Level $level): static
    {
        return $this->state(fn (array $attributes) => [
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);
    }
}