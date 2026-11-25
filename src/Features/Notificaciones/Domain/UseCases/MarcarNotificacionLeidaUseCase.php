<?php

namespace Features\Notificaciones\Domain\UseCases;

use Features\Notificaciones\Domain\Entities\Notificacion;
use Features\Notificaciones\Domain\Repositories\NotificacionesRepositoryInterface;

final class MarcarNotificacionLeidaUseCase
{
    public function __construct(
        private readonly NotificacionesRepositoryInterface $repository,
    ) {}

    public function __invoke(string $usuarioId, string $notificacionId): Notificacion
    {
        return $this->repository->marcarLeida($usuarioId, $notificacionId);
    }
}
