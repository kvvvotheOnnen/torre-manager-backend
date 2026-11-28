<?php

namespace Features\Acceso\Data\Listeners;

use Features\Acceso\Domain\Events\VinculoAsignado;
use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;

final class NotificarVinculoAsignado
{
    public function __construct(
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(VinculoAsignado $event): void
    {
        if ($event->usuarioId === '') {
            return;
        }

        ($this->crearNotificacion)([
            'usuario_id' => $event->usuarioId,
            'proyecto_id' => $event->proyectoId,
            'tipo' => 'acceso',
            'titulo' => 'Nuevo acceso asignado',
            'mensaje' => sprintf(
                'Se te asignó un acceso a una unidad del proyecto como %s.',
                $event->tipoVinculo,
            ),
            'datos' => [
                'vinculo_id' => $event->vinculoId,
                'unidad_principal_id' => $event->unidadPrincipalId,
            ],
        ]);
    }
}
