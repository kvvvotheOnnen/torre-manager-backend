<?php

namespace Features\Infraestructura\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\Agrupacion;
use Features\Infraestructura\Domain\Entities\TipoAgrupacion;
use Features\Infraestructura\Domain\Entities\UnidadPrincipal;
use Features\Infraestructura\Domain\Exceptions\RecursoDuplicadoException;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;
use InvalidArgumentException;

final class RegistrarAgrupacionEnCascadaUseCase
{
    public function __construct(
        private readonly InfraestructuraRepositoryInterface $repository,
        private readonly GeneradorIdentificacionesUnidades $generador,
    ) {}

    /**
     * Registra una agrupación y genera en cascada sus unidades principales.
     *
     * @return Agrupacion
     *
     * @throws InvalidArgumentException
     * @throws RecursoDuplicadoException
     * @throws RecursoNoEncontradoException
     */
    public function execute(
        string $proyectoId,
        string $nombre,
        string $tipo,
        ?string $prefijo,
        int $pisoDesde,
        int $pisoHasta,
        int $unidadesPorPiso,
    ): Agrupacion {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre de la agrupación es obligatorio.');
        }

        $tipoAgrupacion = TipoAgrupacion::tryFrom($tipo);

        if ($tipoAgrupacion === null) {
            throw new InvalidArgumentException('El tipo de agrupación no es válido.');
        }

        $identificaciones = $this->generador->generar($prefijo, $pisoDesde, $pisoHasta, $unidadesPorPiso);

        $unidades = array_map(
            static fn (string $identificador) => new UnidadPrincipal(id: '', agrupacionId: '', identificador: $identificador),
            $identificaciones,
        );

        return $this->repository->crearAgrupacionEnCascada($proyectoId, $nombre, $tipoAgrupacion, $unidades);
    }
}
