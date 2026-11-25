<?php

namespace Features\Acceso\Data\Listeners;

use Features\Acceso\Domain\Events\VinculoCerrado;
use Features\Notificaciones\Domain\UseCases\CrearNotificacionUseCase;

final class NotificarVinculoCerrado
{
    public function __construct(
        private readonly CrearNotificacionUseCase $crearNotificacion,
    ) {}

    public function handle(VinculoCerrado $event): void
    {
        if ($event->usuarioId === '') {
            return;
        }

        ($this->crearNotificacion)([
            'usuario_id' => $event->usuarioId,
            'proyecto_id' => $event->proyectoId,
            'tipo' => 'acceso',
            'titulo' => 'Acceso cerrado',
            'mensaje' => sprintf(
                'Se cerró tu acceso a una unidad del proyecto como %s.',
                $event->tipoVinculo,
            ),
            'datos' => [
                'vinculo_id' => $event->vinculoId,
                'unidad_principal_id' => $event->unidadPrincipalId,
            ],
        ]);
    }
}
