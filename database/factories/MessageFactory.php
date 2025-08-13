<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_id' => Chat::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->sentence(),
            'message_type' => 'text',
            'read_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 day', 'now'),
        ];
    }

    /**
     * Indicate that the message is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the message is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Indicate that the message is a text message.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'text',
            'content' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the message is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'image',
            'content' => 'image_' . $this->faker->uuid() . '.jpg',
        ]);
    }

    /**
     * Indicate that the message is a file.
     */
    public function file(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'file',
            'content' => 'document_' . $this->faker->uuid() . '.pdf',
        ]);
    }

    /**
     * Indicate that the message was sent recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the message is old.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }
}