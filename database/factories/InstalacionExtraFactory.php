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
        $tipos = ['quincho', 'piscina', 'gimnasio', 'sala_multiuso'];

        return [
            'proyecto_id' => Proyecto::factory(),
            'nombre' => fake()->unique()->randomElement(['Quincho', 'Piscina', 'Gimnasio', 'Sala Multiuso']),
            'tipo' => fake()->randomElement($tipos),
            'atributos' => null,
        ];
    }
}
