<?php

namespace Features\Paqueteria\Domain\UseCases;

use Features\Paqueteria\Domain\Entities\Paquete;
use Features\Paqueteria\Domain\Repositories\PaqueteriaRepositoryInterface;

final class ListarPaquetesUseCase
{
    public function __construct(
        private readonly PaqueteriaRepositoryInterface $repository,
    ) {}

    /**
     * @return Paquete[]
     */
    public function execute(string $usuarioId, string $rol, ?string $proyectoId = null): array
    {
        return $this->repository->listar($usuarioId, $rol, $proyectoId);
    }
}
