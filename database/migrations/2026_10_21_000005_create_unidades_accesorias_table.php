<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_accesorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('unidad_principal_id')->nullable()->constrained('unidades_principales')->nullOnDelete();
            $table->string('identificador');
            $table->string('tipo')->index();
            $table->timestamps();

            $table->unique(['proyecto_id', 'tipo', 'identificador']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_accesorias');
    }
};
