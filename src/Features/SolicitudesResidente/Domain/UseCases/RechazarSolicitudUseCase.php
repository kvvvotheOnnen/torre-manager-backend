<?php

namespace Features\SolicitudesResidente\Domain\UseCases;

use Features\Auth\Domain\Repositories\UsuarioRepositoryInterface;
use Features\SolicitudesResidente\Domain\Entities\EstadoSolicitud;
use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudNoEncontradaException;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudYaDecididaException;
use Features\SolicitudesResidente\Domain\Repositories\SolicitudResidenteRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class RechazarSolicitudUseCase
{
    public function __construct(
        private readonly SolicitudResidenteRepositoryInterface $solicitudes,
        private readonly UsuarioRepositoryInterface $usuarios,
    ) {}

    public function execute(string $solicitudId, string $decididoPorId, string $motivo): SolicitudResidente
    {
        DB::transaction(function () use ($solicitudId, $decididoPorId, $motivo): void {
            $solicitud = $this->solicitudes->obtener($solicitudId)
                ?? throw SolicitudNoEncontradaException::porqueNoExiste();

            if ($solicitud->estado() !== EstadoSolicitud::Pendiente) {
                throw SolicitudYaDecididaException::porqueYaFueDecidida();
            }

            foreach ($solicitud->personas() as $persona) {
                if ($persona->usuarioId() !== null) {
                    $this->usuarios->marcarRechazadoSiPendiente($persona->usuarioId());
                }
            }

            $this->solicitudes->marcarRechazada($solicitudId, $decididoPorId, $motivo);
        });

        return $this->solicitudes->obtener($solicitudId)
            ?? throw SolicitudNoEncontradaException::porqueNoExiste();
    }
}
