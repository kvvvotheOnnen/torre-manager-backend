<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Superadmin\Domain\Entities\ProyectoDetalle;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;
use InvalidArgumentException;

final class IniciarProyectoBorradorUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function execute(string $nombre, ?string $descripcion): ProyectoDetalle
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre del proyecto es obligatorio.');
        }

        $descripcion = $descripcion !== null ? trim($descripcion) : null;
        $descripcion = $descripcion !== '' ? $descripcion : null;

        return $this->repository->iniciarProyectoBorrador($nombre, $descripcion);
    }
}
