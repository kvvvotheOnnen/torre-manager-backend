<?php

namespace Features\Notificaciones\Domain\UseCases;

use Features\Notificaciones\Domain\Entities\Notificacion;
use Features\Notificaciones\Domain\Repositories\NotificacionesRepositoryInterface;

final class CrearNotificacionUseCase
{
    public function __construct(
        private readonly NotificacionesRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     */
    public function __invoke(array $datos): Notificacion
    {
        return $this->repository->crear($datos);
    }
}
