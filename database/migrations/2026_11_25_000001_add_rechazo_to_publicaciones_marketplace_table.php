<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publicaciones_marketplace', function (Blueprint $table): void {
            $table->string('motivo_rechazo')->nullable()->after('estado');
            $table->timestamp('rechazado_en')->nullable()->after('motivo_rechazo');
        });
    }

    public function down(): void
    {
        Schema::table('publicaciones_marketplace', function (Blueprint $table): void {
            $table->dropColumn(['motivo_rechazo', 'rechazado_en']);
        });
    }
};
