<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paquetes', function (Blueprint $table): void {
            $table->string('retirado_por_nombre')->nullable()->after('fecha_retiro');
        });
    }

    public function down(): void
    {
        Schema::table('paquetes', function (Blueprint $table): void {
            $table->dropColumn('retirado_por_nombre');
        });
    }
};
