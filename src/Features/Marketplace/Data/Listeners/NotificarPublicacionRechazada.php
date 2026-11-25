<?php

namespace Features\Marketplace\Data\Listeners;

use Features\Marketplace\Domain\Events\PublicacionRechazadaMarketplace;
use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;

final class NotificarPublicacionRechazada
{
    public function __construct(
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(PublicacionRechazadaMarketplace $event): void
    {
        if ($event->propietarioId === null || $event->propietarioId === '') {
            return;
        }

        ($this->crearNotificacion)([
            'usuario_id' => $event->propietarioId,
            'proyecto_id' => $event->proyectoId,
            'tipo' => 'marketplace',
            'titulo' => 'Publicación rechazada',
            'mensaje' => sprintf(
                'Tu publicación "%s" fue rechazada. Motivo: %s',
                $event->titulo,
                $event->motivo,
            ),
            'datos' => ['publicacion_id' => $event->publicacionId],
        ]);
    }
}
