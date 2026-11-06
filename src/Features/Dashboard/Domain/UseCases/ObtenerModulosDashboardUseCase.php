<?php

namespace Features\Dashboard\Domain\UseCases;

use Features\Dashboard\Domain\Entities\ModuloDashboard;
use Features\Dashboard\Domain\Exceptions\ProyectoNoDisponibleException;
use Features\Dashboard\Domain\Repositories\DashboardRepositoryInterface;
use Features\Dashboard\Domain\Services\CatalogoModulosDashboard;

final class ObtenerModulosDashboardUseCase
{
    public function __construct(
        private readonly DashboardRepositoryInterface $repository,
        private readonly CatalogoModulosDashboard $catalogo,
    ) {}

    /**
     * @return ModuloDashboard[]
     *
     * @throws ProyectoNoDisponibleException
     */
    public function execute(string $usuarioId, ?string $proyectoIdSolicitado = null): array
    {
        $proyecto = $this->repository->obtenerProyectoDelUsuario(
            $usuarioId,
            $proyectoIdSolicitado,
        );

        return $this->catalogo->construir($proyecto);
    }
}
