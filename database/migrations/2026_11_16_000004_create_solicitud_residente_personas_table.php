<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_residente_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes_residente')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nombre');
            $table->string('rut', 12);
            $table->string('email');
            $table->string('telefono', 20);
            $table->string('telefono_emergencia', 20);
            $table->string('nombre_emergencia');
            $table->string('tipo_vinculo', 20);
            $table->boolean('es_principal')->default(false);
            $table->timestamps();

            $table->unique(['solicitud_id', 'email']);
            $table->unique(['solicitud_id', 'rut']);
            $table->index(['solicitud_id', 'es_principal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_residente_personas');
    }
};
