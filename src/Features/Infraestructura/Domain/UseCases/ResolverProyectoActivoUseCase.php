<?php

namespace Features\Infraestructura\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\Proyecto;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;

final class ResolverProyectoActivoUseCase
{
    public function __construct(
        private readonly InfraestructuraRepositoryInterface $repository,
    ) {}

    /**
     * @throws RecursoNoEncontradoException
     */
    public function execute(string $usuarioId, ?string $proyectoIdSolicitado = null): Proyecto
    {
        return $this->repository->obtenerProyectoActivo($usuarioId, $proyectoIdSolicitado);
    }
}
