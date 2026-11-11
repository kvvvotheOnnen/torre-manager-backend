<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paquetes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('unidad_principal_id')->nullable()->constrained('unidades_principales')->nullOnDelete();
            $table->foreignId('residente_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('descripcion');
            $table->string('remitente')->nullable();
            $table->string('foto_path')->nullable();
            $table->timestamp('fecha_recepcion');
            $table->string('estado')->default('pendiente');
            $table->timestamp('fecha_retiro')->nullable();
            $table->foreignId('registrado_por')->constrained('usuarios')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paquetes');
    }
};
