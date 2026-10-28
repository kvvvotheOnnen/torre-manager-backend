<?php

namespace Database\Factories;

use App\Models\Proyecto;
use App\Models\UnidadAccesoria;
use Features\Infraestructura\Domain\Entities\TipoUnidadAccesoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnidadAccesoria>
 */
class UnidadAccesoriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'unidad_principal_id' => null,
            'identificador' => (string) fake()->unique()->numberBetween(1, 200),
            'tipo' => TipoUnidadAccesoria::Estacionamiento->value,
            'asignacion' => null,
        ];
    }

    public function bodega(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => TipoUnidadAccesoria::Bodega->value,
        ]);
    }
}
