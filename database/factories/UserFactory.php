<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
        $username = fake()->unique()->userName();

        return [
            'username' => preg_replace('/[^a-zA-Z0-9_]/', '_', $username),
            'display_name' => fake()->name(),
            'password' => static::$password ??= Hash::make('password123'),
            'avatar' => 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed='.urlencode($username),
            'status_message' => 'Disponible en canal anónimo',
            'is_online' => false,
            'last_seen_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }
}
