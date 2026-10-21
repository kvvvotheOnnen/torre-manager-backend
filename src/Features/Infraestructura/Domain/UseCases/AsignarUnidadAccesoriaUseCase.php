<?php

namespace Features\Infraestructura\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\UnidadAccesoria;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;

final class AsignarUnidadAccesoriaUseCase
{
    public function __construct(
        private readonly InfraestructuraRepositoryInterface $repository,
    ) {}

    /**
     * Asigna (o libera con null) una unidad accesoria a una unidad principal.
     *
     * @throws RecursoNoEncontradoException
     */
    public function execute(string $unidadAccesoriaId, ?string $unidadPrincipalId): UnidadAccesoria
    {
        return $this->repository->asignarUnidadAccesoria($unidadAccesoriaId, $unidadPrincipalId);
    }
}
