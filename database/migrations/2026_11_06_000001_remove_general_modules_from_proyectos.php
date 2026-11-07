<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los módulos de paquetería, instalaciones extra, gastos comunes y control
     * de acceso pasaron a ser globales y ya no se persisten por proyecto.
     */
    public function up(): void
    {
        $globales = [
            'paqueteria',
            'quincho',
            'gastos_comunes',
            'control_acceso',
        ];

        DB::table('proyectos')
            ->orderBy('id')
            ->get(['id', 'modulos'])
            ->each(function (object $proyecto) use ($globales): void {
                $modulos = json_decode($proyecto->modulos ?? '[]', true);

                if (! is_array($modulos)) {
                    $modulos = [];
                }

                $filtrados = array_values(array_filter(
                    $modulos,
                    static fn (mixed $modulo) => is_string($modulo)
                        && ! in_array($modulo, $globales, true),
                ));

                DB::table('proyectos')
                    ->where('id', $proyecto->id)
                    ->update(['modulos' => json_encode($filtrados)]);
            });
    }

    public function down(): void
    {
        // No se restauran módulos globales eliminados de proyectos.
    }
};
