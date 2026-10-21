<?php

namespace Database\Factories;

use App\Models\Agrupacion;
use App\Models\Proyecto;
use Features\Infraestructura\Domain\Entities\TipoAgrupacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agrupacion>
 */
class AgrupacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'nombre' => 'Torre '.fake()->unique()->randomLetter(),
            'tipo' => TipoAgrupacion::Torre->value,
        ];
    }

    public function manzana(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Manzana '.fake()->unique()->randomLetter(),
            'tipo' => TipoAgrupacion::Manzana->value,
        ]);
    }
}
