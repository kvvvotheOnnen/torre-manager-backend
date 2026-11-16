<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidad_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_principal_id')->constrained('unidades_principales')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('tipo_vinculo')->index();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('es_actual')->default(true)->index();
            $table->timestamps();

            $table->unique(
                ['unidad_principal_id', 'usuario_id', 'tipo_vinculo', 'fecha_inicio'],
                'unidad_usuario_vinculo_unico'
            );
            $table->index(['usuario_id', 'es_actual']);
            $table->index(['unidad_principal_id', 'tipo_vinculo', 'es_actual'], 'unidad_usuario_tipo_actual_index');
        });

        $driver = DB::getDriverName();
        $true = $driver === 'pgsql' ? 'true' : '1';

        DB::statement(
            "CREATE UNIQUE INDEX unidad_usuario_propietario_actual_unique
             ON unidad_usuario (unidad_principal_id)
             WHERE tipo_vinculo = 'propietario' AND es_actual = {$true}"
        );

        DB::statement(
            "CREATE UNIQUE INDEX unidad_usuario_arrendatario_actual_unique
             ON unidad_usuario (unidad_principal_id)
             WHERE tipo_vinculo = 'arrendatario' AND es_actual = {$true}"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('unidad_usuario');
    }
};
