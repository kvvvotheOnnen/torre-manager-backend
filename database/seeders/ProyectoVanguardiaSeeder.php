<?php

namespace Database\Seeders;

use App\Models\Proyecto;
use App\Models\UnidadPrincipal;
use Illuminate\Database\Seeder;

final class ProyectoVanguardiaSeeder extends Seeder
{
    public function run(): void
    {
        $proyecto = Proyecto::query()->create([
            'nombre' => 'Vanguardia Center',
            'descripcion' => 'Edifico de residentes',
            'tipo_condominio' => 'edificio',
            'modulos' => ['infraestructura'],
            'estado' => 'completo',
        ]);

        $edificio = $proyecto->agrupaciones()->create([
            'nombre' => 'Edificio',
            'tipo' => 'edificio',
        ]);

        $unidades = [];
        for ($piso = 8; $piso <= 26; $piso++) {
            for ($numero = 1; $numero <= 16; $numero++) {
                $unidades[] = [
                    'identificador' => 'Edificio-'.$piso.str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
                ];
            }
        }
        $edificio->unidadesPrincipales()->createMany($unidades);

        $proyecto->instalacionesExtra()->create([
            'nombre' => 'Quincho',
            'capacidad' => 25,
            'descripcion' => 'Quincho para arriendo en el edificio',
        ]);

        $accesorias = [];
        for ($numero = 1; $numero <= 50; $numero++) {
            $accesorias[] = [
                'identificador' => 'B-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                'tipo' => 'bodega',
                'asignacion' => null,
                'unidad_principal_id' => null,
            ];
        }
        for ($numero = 1; $numero <= 100; $numero++) {
            $accesorias[] = [
                'identificador' => 'E-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                'tipo' => 'estacionamiento',
                'asignacion' => null,
                'unidad_principal_id' => null,
            ];
        }
        $proyecto->unidadesAccesorias()->createMany($accesorias);

        $unidadesAsignadas = UnidadPrincipal::query()
            ->whereHas(
                'agrupacion',
                fn ($query) => $query->where('proyecto_id', $proyecto->getKey()),
            )
            ->whereIn('identificador', [
                'Edificio-801',
                'Edificio-802',
                'Edificio-803',
                'Edificio-804',
            ])
            ->pluck('id', 'identificador');

        $asignaciones = [
            ['identificador' => 'B-001', 'tipo' => 'bodega', 'unidad' => 'Edificio-801'],
            ['identificador' => 'B-002', 'tipo' => 'bodega', 'unidad' => 'Edificio-802'],
            ['identificador' => 'B-003', 'tipo' => 'bodega', 'unidad' => 'Edificio-803'],
            ['identificador' => 'E-001', 'tipo' => 'estacionamiento', 'unidad' => 'Edificio-801'],
            ['identificador' => 'E-002', 'tipo' => 'estacionamiento', 'unidad' => 'Edificio-802'],
            ['identificador' => 'E-003', 'tipo' => 'estacionamiento', 'unidad' => 'Edificio-803'],
            ['identificador' => 'E-004', 'tipo' => 'estacionamiento', 'unidad' => 'Edificio-804'],
        ];

        foreach ($asignaciones as $asignacion) {
            $proyecto->unidadesAccesorias()
                ->where('tipo', $asignacion['tipo'])
                ->where('identificador', $asignacion['identificador'])
                ->update([
                    'asignacion' => 'departamento',
                    'unidad_principal_id' => $unidadesAsignadas[$asignacion['unidad']],
                ]);
        }
    }
}
