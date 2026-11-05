<?php

namespace Features\Infraestructura\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\TipoAsignacion;
use Features\Infraestructura\Domain\Entities\TipoUnidadAccesoria;
use Features\Infraestructura\Domain\Entities\UnidadAccesoria;
use Features\Infraestructura\Domain\Exceptions\RecursoDuplicadoException;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;
use InvalidArgumentException;

final class CrearUnidadesAccesoriasEnLoteUseCase
{
    public function __construct(
        private readonly InfraestructuraRepositoryInterface $repository,
        private readonly GeneradorIdentificadoresAccesorias $generador,
    ) {}

    /**
     * @return UnidadAccesoria[]
     *
     * @throws InvalidArgumentException
     * @throws RecursoDuplicadoException
     * @throws RecursoNoEncontradoException
     */
    public function execute(
        string $proyectoId,
        string $tipo,
        int $cantidad,
        ?string $asignacion,
        ?string $unidadPrincipalId = null,
    ): array {
        if ($cantidad < 1 || $cantidad > GeneradorIdentificadoresAccesorias::MAX_POR_LOTE) {
            throw new InvalidArgumentException('La cantidad debe estar entre 1 y '.GeneradorIdentificadoresAccesorias::MAX_POR_LOTE.'.');
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

        $identificadores = $this->generador->generar(
            $tipoAccesoria,
            $cantidad,
            $this->repository->listarIdentificadoresAccesorias($proyectoId, $tipoAccesoria),
        );

        return $this->repository->crearUnidadesAccesoriasEnLote(
            $proyectoId,
            $tipoAccesoria,
            $identificadores,
            $tipoAsignacion,
            $unidadPrincipalId,
        );
    }
}
