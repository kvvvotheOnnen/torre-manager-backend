<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_residente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('unidad_principal_id')->constrained('unidades_principales')->cascadeOnDelete();
            $table->string('tipo_solicitud', 20);
            $table->string('origen', 30)->default('autoregistro');
            $table->string('estado', 20)->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->json('datos_extra')->nullable();
            $table->foreignId('solicitado_por_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('decidido_por_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('decidido_at')->nullable();
            $table->timestamps();

            $table->index(['proyecto_id', 'estado', 'created_at']);
            $table->index(['unidad_principal_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_residente');
    }
};
