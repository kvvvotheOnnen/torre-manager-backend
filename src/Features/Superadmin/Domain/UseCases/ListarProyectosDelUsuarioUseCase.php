<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;

final class ListarProyectosDelUsuarioUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
    ) {}

    /**
     * @return \Features\Superadmin\Domain\Entities\ProyectoDetalle[]
     *
     * @throws RecursoNoEncontradoException
     */
    public function execute(string $usuarioId): array
    {
        return $this->repository->listarProyectosDelUsuario($usuarioId);
    }
}
