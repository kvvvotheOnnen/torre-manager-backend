<?php

namespace Features\SolicitudesResidente\Domain\UseCases;

use Features\Acceso\Domain\Entities\TipoVinculo;
use Features\Acceso\Domain\UseCases\AsignarVinculoUnidadUseCase;
use Features\SolicitudesResidente\Domain\Entities\EstadoSolicitud;
use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Events\SolicitudAprobada;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudInvalidaException;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudNoEncontradaException;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudYaDecididaException;
use Features\SolicitudesResidente\Domain\Repositories\SolicitudResidenteRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class AprobarSolicitudUseCase
{
    public function __construct(
        private readonly SolicitudResidenteRepositoryInterface $solicitudes,
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

            $principal = null;

            foreach ($solicitud->personas() as $persona) {
                if ($persona->esPrincipal()) {
                    $principal = $persona;
                    break;
                }
            }

            if ($principal === null || $principal->usuarioId() === null) {
                throw SolicitudInvalidaException::porque(
                    'La solicitud no tiene un usuario asociado a la persona principal.',
                );
            }

            if (! in_array(
                $principal->tipoVinculo(),
                [TipoVinculo::Propietario, TipoVinculo::Arrendatario, TipoVinculo::Residente],
                true,
            )) {
                throw SolicitudInvalidaException::porque(
                    'El tipo de vínculo de la persona principal no es válido.',
                );
            }

            $this->asignarVinculo->execute(
                $solicitud->unidadPrincipalId(),
                $principal->usuarioId(),
                $solicitud->proyectoId(),
                $principal->tipoVinculo(),
                now()->toDateString(),
                null,
                notificar: false,
            );

            $this->solicitudes->marcarAprobada($solicitudId, $decididoPorId);
        });

        $solicitud = $this->solicitudes->obtener($solicitudId)
            ?? throw SolicitudNoEncontradaException::porqueNoExiste();

        foreach ($solicitud->personas() as $persona) {
            if ($persona->esPrincipal() && $persona->usuarioId() !== null) {
                event(new SolicitudAprobada(
                    solicitudId: $solicitud->id(),
                    proyectoId: $solicitud->proyectoId(),
                    usuarioId: $persona->usuarioId(),
                ));

                break;
            }
        }

        return $solicitud;
    }
}
