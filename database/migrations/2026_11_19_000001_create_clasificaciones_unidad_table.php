<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clasificaciones_unidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->timestamps();

            $table->unique(['proyecto_id', 'nombre']);
        });

        Schema::table('unidades_principales', function (Blueprint $table) {
            $table->foreignId('clasificacion_id')
                ->nullable()
                ->after('residente_id')
                ->constrained('clasificaciones_unidad')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unidades_principales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clasificacion_id');
        });

        Schema::dropIfExists('clasificaciones_unidad');
    }
};
