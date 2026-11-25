<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_residente_mascotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes_residente')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('especie', 30);
            $table->string('raza', 100);
            $table->string('microchip', 15)->nullable();
            $table->string('foto_url')->nullable();
            $table->timestamps();

            $table->index('solicitud_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_residente_mascotas');
    }
};
