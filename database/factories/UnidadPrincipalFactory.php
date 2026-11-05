<?php

namespace Database\Factories;

use App\Models\Agrupacion;
use App\Models\UnidadPrincipal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnidadPrincipal>
 */
class UnidadPrincipalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'agrupacion_id' => Agrupacion::factory(),
            'identificador' => (string) fake()->unique()->numberBetween(101, 2505),
        ];
    }

    public function casa(): static
    {
        return $this->state(fn (array $attributes) => [
            'identificador' => 'Casa '.fake()->unique()->numberBetween(1, 80),
        ]);
    }
}
