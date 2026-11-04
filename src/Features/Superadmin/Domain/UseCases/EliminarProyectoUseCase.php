<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;

final class EliminarProyectoUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
    ) {}

    /**
     * Elimina un proyecto (tenant). El borrado es físico y en cascada sobre
     * su estructura, accesorias, instalaciones extra y administradores.
     *
     * @throws RecursoNoEncontradoException
     */
    public function execute(string $id): void
    {
        $this->repository->eliminarProyecto($id);
    }
}
