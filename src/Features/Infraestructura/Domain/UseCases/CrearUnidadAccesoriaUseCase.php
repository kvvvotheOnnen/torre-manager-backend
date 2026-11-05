<?php

namespace Features\Infraestructura\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\TipoAsignacion;
use Features\Infraestructura\Domain\Entities\TipoUnidadAccesoria;
use Features\Infraestructura\Domain\Entities\UnidadAccesoria;
use Features\Infraestructura\Domain\Exceptions\RecursoDuplicadoException;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;
use InvalidArgumentException;

final class CrearUnidadAccesoriaUseCase
{
    public function __construct(
        private readonly InfraestructuraRepositoryInterface $repository,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws RecursoDuplicadoException
     * @throws RecursoNoEncontradoException
     */
    public function execute(
        string $proyectoId,
        string $identificador,
        string $tipo,
        ?string $asignacion,
        ?string $unidadPrincipalId,
    ): UnidadAccesoria {
        $identificador = trim($identificador);

        if ($identificador === '') {
            throw new InvalidArgumentException('El identificador de la unidad accesoria es obligatorio.');
        }

        $tipoAccesoria = TipoUnidadAccesoria::tryFrom($tipo);

        if ($tipoAccesoria === null) {
            throw new InvalidArgumentException('El tipo de unidad accesoria no es válido.');
        }

        $tipoAsignacion = $asignacion !== null
            ? TipoAsignacion::tryFrom($asignacion)
            : null;

        if ($tipoAsignacion === null && $asignacion !== null) {
            throw new InvalidArgumentException('El tipo de asignación no es válido.');
        }

        if ($tipoAsignacion === TipoAsignacion::Departamento && $unidadPrincipalId === null) {
            throw new InvalidArgumentException('Debes indicar la unidad principal para la asignación.');
        }

        return $this->repository->crearUnidadAccesoria(
            $proyectoId,
            $identificador,
            $tipoAccesoria,
            $tipoAsignacion,
            $unidadPrincipalId,
        );
    }
}
