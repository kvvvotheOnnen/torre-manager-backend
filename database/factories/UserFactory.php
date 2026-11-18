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

    protected static int $rutSequence = 10000000;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cuerpoRut = (string) static::$rutSequence++;

        return [
            'nombre' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => fake()->randomElement(Rol::cases())->value,
            'rut' => $cuerpoRut.'-'.self::calcularDv($cuerpoRut),
            'telefono' => fake()->numerify('+569########'),
            'telefono_emergencia' => fake()->numerify('+569########'),
            'nombre_emergencia' => fake()->name(),
            'remember_token' => Str::random(10),
        ];
    }

    private static function calcularDv(string $cuerpo): string
    {
        $suma = 0;
        $multiplicador = 2;

        for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
            $suma += (int) $cuerpo[$i] * $multiplicador;
            $multiplicador = $multiplicador === 7 ? 2 : $multiplicador + 1;
        }

        $resto = 11 - ($suma % 11);

        return match ($resto) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };
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
