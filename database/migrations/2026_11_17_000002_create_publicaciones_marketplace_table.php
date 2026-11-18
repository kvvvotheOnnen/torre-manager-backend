<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicaciones_marketplace', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('titulo', 120);
            $table->text('descripcion');
            $table->integer('precio');
            $table->string('categoria');
            $table->string('condicion');
            $table->string('estado')->default('disponible');
            $table->string('foto_path')->nullable();
            $table->timestamp('vendido_en')->nullable();
            $table->text('motivo_eliminacion')->nullable();
            $table->foreignId('eliminado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('eliminado_por_rol', 20)->nullable();
            $table->timestamp('eliminado_en')->nullable();
            $table->timestamps();

            $table->index(['proyecto_id', 'estado']);
            $table->index(['proyecto_id', 'categoria']);
            $table->index(['usuario_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicaciones_marketplace');
    }
};
