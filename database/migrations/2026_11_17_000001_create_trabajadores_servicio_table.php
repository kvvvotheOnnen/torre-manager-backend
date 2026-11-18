<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajadores_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('nombre_empresa')->nullable();
            $table->string('telefono');
            $table->string('tipo_servicio');
            $table->text('descripcion')->nullable();
            $table->string('foto_path')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->index(['proyecto_id', 'tipo_servicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajadores_servicio');
    }
};
