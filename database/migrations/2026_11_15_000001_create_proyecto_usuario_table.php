<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyecto_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('rol')->default('administrador')->index();
            $table->string('estado')->default('activo')->index();
            $table->timestamps();

            $table->unique(['proyecto_id', 'usuario_id']);
            $table->index(['usuario_id', 'estado']);
            $table->index(['proyecto_id', 'rol', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto_usuario');
    }
};
