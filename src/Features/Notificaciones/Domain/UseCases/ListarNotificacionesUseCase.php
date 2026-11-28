<?php

namespace Features\Notificaciones\Domain\UseCases;

use Features\Notificaciones\Domain\Entities\Notificacion;
use Features\Notificaciones\Domain\Repositories\NotificacionesRepositoryInterface;

final class ListarNotificacionesUseCase
{
    public function __construct(
        private readonly NotificacionesRepositoryInterface $repository,
    ) {}

    /**
     * @return list<Notificacion>
     */
    public function __invoke(string $usuarioId, int $limite = 50): array
    {
        return $this->repository->listar($usuarioId, $limite);
    }
}
