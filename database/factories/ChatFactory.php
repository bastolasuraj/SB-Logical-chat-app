<?php

namespace Database\Factories;

use App\Models\Chat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chat>
 */
class ChatFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Chat::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['private', 'group']),
            'name' => $this->faker->optional(0.3)->words(3, true), // 30% chance of having a name
            'last_message_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the chat is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'private',
            'name' => null, // Private chats don't have names
        ]);
    }

    /**
     * Indicate that the chat is a group.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'group',
            'name' => $this->faker->words(3, true),
        ]);
    }

    /**
     * Indicate that the chat has recent activity.
     */
    public function withRecentActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_message_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the chat has no activity.
     */
    public function withoutActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_message_at' => null,
        ]);
    }
}