<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mascotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_principal_id')->constrained('unidades_principales')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('especie', 30);
            $table->string('raza', 100);
            $table->string('microchip', 15)->nullable();
            $table->string('foto_url')->nullable();
            $table->timestamps();

            $table->index('unidad_principal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mascotas');
    }
};
