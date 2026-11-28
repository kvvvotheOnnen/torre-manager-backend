<?php

namespace Features\Marketplace\Data\Listeners;

use Features\Marketplace\Domain\Events\PublicacionEliminadaPorModeracion;
use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;

final class NotificarPublicacionModerada
{
    public function __construct(
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(PublicacionEliminadaPorModeracion $event): void
    {
        if ($event->propietarioId === null || $event->propietarioId === '') {
            return;
        }

        ($this->crearNotificacion)([
            'usuario_id' => $event->propietarioId,
            'proyecto_id' => $event->proyectoId,
            'tipo' => 'marketplace',
            'titulo' => 'Publicación eliminada',
            'mensaje' => sprintf(
                'Tu publicación "%s" fue eliminada por moderación. Motivo: %s',
                $event->titulo,
                $event->motivo,
            ),
            'datos' => ['publicacion_id' => $event->publicacionId],
        ]);
    }
}
