<?php

namespace Features\SolicitudesResidente\Domain\UseCases;

use Features\SolicitudesResidente\Domain\Entities\EstadoSolicitud;
use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Events\SolicitudRechazada;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudNoEncontradaException;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudYaDecididaException;
use Features\SolicitudesResidente\Domain\Repositories\SolicitudResidenteRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class RechazarSolicitudUseCase
{
    public function __construct(
        private readonly SolicitudResidenteRepositoryInterface $solicitudes,
    ) {}

    public function execute(string $solicitudId, string $decididoPorId, string $motivo): SolicitudResidente
    {
        DB::transaction(function () use ($solicitudId, $decididoPorId, $motivo): void {
            $solicitud = $this->solicitudes->obtener($solicitudId)
                ?? throw SolicitudNoEncontradaException::porqueNoExiste();

            if ($solicitud->estado() !== EstadoSolicitud::Pendiente) {
                throw SolicitudYaDecididaException::porqueYaFueDecidida();
            }

            $this->solicitudes->marcarRechazada($solicitudId, $decididoPorId, $motivo);
        });

        $solicitud = $this->solicitudes->obtener($solicitudId)
            ?? throw SolicitudNoEncontradaException::porqueNoExiste();

        foreach ($solicitud->personas() as $persona) {
            if ($persona->esPrincipal() && $persona->usuarioId() !== null) {
                event(new SolicitudRechazada(
                    solicitudId: $solicitud->id(),
                    proyectoId: $solicitud->proyectoId(),
                    usuarioId: $persona->usuarioId(),
                    motivo: $motivo,
                ));

                break;
            }
        }

        return $solicitud;
    }
}
