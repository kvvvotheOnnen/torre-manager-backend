<?php

namespace Database\Factories;

use App\Models\Paquete;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paquete>
 */
class PaqueteFactory extends Factory
{
    protected $model = Paquete::class;

    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'unidad_principal_id' => null,
            'residente_id' => null,
            'descripcion' => fake()->sentence(4),
            'remitente' => fake()->company(),
            'foto_path' => null,
            'fecha_recepcion' => fake()->dateTimeBetween('-7 days', 'now'),
            'estado' => 'pendiente',
            'fecha_retiro' => null,
            'registrado_por' => User::factory(),
        ];
    }

    public function retirado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'retirado',
            'fecha_retiro' => fake()->dateTimeBetween('-3 days', 'now'),
        ]);
    }
}
