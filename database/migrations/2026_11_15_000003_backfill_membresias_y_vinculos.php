<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('administrador_proyecto')
            ->orderBy('id')
            ->chunkById(200, function ($filas) use ($now): void {
                foreach ($filas as $fila) {
                    DB::table('proyecto_usuario')->insertOrIgnore([
                        'proyecto_id' => $fila->proyecto_id,
                        'usuario_id' => $fila->usuario_id,
                        'rol' => 'administrador',
                        'estado' => 'activo',
                        'created_at' => $fila->created_at ?? $now,
                        'updated_at' => $fila->updated_at ?? $now,
                    ]);
                }
            });

        $rolesProyecto = ['administrador', 'residente', 'conserje', 'propietario'];

        DB::table('usuarios')
            ->whereNotNull('proyecto_id')
            ->whereIn('rol', $rolesProyecto)
            ->orderBy('id')
            ->chunkById(200, function ($usuarios) use ($now): void {
                foreach ($usuarios as $usuario) {
                    DB::table('proyecto_usuario')->insertOrIgnore([
                        'proyecto_id' => $usuario->proyecto_id,
                        'usuario_id' => $usuario->id,
                        'rol' => $usuario->rol,
                        'estado' => 'activo',
                        'created_at' => $usuario->created_at ?? $now,
                        'updated_at' => $usuario->updated_at ?? $now,
                    ]);
                }
            });

        DB::table('unidades_principales')
            ->whereNotNull('residente_id')
            ->orderBy('id')
            ->chunkById(200, function ($unidades) use ($now): void {
                foreach ($unidades as $unidad) {
                    $fecha = $unidad->created_at !== null
                        ? substr((string) $unidad->created_at, 0, 10)
                        : $now->toDateString();

                    $existe = DB::table('unidad_usuario')
                        ->where('unidad_principal_id', $unidad->id)
                        ->where('usuario_id', $unidad->residente_id)
                        ->where('tipo_vinculo', 'residente')
                        ->where('es_actual', true)
                        ->exists();

                    if ($existe) {
                        continue;
                    }

                    DB::table('unidad_usuario')->insert([
                        'unidad_principal_id' => $unidad->id,
                        'usuario_id' => $unidad->residente_id,
                        'tipo_vinculo' => 'residente',
                        'fecha_inicio' => $fecha,
                        'fecha_fin' => null,
                        'es_actual' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('unidad_usuario')->truncate();
        DB::table('proyecto_usuario')->truncate();
    }
};
