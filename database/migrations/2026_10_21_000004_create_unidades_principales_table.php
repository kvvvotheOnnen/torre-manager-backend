<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_principales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agrupacion_id')->constrained('agrupaciones')->cascadeOnDelete();
            $table->string('identificador');
            $table->timestamps();

            $table->unique(['agrupacion_id', 'identificador']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_principales');
    }
};
