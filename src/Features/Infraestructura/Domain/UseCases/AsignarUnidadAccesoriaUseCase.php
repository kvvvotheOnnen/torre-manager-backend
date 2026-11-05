<?php

namespace Features\Infraestructura\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\TipoAsignacion;
use Features\Infraestructura\Domain\Entities\UnidadAccesoria;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;
use InvalidArgumentException;

final class AsignarUnidadAccesoriaUseCase
{
    public function __construct(
        private readonly InfraestructuraRepositoryInterface $repository,
    ) {}

    /**
     * Asigna (o libera con null) una unidad accesoria a una unidad principal.
     *
     * @throws RecursoNoEncontradoException
     * @throws InvalidArgumentException
     */
    public function execute(
        string $unidadAccesoriaId,
        ?string $asignacion,
        ?string $unidadPrincipalId,
    ): UnidadAccesoria {
        $tipoAsignacion = $asignacion !== null
            ? TipoAsignacion::tryFrom($asignacion)
            : null;

        if ($tipoAsignacion === null && $asignacion !== null) {
            throw new InvalidArgumentException('El tipo de asignación no es válido.');
        }

        if ($tipoAsignacion === TipoAsignacion::Departamento && $unidadPrincipalId === null) {
            throw new InvalidArgumentException('Debes indicar la unidad principal para la asignación.');
        }

        return $this->repository->asignarUnidadAccesoria(
            $unidadAccesoriaId,
            $tipoAsignacion,
            $unidadPrincipalId,
        );
    }
}
