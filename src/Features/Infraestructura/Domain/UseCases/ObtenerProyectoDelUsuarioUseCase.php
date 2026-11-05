<?php

namespace Features\Infraestructura\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\Proyecto;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;

final class ObtenerProyectoDelUsuarioUseCase
{
    public function __construct(
        private readonly InfraestructuraRepositoryInterface $repository,
    ) {}

    /**
     * Retorna el proyecto (tenant) al que pertenece el usuario autenticado.
     *
     * @throws RecursoNoEncontradoException
     */
    public function execute(string $usuarioId): Proyecto
    {
        return $this->repository->obtenerProyectoDelUsuario($usuarioId);
    }
}
