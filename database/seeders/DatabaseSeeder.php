<?php

namespace Database\Seeders;

use App\Models\Proyecto;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $proyecto = Proyecto::factory()->create(['nombre' => 'Condominio Demo']);

        User::factory()->create([
            'nombre' => 'Superadministrador Demo',
            'email' => env('SUPERADMIN_EMAIL', 'superadmin@torremanager.test'),
            'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'password')),
            'rol' => Rol::Superadmin->value,
            'proyecto_id' => null,
        ]);

        User::factory()->administrador()->create([
            'nombre' => 'Administrador Demo',
            'email' => env('ADMIN_EMAIL', 'admin@torremanager.test'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'proyecto_id' => $proyecto->getKey(),
        ]);

        User::factory()->create([
            'nombre' => 'Residente Demo',
            'email' => env('RESIDENTE_EMAIL', 'residente@torremanager.test'),
            'password' => Hash::make(env('RESIDENTE_PASSWORD', 'password')),
            'rol' => Rol::Residente->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        User::factory()->create([
            'nombre' => 'Conserje Demo',
            'email' => env('CONSERJE_EMAIL', 'conserje@torremanager.test'),
            'password' => Hash::make(env('CONSERJE_PASSWORD', 'password')),
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyecto->getKey(),
        ]);

        $this->call(InfraestructuraSeeder::class);
    }
}
