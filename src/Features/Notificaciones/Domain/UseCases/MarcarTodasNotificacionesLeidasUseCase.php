<?php

namespace Features\Notificaciones\Domain\UseCases;

use Features\Notificaciones\Domain\Repositories\NotificacionesRepositoryInterface;

final class MarcarTodasNotificacionesLeidasUseCase
{
    public function __construct(
        private readonly NotificacionesRepositoryInterface $repository,
    ) {}

    public function __invoke(string $usuarioId): int
    {
        return $this->repository->marcarTodasLeidas($usuarioId);
    }
}
