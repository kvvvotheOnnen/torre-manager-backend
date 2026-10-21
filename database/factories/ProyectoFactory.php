<?php

namespace Database\Factories;

use App\Models\Proyecto;
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
        ];
    }
}
