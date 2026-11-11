<?php

namespace Features\Paqueteria\Domain\UseCases;

use Features\Paqueteria\Domain\Entities\Paquete;
use Features\Paqueteria\Domain\Exceptions\PaqueteNoEncontradoException;
use Features\Paqueteria\Domain\Repositories\PaqueteriaRepositoryInterface;

final class ActualizarPaqueteUseCase
{
    public function __construct(
        private readonly PaqueteriaRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws PaqueteNoEncontradoException
     */
    public function execute(string $paqueteId, array $datos): Paquete
    {
        return $this->repository->actualizar($paqueteId, $datos);
    }
}
