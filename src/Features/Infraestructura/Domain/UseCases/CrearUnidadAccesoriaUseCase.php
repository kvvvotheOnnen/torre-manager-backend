<?php

namespace Features\Infraestructura\Domain\UseCases;

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
    public function execute(string $proyectoId, string $identificador, string $tipo, ?string $unidadPrincipalId): UnidadAccesoria
    {
        $identificador = trim($identificador);

        if ($identificador === '') {
            throw new InvalidArgumentException('El identificador de la unidad accesoria es obligatorio.');
        }

        $tipoAccesoria = TipoUnidadAccesoria::tryFrom($tipo);

        if ($tipoAccesoria === null) {
            throw new InvalidArgumentException('El tipo de unidad accesoria no es válido.');
        }

        return $this->repository->crearUnidadAccesoria($proyectoId, $identificador, $tipoAccesoria, $unidadPrincipalId);
    }
}
