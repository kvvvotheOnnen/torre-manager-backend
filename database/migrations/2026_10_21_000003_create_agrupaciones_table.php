<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agrupaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('tipo')->index();
            $table->timestamps();

            $table->unique(['proyecto_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agrupaciones');
    }
};
