<?php

namespace Features\Servicios\Domain\UseCases;

use Features\Servicios\Domain\Entities\TipoServicio;
use Features\Servicios\Domain\Entities\TrabajadorServicio;
use Features\Servicios\Domain\Repositories\ServiciosRepositoryInterface;

final class ListarTrabajadoresUseCase
{
    public function __construct(
        private readonly ServiciosRepositoryInterface $repository,
    ) {}

    /**
     * @return TrabajadorServicio[]
     */
    public function execute(string $proyectoId, ?string $tipo = null, ?string $buscar = null): array
    {
        $tipoFiltrado = $tipo === null || $tipo === '' ? null : TipoServicio::tryFrom($tipo);

        return $this->repository->listar($proyectoId, $tipoFiltrado, $buscar);
    }
}
