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
        Schema::table('unidades_principales', function (Blueprint $table) {
            $table->foreignId('residente_id')
                ->nullable()
                ->unique()
                ->constrained('usuarios')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unidades_principales', function (Blueprint $table) {
            $table->dropForeign(['residente_id']);
            $table->dropUnique(['residente_id']);
            $table->dropColumn('residente_id');
        });
    }
};
