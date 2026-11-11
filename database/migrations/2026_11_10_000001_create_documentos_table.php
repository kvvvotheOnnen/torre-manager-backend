<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')
                ->unique()
                ->constrained('proyectos')
                ->cascadeOnDelete();
            $table->foreignId('subido_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->string('nombre_original');
            $table->string('ruta');
            $table->unsignedInteger('paginas')->default(0);
            $table->longText('texto');
            $table->unsignedInteger('tamano_tokens')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
