<?php

namespace Database\Seeders;

use App\Models\Agrupacion;
use App\Models\Proyecto;
use App\Models\UnidadPrincipal;
use Illuminate\Database\Seeder;

final class InfraestructuraSeeder extends Seeder
{
    public function run(): void
    {
        $proyecto = Proyecto::query()->first() ?? Proyecto::factory()->create(['nombre' => 'Condominio Demo']);

        $proyecto->instalacionesExtra()->firstOrCreate(
            ['nombre' => 'Quincho Comunitario'],
            [
                'capacidad' => 40,
                'descripcion' => 'Espacio para reuniones y eventos del condominio.',
            ],
        );

        $torre = Agrupacion::factory()->for($proyecto)->create([
            'nombre' => 'Torre A',
            'tipo' => 'torre',
        ]);

        $identificadores = [];
        foreach ([1, 2, 3] as $piso) {
            foreach ([1, 2, 3, 4] as $unidad) {
                $identificadores[] = ['identificador' => $piso.str_pad((string) $unidad, 2, '0', STR_PAD_LEFT)];
            }
        }
        $torre->unidadesPrincipales()->createMany($identificadores);

        $manzana = Agrupacion::factory()->for($proyecto)->create([
            'nombre' => 'Manzana 1',
            'tipo' => 'manzana',
        ]);

        $casas = [];
        foreach (range(1, 6) as $n) {
            $casas[] = ['identificador' => 'Casa '.$n];
        }
        $manzana->unidadesPrincipales()->createMany($casas);

        $primeraCasa = UnidadPrincipal::query()
            ->where('agrupacion_id', $manzana->getKey())
            ->where('identificador', 'Casa 1')
            ->first();

        $torre->unidadesPrincipales()->orderBy('id')->first()->unidadesAccesorias()->createMany([
            ['proyecto_id' => $proyecto->getKey(), 'identificador' => 'E-101', 'tipo' => 'estacionamiento', 'asignacion' => 'departamento'],
            ['proyecto_id' => $proyecto->getKey(), 'identificador' => 'B-101', 'tipo' => 'bodega', 'asignacion' => 'departamento'],
        ]);

        $proyecto->unidadesAccesorias()->createMany([
            ['proyecto_id' => $proyecto->getKey(), 'unidad_principal_id' => $primeraCasa?->getKey(), 'identificador' => 'E-201', 'tipo' => 'estacionamiento', 'asignacion' => 'departamento'],
            ['proyecto_id' => $proyecto->getKey(), 'identificador' => 'V-001', 'tipo' => 'bodega', 'asignacion' => 'visita'],
            ['proyecto_id' => $proyecto->getKey(), 'identificador' => 'B-201', 'tipo' => 'bodega', 'asignacion' => 'otro'],
        ]);
    }
}
