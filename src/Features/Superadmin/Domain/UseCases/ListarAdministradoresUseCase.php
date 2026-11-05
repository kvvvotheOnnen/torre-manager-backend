<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;

final class ListarAdministradoresUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
    ) {}

    /**
     * @return \Features\Superadmin\Domain\Entities\Administrador[]
     */
    public function execute(): array
    {
        return $this->repository->listarAdministradores();
    }
}
