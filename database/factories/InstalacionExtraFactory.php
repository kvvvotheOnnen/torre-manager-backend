<?php

namespace Database\Factories;

use App\Models\InstalacionExtra;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstalacionExtra>
 */
class InstalacionExtraFactory extends Factory
{
    protected $model = InstalacionExtra::class;

    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'nombre' => fake()->unique()->randomElement(['Quincho', 'Piscina', 'Gimnasio', 'Sala Multiuso']),
            'capacidad' => fake()->numberBetween(10, 60),
            'descripcion' => fake()->optional(0.7)->words(6, true),
            'atributos' => null,
        ];
    }
}
