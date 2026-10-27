<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Superadmin\Domain\Entities\ProyectoDetalle;
use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;

final class BuscarProyectoUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
    ) {}

    /**
     * @throws RecursoNoEncontradoException
     */
    public function execute(string $id): ProyectoDetalle
    {
        return $this->repository->buscarProyecto($id);
    }
}
