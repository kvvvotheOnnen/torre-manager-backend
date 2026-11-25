<?php

namespace Features\SolicitudesResidente\Data\Repositories;

use App\Models\Proyecto;
use App\Models\SolicitudResidente as SolicitudResidenteModel;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Features\SolicitudesResidente\Domain\Entities\DatosRegistroResidente;
use Features\SolicitudesResidente\Domain\Entities\EstadoSolicitud;
use Features\SolicitudesResidente\Domain\Entities\MascotaRegistro;
use Features\SolicitudesResidente\Domain\Entities\OrigenSolicitud;
use Features\SolicitudesResidente\Domain\Entities\ProyectoRegistro;
use Features\SolicitudesResidente\Domain\Entities\UnidadRegistro;
use Features\SolicitudesResidente\Domain\Repositories\RegistroResidenteRepositoryInterface;
use Features\Superadmin\Domain\Entities\EstadoProyecto;
use Illuminate\Support\Facades\DB;

final class RegistroResidenteRepository implements RegistroResidenteRepositoryInterface
{
    public function listarProyectosDisponibles(): array
    {
        return Proyecto::query()
            ->where('estado', EstadoProyecto::Completo->value)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Proyecto $proyecto) => new ProyectoRegistro(
                id: (string) $proyecto->getKey(),
                nombre: $proyecto->nombre,
            ))
            ->all();
    }

    public function listarUnidades(string $proyectoId): array
    {
        $proyecto = Proyecto::query()
            ->with(['unidadesPrincipales.agrupacion'])
            ->find($proyectoId);

        if ($proyecto === null) {
            return [];
        }

        return $proyecto->unidadesPrincipales
            ->map(fn ($unidad) => new UnidadRegistro(
                id: (string) $unidad->getKey(),
                identificador: (string) $unidad->identificador,
                agrupacion: (string) ($unidad->agrupacion?->nombre ?? ''),
            ))
            ->sortBy(fn (UnidadRegistro $unidad) => $unidad->identificador)
            ->values()
            ->all();
    }

    public function proyectoDisponible(string $proyectoId): bool
    {
        return Proyecto::query()
            ->whereKey($proyectoId)
            ->where('estado', EstadoProyecto::Completo->value)
            ->exists();
    }

    public function unidadPerteneceAProyecto(string $unidadPrincipalId, string $proyectoId): bool
    {
        return DB::table('unidades_principales')
            ->join('agrupaciones', 'agrupaciones.id', '=', 'unidades_principales.agrupacion_id')
            ->where('unidades_principales.id', $unidadPrincipalId)
            ->where('agrupaciones.proyecto_id', $proyectoId)
            ->exists();
    }

    public function existeEmail(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    public function existeRut(string $rut): bool
    {
        return User::query()->where('rut', $rut)->exists();
    }

    public function crearCuentaYSolicitud(DatosRegistroResidente $datos): string
    {
        return DB::transaction(function () use ($datos): string {
            $principal = $datos->principal();

            $usuario = User::query()->create([
                'nombre' => $principal->nombre(),
                'email' => $principal->email(),
                'password' => $datos->password(),
                'rol' => $principal->tipoVinculo() === \Features\Acceso\Domain\Entities\TipoVinculo::Propietario
                    ? Rol::Propietario->value
                    : Rol::Residente->value,
                'proyecto_id' => null,
                'rut' => $principal->rut(),
                'telefono' => $principal->telefono(),
                'telefono_emergencia' => $principal->telefonoEmergencia(),
                'nombre_emergencia' => $principal->nombreEmergencia(),
            ]);

            $solicitud = SolicitudResidenteModel::query()->create([
                'proyecto_id' => $datos->proyectoId(),
                'unidad_principal_id' => $datos->unidadPrincipalId(),
                'tipo_solicitud' => $principal->tipoVinculo()->value,
                'origen' => OrigenSolicitud::Autoregistro->value,
                'estado' => EstadoSolicitud::Pendiente->value,
                'solicitado_por_id' => $usuario->getKey(),
            ]);

            foreach ($datos->personas() as $persona) {
                $solicitud->personas()->create([
                    'usuario_id' => $persona->esPrincipal() ? $usuario->getKey() : null,
                    'nombre' => $persona->nombre(),
                    'rut' => $persona->rut(),
                    'email' => $persona->email(),
                    'telefono' => $persona->telefono(),
                    'telefono_emergencia' => $persona->telefonoEmergencia(),
                    'nombre_emergencia' => $persona->nombreEmergencia(),
                    'tipo_vinculo' => $persona->tipoVinculo()->value,
                    'es_principal' => $persona->esPrincipal(),
                ]);
            }

            foreach ($datos->mascotas() as $mascota) {
                $this->guardarMascotaDeSolicitud($solicitud->getKey(), $mascota);
            }

            return (string) $solicitud->getKey();
        });
    }

    private function guardarMascotaDeSolicitud(int|string $solicitudId, MascotaRegistro $mascota): void
    {
        DB::table('solicitud_residente_mascotas')->insert([
            'solicitud_id' => $solicitudId,
            'nombre' => $mascota->nombre(),
            'especie' => $mascota->especie()->value,
            'raza' => $mascota->raza(),
            'microchip' => $mascota->microchip(),
            'foto_url' => $mascota->fotoUrl(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
