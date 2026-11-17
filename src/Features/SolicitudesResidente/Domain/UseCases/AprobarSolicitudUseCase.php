<?php

namespace Features\SolicitudesResidente\Domain\UseCases;

use Features\Acceso\Domain\Entities\TipoVinculo;
use Features\Acceso\Domain\Repositories\MembresiaProyectoRepositoryInterface;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;
use Features\Acceso\Domain\UseCases\AsignarVinculoUnidadUseCase;
use Features\Auth\Domain\Entities\EstadoUsuario;
use Features\Auth\Domain\Entities\Rol;
use Features\Auth\Domain\Repositories\UsuarioRepositoryInterface;
use Features\SolicitudesResidente\Domain\Entities\EstadoSolicitud;
use Features\SolicitudesResidente\Domain\Entities\PersonaSolicitud;
use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Entities\TipoSolicitud;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudNoEncontradaException;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudYaDecididaException;
use Features\SolicitudesResidente\Domain\Repositories\SolicitudResidenteRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class AprobarSolicitudUseCase
{
    public function __construct(
        private readonly SolicitudResidenteRepositoryInterface $solicitudes,
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly MembresiaProyectoRepositoryInterface $membresias,
        private readonly VinculoUnidadRepositoryInterface $vinculos,
        private readonly AsignarVinculoUnidadUseCase $asignarVinculo,
    ) {}

    public function execute(string $solicitudId, string $decididoPorId): SolicitudResidente
    {
        DB::transaction(function () use ($solicitudId, $decididoPorId): void {
            $solicitud = $this->solicitudes->obtener($solicitudId)
                ?? throw SolicitudNoEncontradaException::porqueNoExiste();

            if ($solicitud->estado() !== EstadoSolicitud::Pendiente) {
                throw SolicitudYaDecididaException::porqueYaFueDecidida();
            }

            $fechaInicio = now()->toDateString();

            foreach ($solicitud->personas() as $persona) {
                $usuarioId = $this->asegurarUsuarioActivo($solicitud, $persona);

                if ($persona->esPrincipal()) {
                    $this->asignarVinculo->execute(
                        $solicitud->unidadPrincipalId(),
                        $usuarioId,
                        $solicitud->proyectoId(),
                        $persona->tipoVinculo(),
                        $fechaInicio,
                        null,
                    );

                    continue;
                }

                $this->membresias->asegurar($solicitud->proyectoId(), $usuarioId, Rol::Residente);
                $this->vinculos->crear(
                    $solicitud->unidadPrincipalId(),
                    $usuarioId,
                    TipoVinculo::Familiar,
                    $fechaInicio,
                    null,
                );
            }

            $this->solicitudes->marcarAprobada($solicitudId, $decididoPorId);
        });

        return $this->solicitudes->obtener($solicitudId)
            ?? throw SolicitudNoEncontradaException::porqueNoExiste();
    }

    private function asegurarUsuarioActivo(SolicitudResidente $solicitud, PersonaSolicitud $persona): string
    {
        $existente = $this->usuarios->buscarPorEmail($persona->email());
        $usuarioId = $persona->usuarioId() ?? $existente?->id();
        $rol = $this->rolPara($solicitud, $persona);

        if ($usuarioId === null) {
            $usuario = $this->usuarios->crearPendiente(
                $solicitud->proyectoId(),
                $persona->nombre(),
                $persona->email(),
                $persona->rut(),
                $persona->telefono(),
                $persona->telefonoEmergencia(),
                $persona->nombreEmergencia(),
                $rol,
            );

            $usuarioId = $usuario->id();
            $this->solicitudes->actualizarUsuarioIdPersona($persona->id(), $usuarioId);
            $existente = $usuario;
        }

        $estabaActivo = $existente?->estado() === EstadoUsuario::Activo;

        $this->usuarios->actualizarDatos(
            $usuarioId,
            $persona->nombre(),
            $persona->rut(),
            $persona->telefono(),
            $persona->telefonoEmergencia(),
            $persona->nombreEmergencia(),
            $estabaActivo ? null : $rol,
            EstadoUsuario::Activo,
        );

        return $usuarioId;
    }

    private function rolPara(SolicitudResidente $solicitud, PersonaSolicitud $persona): Rol
    {
        if (! $persona->esPrincipal()) {
            return Rol::Residente;
        }

        return $solicitud->tipoSolicitud() === TipoSolicitud::Propietario
            ? Rol::Propietario
            : Rol::Residente;
    }
}
