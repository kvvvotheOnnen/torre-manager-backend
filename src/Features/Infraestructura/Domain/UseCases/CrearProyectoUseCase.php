<?php

namespace Features\Infraestructura\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\Proyecto;
use Features\Infraestructura\Domain\Exceptions\RecursoDuplicadoException;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;
use InvalidArgumentException;

final class CrearProyectoUseCase
{
    public function __construct(
        private readonly InfraestructuraRepositoryInterface $repository,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws RecursoDuplicadoException
     */
    public function execute(string $nombre, ?string $descripcion): Proyecto
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre del proyecto es obligatorio.');
        }

        return $this->repository->crearProyecto($nombre, $descripcion !== null ? trim($descripcion) : null);
    }
}
