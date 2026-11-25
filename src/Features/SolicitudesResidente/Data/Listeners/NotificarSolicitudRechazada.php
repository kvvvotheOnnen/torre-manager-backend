<?php

namespace Features\SolicitudesResidente\Data\Listeners;

use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;
use Features\SolicitudesResidente\Domain\Events\SolicitudRechazada;

final class NotificarSolicitudRechazada
{
    public function __construct(
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(SolicitudRechazada $event): void
    {
        if ($event->usuarioId === '') {
            return;
        }

        ($this->crearNotificacion)([
            'usuario_id' => $event->usuarioId,
            'proyecto_id' => $event->proyectoId,
            'tipo' => 'solicitudes',
            'titulo' => 'Solicitud rechazada',
            'mensaje' => sprintf(
                'Tu solicitud de acceso a la unidad fue rechazada. Motivo: %s',
                $event->motivo,
            ),
            'datos' => ['solicitud_id' => $event->solicitudId],
        ]);
    }
}
