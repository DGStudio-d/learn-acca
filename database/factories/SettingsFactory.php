<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settings>
 */
class SettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $settings = [
            [
                'key' => 'allow_guest_languages',
                'type' => 'boolean',
                'description' => 'Allow guests to view available languages',
            ],
            [
                'key' => 'allow_guest_teachers',
                'type' => 'boolean',
                'description' => 'Allow guests to view teacher profiles',
            ],
            [
                'key' => 'allow_guest_quizzes',
                'type' => 'boolean',
                'description' => 'Allow guests to attempt quizzes',
            ],
            [
                'key' => 'max_quiz_attempts',
                'type' => 'integer',
                'description' => 'Maximum number of quiz attempts per student',
            ],
            [
                'key' => 'notification_retry_limit',
                'type' => 'integer',
                'description' => 'Maximum number of notification retry attempts',
            ],
        ];

        $setting = fake()->randomElement($settings);
        
        return [
            'key' => $setting['key'],
            'value' => $this->generateValue($setting['type']),
            'type' => $setting['type'],
            'description' => $setting['description'],
        ];
    }

    /**
     * Generate a value based on the type.
     */
    private function generateValue(string $type): string
    {
        return match ($type) {
            'boolean' => fake()->boolean() ? '1' : '0',
            'integer' => (string) fake()->numberBetween(1, 10),
            'float' => (string) fake()->randomFloat(2, 0, 100),
            'array', 'json' => json_encode([fake()->word(), fake()->word()]),
            default => fake()->sentence(),
        };
    }

    /**
     * Create a guest access setting.
     */
    public function guestAccess(string $feature, bool $allowed = true): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => "allow_guest_{$feature}",
            'value' => $allowed ? '1' : '0',
            'type' => 'boolean',
            'description' => "Allow guests to access {$feature}",
        ]);
    }

    /**
     * Create a boolean setting.
     */
    public function boolean(string $key, bool $value, string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => $value ? '1' : '0',
            'type' => 'boolean',
            'description' => $description ?? "Boolean setting for {$key}",
        ]);
    }

    /**
     * Create an integer setting.
     */
    public function integer(string $key, int $value, string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => (string) $value,
            'type' => 'integer',
            'description' => $description ?? "Integer setting for {$key}",
        ]);
    }

    /**
     * Create a string setting.
     */
    public function string(string $key, string $value, string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => $value,
            'type' => 'string',
            'description' => $description ?? "String setting for {$key}",
        ]);
    }
}