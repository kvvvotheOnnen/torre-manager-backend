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
            $table->string('asignacion')->default('otro')->after('tipo')->index();
            $table->index(['proyecto_id', 'tipo']);
        });

        DB::table('unidades_accesorias')
            ->whereNotNull('unidad_principal_id')
            ->update(['asignacion' => 'departamento']);
    }

    public function down(): void
    {
        Schema::table('unidades_accesorias', function (Blueprint $table) {
            $table->dropIndex(['proyecto_id', 'tipo']);
            $table->dropColumn('asignacion');
        });
    }
};
