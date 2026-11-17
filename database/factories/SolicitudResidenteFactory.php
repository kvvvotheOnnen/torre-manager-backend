<?php

namespace Database\Factories;

use App\Models\Proyecto;
use App\Models\SolicitudResidente;
use App\Models\UnidadPrincipal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitudResidente>
 */
class SolicitudResidenteFactory extends Factory
{
    protected $model = SolicitudResidente::class;

    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'unidad_principal_id' => UnidadPrincipal::factory(),
            'tipo_solicitud' => fake()->randomElement(['propietario', 'arrendatario']),
            'origen' => 'seeder',
            'estado' => 'pendiente',
            'motivo_rechazo' => null,
            'datos_extra' => null,
            'solicitado_por_id' => null,
            'decidido_por_id' => null,
            'decidido_at' => null,
        ];
    }
}
