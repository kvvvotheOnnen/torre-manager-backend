<?php

namespace Features\Marketplace\Data\Listeners;

use Features\Marketplace\Domain\Events\PublicacionAprobadaMarketplace;
use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;

final class NotificarPublicacionAprobada
{
    public function __construct(
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(PublicacionAprobadaMarketplace $event): void
    {
        if ($event->propietarioId === null || $event->propietarioId === '') {
            return;
        }

        ($this->crearNotificacion)([
            'usuario_id' => $event->propietarioId,
            'proyecto_id' => $event->proyectoId,
            'tipo' => 'marketplace',
            'titulo' => 'Publicación aprobada',
            'mensaje' => sprintf(
                'Tu publicación "%s" fue aprobada y ya está visible en el marketplace.',
                $event->titulo,
            ),
            'datos' => ['publicacion_id' => $event->publicacionId],
        ]);
    }
}
