<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('rut', 12)->nullable()->unique()->after('nombre');
            $table->string('telefono', 20)->nullable()->after('rut');
            $table->string('telefono_emergencia', 20)->nullable()->after('telefono');
            $table->string('nombre_emergencia', 255)->nullable()->after('telefono_emergencia');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropUnique(['rut']);
            $table->dropColumn(['rut', 'telefono', 'telefono_emergencia', 'nombre_emergencia']);
        });
    }
};
