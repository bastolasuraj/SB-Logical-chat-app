<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'avatar' => null, // Will be set when user uploads avatar
            'last_seen_at' => fake()->optional(0.8)->dateTimeBetween('-1 hour', 'now'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is currently online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_seen_at' => fake()->dateTimeBetween('-1 day', '-10 minutes'),
        ]);
    }

    /**
     * Create a user with an avatar.
     */
    public function withAvatar(): static
    {
        return $this->state(fn (array $attributes) => [
            'avatar' => fake()->uuid() . '.jpg',
        ]);
    }
}
