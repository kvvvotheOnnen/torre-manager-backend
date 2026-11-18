<?php

namespace Database\Seeders;

use App\Models\Proyecto;
use App\Models\TrabajadorServicio;
use Features\Servicios\Domain\Entities\TipoServicio;
use Illuminate\Database\Seeder;

/**
 * Trabajadores de ejemplo para el módulo de servicios recomendados.
 *
 * Se siembran sin imagen para que el cliente muestre el recuadro "Sin imagen".
 */
final class TrabajadoresServicioSeeder extends Seeder
{
    public function run(): void
    {
        $proyecto = Proyecto::query()->orderBy('id')->first();

        if ($proyecto === null) {
            return;
        }

        $trabajadores = [
            [
                'nombre' => 'Carlos Ramírez',
                'nombre_empresa' => 'Electricidad Ramírez',
                'telefono' => '+56 9 1111 1111',
                'tipo_servicio' => TipoServicio::Electricista->value,
                'descripcion' => 'Instalaciones eléctricas, tableros y emergencias las 24 horas.',
            ],
            [
                'nombre' => 'Luis Fuentes',
                'nombre_empresa' => 'Gasfitería Fuentes',
                'telefono' => '+56 9 2222 2222',
                'tipo_servicio' => TipoServicio::Gasfiter->value,
                'descripcion' => 'Filtraciones, destapes y cambio de grifería.',
            ],
            [
                'nombre' => 'Pedro Soto',
                'nombre_empresa' => null,
                'telefono' => '+56 9 3333 3333',
                'tipo_servicio' => TipoServicio::Maestro->value,
                'descripcion' => 'Reparaciones generales, pintura y terminaciones.',
            ],
            [
                'nombre' => 'María Díaz',
                'nombre_empresa' => 'Cerrajería Díaz',
                'telefono' => '+56 9 4444 4444',
                'tipo_servicio' => TipoServicio::Cerrajero->value,
                'descripcion' => 'Apertura de puertas, cambio de cerraduras y llaves.',
            ],
            [
                'nombre' => 'Andrés Vera',
                'nombre_empresa' => 'Soporte Vera',
                'telefono' => '+56 9 5555 5555',
                'tipo_servicio' => TipoServicio::TecnicoInformatico->value,
                'descripcion' => 'Redes, computadores e instalación de cámaras.',
            ],
            [
                'nombre' => 'Sofía Torres',
                'nombre_empresa' => 'Servicios Torres',
                'telefono' => '+56 9 6666 6666',
                'tipo_servicio' => TipoServicio::Otro->value,
                'descripcion' => 'Fumigación y limpieza profunda de departamentos.',
            ],
        ];

        foreach ($trabajadores as $trabajador) {
            TrabajadorServicio::query()->updateOrCreate(
                [
                    'proyecto_id' => $proyecto->getKey(),
                    'nombre' => $trabajador['nombre'],
                ],
                $trabajador,
            );
        }
    }
}
