<?php

namespace Database\Factories;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = NotificationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['enrollment', 'access_approval', 'meeting_created', 'meeting_updated', 'meeting_reminder'];
        $channels = ['email', 'whatsapp'];
        $statuses = ['pending', 'sent', 'failed'];
        $channel = $this->faker->randomElement($channels);

        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement($types),
            'channel' => $channel,
            'recipient' => $channel === 'email' ? $this->faker->email() : $this->faker->phoneNumber(),
            'subject' => $this->faker->sentence(),
            'payload' => [
                'subject' => $this->faker->sentence(),
                'message' => $this->faker->paragraph(),
                'timestamp' => now()->toISOString(),
            ],
            'status' => $this->faker->randomElement($statuses),
            'sent_at' => function (array $attributes) {
                return $attributes['status'] === 'sent' ? $this->faker->dateTimeBetween('-1 week', 'now') : null;
            },
            'error_message' => function (array $attributes) {
                return $attributes['status'] === 'failed' ? $this->faker->sentence() : null;
            },
        ];
    }

    /**
     * Indicate that the notification was sent successfully.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the notification failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'sent_at' => null,
            'error_message' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the notification is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'sent_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Set the notification type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Set the notification channel.
     */
    public function channel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }
}