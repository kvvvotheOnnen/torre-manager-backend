<?php

namespace Features\SolicitudesResidente\Data\Listeners;

use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;
use Features\SolicitudesResidente\Domain\Events\SolicitudAprobada;

final class NotificarSolicitudAprobada
{
    public function __construct(
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(SolicitudAprobada $event): void
    {
        if ($event->usuarioId === '') {
            return;
        }

        ($this->crearNotificacion)([
            'usuario_id' => $event->usuarioId,
            'proyecto_id' => $event->proyectoId,
            'tipo' => 'solicitudes',
            'titulo' => 'Solicitud aprobada',
            'mensaje' => 'Tu solicitud de acceso a la unidad fue aprobada. Ya tienes acceso al proyecto.',
            'datos' => ['solicitud_id' => $event->solicitudId],
        ]);
    }
}
