<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instalaciones_extra', function (Blueprint $table) {
            $table->unsignedInteger('capacidad')->default(0);
            $table->string('descripcion', 100)->nullable();
        });

        DB::table('instalaciones_extra')
            ->where('capacidad', '<=', 0)
            ->update(['capacidad' => 1]);
    }

    public function down(): void
    {
        Schema::table('instalaciones_extra', function (Blueprint $table) {
            $table->dropColumn(['capacidad', 'descripcion']);
        });
    }
};
