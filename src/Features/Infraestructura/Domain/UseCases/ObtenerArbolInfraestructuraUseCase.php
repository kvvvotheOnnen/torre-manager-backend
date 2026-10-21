<?php

namespace Features\Infraestructura\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\ArbolInfraestructura;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;

final class ObtenerArbolInfraestructuraUseCase
{
    public function __construct(
        private readonly InfraestructuraRepositoryInterface $repository,
    ) {}

    /**
     * @throws RecursoNoEncontradoException
     */
    public function execute(string $proyectoId): ArbolInfraestructura
    {
        return $this->repository->obtenerArbol($proyectoId);
    }
}
