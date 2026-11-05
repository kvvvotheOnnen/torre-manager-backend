<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->string('tipo_condominio')->default('torres')->after('descripcion')->index();
            $table->json('modulos')->nullable()->after('tipo_condominio');
        });
    }

    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn(['tipo_condominio', 'modulos']);
        });
    }
};
