<?php

namespace Database\Factories;

use App\Models\Proyecto;
use Features\Superadmin\Domain\Entities\ModuloSistema;
use Features\Superadmin\Domain\Entities\TipoCondominio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proyecto>
 */
class ProyectoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company().' Condominio',
            'descripcion' => fake()->optional()->sentence(),
            'tipo_condominio' => TipoCondominio::Torres->value,
            'modulos' => [
                ModuloSistema::Infraestructura->value,
                ModuloSistema::Paqueteria->value,
            ],
        ];
    }

    public function casas(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_condominio' => TipoCondominio::Casas->value,
        ]);
    }
}
