<?php

namespace Features\Paqueteria\Domain\UseCases;

use Features\Paqueteria\Domain\Entities\Paquete;
use Features\Paqueteria\Domain\Repositories\PaqueteriaRepositoryInterface;

final class RegistrarPaqueteUseCase
{
    public function __construct(
        private readonly PaqueteriaRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     */
    public function execute(array $datos): Paquete
    {
        return $this->repository->registrar($datos);
    }
}
