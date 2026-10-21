<?php

namespace Database\Factories;

use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
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
        return [
            'nombre' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => fake()->randomElement(Rol::cases())->value,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model has the Administrador role.
     */
    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Administrador->value,
        ]);
    }

    /**
     * Indicate that the model has the Superadmin role (tenant-agnostic).
     */
    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Superadmin->value,
            'proyecto_id' => null,
        ]);
    }
}
