<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades_accesorias', function (Blueprint $table) {
            $table->string('asignacion')->nullable()->default(null)->change();
        });

        DB::table('unidades_accesorias')
            ->where('asignacion', 'otro')
            ->update(['asignacion' => null]);
    }

    public function down(): void
    {
        DB::table('unidades_accesorias')
            ->whereNull('asignacion')
            ->update(['asignacion' => 'otro']);

        Schema::table('unidades_accesorias', function (Blueprint $table) {
            $table->string('asignacion')->nullable(false)->default('otro')->change();
        });
    }
};
