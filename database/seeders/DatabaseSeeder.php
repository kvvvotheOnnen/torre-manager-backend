<?php

namespace Database\Seeders;

use App\Models\Paquete;
use App\Models\Proyecto;
use App\Models\ProyectoUsuario;
use App\Models\UnidadPrincipal;
use App\Models\UnidadUsuario;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProyectoVanguardiaSeeder::class);

        $proyecto = Proyecto::query()
            ->where('nombre', 'Vanguardia Center')
            ->firstOrFail();

        User::factory()->create([
            'nombre' => 'Superadministrador Demo',
            'email' => env('SUPERADMIN_EMAIL', 'superadmin@torremanager.test'),
            'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'password')),
            'rol' => Rol::Superadmin->value,
            'proyecto_id' => null,
            'rut' => '11111111-1',
            'telefono' => '+56911111111',
            'telefono_emergencia' => '+56911111112',
            'nombre_emergencia' => 'Contacto Superadmin',
        ]);

        $admin = User::factory()->administrador()->create([
            'nombre' => 'Administrador Demo',
            'email' => env('ADMIN_EMAIL', 'admin@torremanager.test'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'proyecto_id' => $proyecto->getKey(),
            'rut' => '22222222-2',
            'telefono' => '+56922222222',
            'telefono_emergencia' => '+56922222223',
            'nombre_emergencia' => 'Contacto Administrador',
        ]);
        $this->asegurarMembresia($admin, $proyecto->getKey(), Rol::Administrador);
        $admin->proyectos()->syncWithoutDetaching([
            $proyecto->getKey() => ['rol' => Rol::Administrador->value, 'estado' => 'activo'],
        ]);
        DB::table('administrador_proyecto')->insertOrIgnore([
            'proyecto_id' => $proyecto->getKey(),
            'usuario_id' => $admin->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $residente = User::factory()->create([
            'nombre' => 'Residente Demo',
            'email' => env('RESIDENTE_EMAIL', 'residente@torremanager.test'),
            'password' => Hash::make(env('RESIDENTE_PASSWORD', 'password')),
            'rol' => Rol::Residente->value,
            'proyecto_id' => $proyecto->getKey(),
            'rut' => '12345678-5',
            'telefono' => '+56933333333',
            'telefono_emergencia' => '+56933333334',
            'nombre_emergencia' => 'Contacto Residente',
        ]);
        $this->asegurarMembresia($residente, $proyecto->getKey(), Rol::Residente);

        $propietario = User::factory()->create([
            'nombre' => 'Propietario Demo',
            'email' => env('PROPIETARIO_EMAIL', 'propietario@torremanager.test'),
            'password' => Hash::make(env('PROPIETARIO_PASSWORD', 'password')),
            'rol' => Rol::Propietario->value,
            'proyecto_id' => $proyecto->getKey(),
            'rut' => '23456789-6',
            'telefono' => '+56944444444',
            'telefono_emergencia' => '+56944444445',
            'nombre_emergencia' => 'Contacto Propietario',
        ]);
        $this->asegurarMembresia($propietario, $proyecto->getKey(), Rol::Propietario);

        // El residente ocupa la unidad Edificio-801 y el propietario demo es su dueño.
        $unidad = UnidadPrincipal::query()
            ->where('identificador', 'Edificio-801')
            ->whereHas('agrupacion', fn ($q) => $q->where('proyecto_id', $proyecto->getKey()))
            ->first();

        $unidad?->update(['residente_id' => $residente->getKey()]);

        if ($unidad !== null) {
            UnidadUsuario::query()->create([
                'unidad_principal_id' => $unidad->getKey(),
                'usuario_id' => $propietario->getKey(),
                'tipo_vinculo' => 'propietario',
                'fecha_inicio' => now()->subYear()->toDateString(),
                'es_actual' => true,
            ]);
            UnidadUsuario::query()->create([
                'unidad_principal_id' => $unidad->getKey(),
                'usuario_id' => $residente->getKey(),
                'tipo_vinculo' => 'arrendatario',
                'fecha_inicio' => now()->subMonths(6)->toDateString(),
                'es_actual' => true,
            ]);
        }

        $unidad802 = UnidadPrincipal::query()
            ->where('identificador', 'Edificio-802')
            ->whereHas('agrupacion', fn ($q) => $q->where('proyecto_id', $proyecto->getKey()))
            ->first();

        if ($unidad802 !== null) {
            UnidadUsuario::query()->create([
                'unidad_principal_id' => $unidad802->getKey(),
                'usuario_id' => $propietario->getKey(),
                'tipo_vinculo' => 'propietario',
                'fecha_inicio' => now()->subYear()->toDateString(),
                'es_actual' => true,
            ]);
        }

        // Paquetes demo para el residente
        Paquete::query()->create([
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad?->getKey(),
            'residente_id' => $residente->getKey(),
            'descripcion' => 'Paquete de Amazon',
            'remitente' => 'Amazon',
            'fecha_recepcion' => now()->subDays(2),
            'estado' => 'pendiente',
            'registrado_por' => $admin->getKey() ?? 1,
        ]);
        Paquete::query()->create([
            'proyecto_id' => $proyecto->getKey(),
            'unidad_principal_id' => $unidad?->getKey(),
            'residente_id' => $residente->getKey(),
            'descripcion' => 'Sobre de MercadoLibre',
            'remitente' => 'MercadoLibre',
            'fecha_recepcion' => now()->subDays(5),
            'estado' => 'retirado',
            'fecha_retiro' => now()->subDays(4),
            'registrado_por' => $admin->getKey() ?? 1,
        ]);

        $conserje = User::factory()->create([
            'nombre' => 'Conserje Demo',
            'email' => env('CONSERJE_EMAIL', 'conserje@torremanager.test'),
            'password' => Hash::make(env('CONSERJE_PASSWORD', 'password')),
            'rol' => Rol::Conserje->value,
            'proyecto_id' => $proyecto->getKey(),
            'rut' => '34567890-5',
            'telefono' => '+56955555555',
            'telefono_emergencia' => '+56955555556',
            'nombre_emergencia' => 'Contacto Conserje',
        ]);
        $this->asegurarMembresia($conserje, $proyecto->getKey(), Rol::Conserje);
    }

    private function asegurarMembresia(User $usuario, int|string $proyectoId, Rol $rol): void
    {
        ProyectoUsuario::query()->firstOrCreate(
            [
                'proyecto_id' => $proyectoId,
                'usuario_id' => $usuario->getKey(),
            ],
            [
                'rol' => $rol->value,
                'estado' => 'activo',
            ],
        );
    }
}
