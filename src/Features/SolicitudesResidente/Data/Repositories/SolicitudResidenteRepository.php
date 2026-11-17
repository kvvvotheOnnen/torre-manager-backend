<?php

namespace Features\SolicitudesResidente\Data\Repositories;

use App\Models\SolicitudResidente as SolicitudResidenteModel;
use App\Models\SolicitudResidentePersona as PersonaModel;
use App\Models\UnidadAccesoria;
use App\Models\UnidadPrincipal;
use Features\Acceso\Domain\Entities\TipoVinculo;
use Features\SolicitudesResidente\Domain\Entities\EstadoSolicitud;
use Features\SolicitudesResidente\Domain\Entities\InstalacionUnidad;
use Features\SolicitudesResidente\Domain\Entities\OrigenSolicitud;
use Features\SolicitudesResidente\Domain\Entities\PersonaSolicitud;
use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Entities\TipoSolicitud;
use Features\SolicitudesResidente\Domain\Repositories\SolicitudResidenteRepositoryInterface;

final class SolicitudResidenteRepository implements SolicitudResidenteRepositoryInterface
{
    public function unidadPerteneceAProyecto(string $unidadId, string $proyectoId): bool
    {
        return UnidadPrincipal::query()
            ->whereKey($unidadId)
            ->whereHas('agrupacion', fn ($query) => $query->where('proyecto_id', $proyectoId))
            ->exists();
    }

    public function existePendienteDuplicada(string $unidadId, array $emails, array $ruts): bool
    {
        return SolicitudResidenteModel::query()
            ->where('unidad_principal_id', $unidadId)
            ->where('estado', EstadoSolicitud::Pendiente->value)
            ->whereHas('personas', function ($query) use ($emails, $ruts): void {
                $query->whereIn('email', $emails)->orWhereIn('rut', $ruts);
            })
            ->exists();
    }

    public function crear(
        string $proyectoId,
        string $unidadId,
        TipoSolicitud $tipoSolicitud,
        OrigenSolicitud $origen,
        ?string $solicitadoPorId,
        array $personas,
        ?array $datosExtra = null,
    ): SolicitudResidente {
        $solicitud = SolicitudResidenteModel::query()->create([
            'proyecto_id' => $proyectoId,
            'unidad_principal_id' => $unidadId,
            'tipo_solicitud' => $tipoSolicitud->value,
            'origen' => $origen->value,
            'estado' => EstadoSolicitud::Pendiente->value,
            'datos_extra' => $datosExtra,
            'solicitado_por_id' => $solicitadoPorId,
        ]);

        foreach ($personas as $persona) {
            $solicitud->personas()->create($persona);
        }

        return $this->obtener((string) $solicitud->getKey());
    }

    public function obtener(string $id): ?SolicitudResidente
    {
        $solicitud = $this->consultaBase()->find($id);

        return $solicitud !== null ? $this->toEntity($solicitud) : null;
    }

    public function listarPendientes(string $proyectoId): array
    {
        return $this->consultaBase()
            ->where('proyecto_id', $proyectoId)
            ->where('estado', EstadoSolicitud::Pendiente->value)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SolicitudResidenteModel $solicitud) => $this->toEntity($solicitud))
            ->all();
    }

    public function listarHistorial(string $proyectoId, int $limite = 100): array
    {
        return $this->consultaBase()
            ->where('proyecto_id', $proyectoId)
            ->where('estado', '!=', EstadoSolicitud::Pendiente->value)
            ->whereNotNull('decidido_at')
            ->orderByDesc('decidido_at')
            ->orderByDesc('id')
            ->limit($limite)
            ->get()
            ->map(fn (SolicitudResidenteModel $solicitud) => $this->toEntity($solicitud))
            ->all();
    }

    public function marcarAprobada(string $id, string $decididoPorId): void
    {
        SolicitudResidenteModel::query()->whereKey($id)->update([
            'estado' => EstadoSolicitud::Aprobada->value,
            'decidido_por_id' => $decididoPorId,
            'decidido_at' => now(),
            'motivo_rechazo' => null,
        ]);
    }

    public function marcarRechazada(string $id, string $decididoPorId, string $motivo): void
    {
        SolicitudResidenteModel::query()->whereKey($id)->update([
            'estado' => EstadoSolicitud::Rechazada->value,
            'motivo_rechazo' => $motivo,
            'decidido_por_id' => $decididoPorId,
            'decidido_at' => now(),
        ]);
    }

    public function actualizarUsuarioIdPersona(string $personaId, string $usuarioId): void
    {
        PersonaModel::query()->whereKey($personaId)->update(['usuario_id' => $usuarioId]);
    }

    private function consultaBase()
    {
        return SolicitudResidenteModel::query()->with([
            'personas',
            'unidadPrincipal.agrupacion',
            'unidadPrincipal.unidadesAccesorias',
            'solicitadoPor',
            'decididoPor',
        ]);
    }

    private function toEntity(SolicitudResidenteModel $solicitud): SolicitudResidente
    {
        $unidad = $solicitud->unidadPrincipal;

        return new SolicitudResidente(
            id: (string) $solicitud->getKey(),
            proyectoId: (string) $solicitud->proyecto_id,
            unidadPrincipalId: (string) $solicitud->unidad_principal_id,
            unidad: (string) ($unidad?->identificador ?? ''),
            agrupacion: (string) ($unidad?->agrupacion?->nombre ?? ''),
            tipoSolicitud: TipoSolicitud::tryFrom((string) $solicitud->tipo_solicitud) ?? TipoSolicitud::Propietario,
            origen: OrigenSolicitud::tryFrom((string) $solicitud->origen) ?? OrigenSolicitud::Autoregistro,
            estado: EstadoSolicitud::tryFrom((string) $solicitud->estado) ?? EstadoSolicitud::Pendiente,
            motivoRechazo: $solicitud->motivo_rechazo,
            personas: $solicitud->personas
                ->map(fn (PersonaModel $persona) => new PersonaSolicitud(
                    id: (string) $persona->getKey(),
                    usuarioId: $persona->usuario_id !== null ? (string) $persona->usuario_id : null,
                    nombre: $persona->nombre,
                    rut: $persona->rut,
                    email: $persona->email,
                    telefono: $persona->telefono,
                    telefonoEmergencia: $persona->telefono_emergencia,
                    nombreEmergencia: $persona->nombre_emergencia,
                    tipoVinculo: TipoVinculo::tryFrom((string) $persona->tipo_vinculo) ?? TipoVinculo::Residente,
                    esPrincipal: (bool) $persona->es_principal,
                ))
                ->all(),
            instalaciones: $unidad?->unidadesAccesorias
                ?->map(fn (UnidadAccesoria $accesoria) => new InstalacionUnidad(
                    id: (string) $accesoria->getKey(),
                    identificador: $accesoria->identificador,
                    tipo: $accesoria->tipo,
                    asignacion: $accesoria->asignacion,
                ))
                ->all() ?? [],
            solicitadoPorId: $solicitud->solicitado_por_id !== null ? (string) $solicitud->solicitado_por_id : null,
            solicitadoPorNombre: $solicitud->solicitadoPor?->nombre,
            decididoPorId: $solicitud->decidido_por_id !== null ? (string) $solicitud->decidido_por_id : null,
            decididoPorNombre: $solicitud->decididoPor?->nombre,
            decididoAt: $solicitud->decidido_at?->toDateTimeString(),
            createdAt: (string) $solicitud->created_at?->toDateTimeString(),
        );
    }
}
