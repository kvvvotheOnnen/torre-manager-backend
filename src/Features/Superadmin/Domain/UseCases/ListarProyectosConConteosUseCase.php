<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;

final class ListarProyectosConConteosUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
    ) {}

    /**
     * @return \Features\Superadmin\Domain\Entities\ResumenProyecto[]
     */
    public function execute(): array
    {
        return $this->repository->listarProyectosConConteos();
    }
}
