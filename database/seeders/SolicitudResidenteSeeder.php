<?php

namespace Database\Seeders;

use App\Models\Proyecto;
use App\Models\SolicitudResidente;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Database\Seeder;

final class SolicitudResidenteSeeder extends Seeder
{
    public function run(): void
    {
        $proyecto = Proyecto::query()->where('nombre', 'Vanguardia Center')->firstOrFail();
        $admin = User::query()->where('email', env('ADMIN_EMAIL', 'admin@torremanager.test'))->firstOrFail();
        $residente = User::query()->where('email', env('RESIDENTE_EMAIL', 'residente@torremanager.test'))->first();
        $propietario = User::query()->where('email', env('PROPIETARIO_EMAIL', 'propietario@torremanager.test'))->first();

        $pendientes = [
            ['unidad' => 'Unidad-803', 'tipo' => 'propietario', 'principal' => ['Ana Pérez Demo', '15000001-7', 'ana.demo@example.test', '+56960000001'], 'acompanante' => ['Luis Pérez Demo', '15000002-5', 'luis.demo@example.test', '+56960000002']],
            ['unidad' => 'Unidad-804', 'tipo' => 'arrendatario', 'principal' => ['Carla Rojas Demo', '15000003-3', 'carla.demo@example.test', '+56960000003'], 'acompanante' => null],
            ['unidad' => 'Unidad-805', 'tipo' => 'propietario', 'principal' => ['Diego Soto Demo', '15000004-1', 'diego.demo@example.test', '+56960000004'], 'acompanante' => null],
        ];

        foreach ($pendientes as $indice => $datos) {
            $unidad = $this->unidad($proyecto, $datos['unidad']);

            if ($unidad === null) {
                continue;
            }

            $solicitud = SolicitudResidente::query()->create([
                'proyecto_id' => $proyecto->getKey(),
                'unidad_principal_id' => $unidad->getKey(),
                'tipo_solicitud' => $datos['tipo'],
                'origen' => 'seeder',
                'estado' => 'pendiente',
            ]);

            $principal = $this->crearUsuarioPrincipal($datos['principal'], $datos['tipo']);

            $this->crearPersona(
                $solicitud,
                $datos['principal'],
                $datos['tipo'],
                true,
                $principal->getKey(),
            );

            if ($datos['acompanante'] !== null) {
                $this->crearPersona($solicitud, $datos['acompanante'], 'residente', false);
            }
        }

        $aprobada = $this->unidad($proyecto, 'Unidad-806');
        if ($aprobada !== null && $residente !== null) {
            $solicitud = SolicitudResidente::query()->create([
                'proyecto_id' => $proyecto->getKey(),
                'unidad_principal_id' => $aprobada->getKey(),
                'tipo_solicitud' => 'arrendatario',
                'origen' => 'seeder',
                'estado' => 'aprobada',
                'decidido_por_id' => $admin->getKey(),
                'decidido_at' => now()->subDays(3),
            ]);

            $this->crearPersona(
                $solicitud,
                ['Residente Demo Histórico', '56789012-0', 'historico.demo@example.test', '+56960000005'],
                'arrendatario',
                true,
                $residente->getKey(),
            );
        }

        $rechazada = $this->unidad($proyecto, 'Unidad-807');
        if ($rechazada !== null) {
            $solicitud = SolicitudResidente::query()->create([
                'proyecto_id' => $proyecto->getKey(),
                'unidad_principal_id' => $rechazada->getKey(),
                'tipo_solicitud' => 'propietario',
                'origen' => 'seeder',
                'estado' => 'rechazada',
                'motivo_rechazo' => 'Los datos entregados no coinciden con la unidad solicitada.',
                'decidido_por_id' => $admin->getKey(),
                'decidido_at' => now()->subDays(1),
            ]);

            $this->crearPersona(
                $solicitud,
                ['Elena Muñoz Demo', '10000013-K', 'elena.demo@example.test', '+56960000006'],
                'propietario',
                true,
                $propietario?->getKey(),
            );
        }
    }

    /** @param array{0: string, 1: string, 2: string, 3: string} $persona */
    private function crearUsuarioPrincipal(array $persona, string $tipoSolicitud): User
    {
        return User::query()->create([
            'nombre' => $persona[0],
            'email' => $persona[2],
            'password' => env('RESIDENTE_PASSWORD', 'password'),
            'rol' => $tipoSolicitud === 'propietario'
                ? Rol::Propietario->value
                : Rol::Residente->value,
            'proyecto_id' => null,
            'rut' => $persona[1],
            'telefono' => $persona[3],
            'telefono_emergencia' => '+56961111111',
            'nombre_emergencia' => 'Contacto de emergencia',
        ]);
    }

    private function unidad(Proyecto $proyecto, string $identificador): ?UnidadPrincipal
    {
        return UnidadPrincipal::query()
            ->where('identificador', $identificador)
            ->whereHas('agrupacion', fn ($query) => $query->where('proyecto_id', $proyecto->getKey()))
            ->first();
    }

    /** @param array{0: string, 1: string, 2: string, 3: string} $persona */
    private function crearPersona(
        SolicitudResidente $solicitud,
        array $persona,
        string $tipoVinculo,
        bool $esPrincipal,
        ?int $usuarioId = null,
    ): void {
        $solicitud->personas()->create([
            'usuario_id' => $usuarioId,
            'nombre' => $persona[0],
            'rut' => $persona[1],
            'email' => $persona[2],
            'telefono' => $persona[3],
            'telefono_emergencia' => '+56961111111',
            'nombre_emergencia' => 'Contacto de emergencia',
            'tipo_vinculo' => $tipoVinculo,
            'es_principal' => $esPrincipal,
        ]);
    }
}
