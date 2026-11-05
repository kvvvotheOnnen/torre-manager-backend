<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;

final class DescartarProyectoBorradorUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
    ) {}

    /**
     * @throws RecursoNoEncontradoException
     */
    public function execute(string $id): void
    {
        $this->repository->descartarProyectoBorrador($id);
    }
}
