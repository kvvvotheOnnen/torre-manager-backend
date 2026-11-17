<?php

namespace Features\Servicios\Domain\UseCases;

use Features\Servicios\Domain\Repositories\ServiciosRepositoryInterface;

final class EliminarTrabajadorUseCase
{
    public function __construct(
        private readonly ServiciosRepositoryInterface $repository,
    ) {}

    /**
     * Elimina el trabajador y devuelve la ruta de su imagen para que la
     * infraestructura pueda borrar el archivo físico.
     */
    public function execute(string $id, string $proyectoId): ?string
    {
        $trabajador = $this->repository->obtener($id, $proyectoId);
        $this->repository->eliminar($id, $proyectoId);

        return $trabajador->fotoPath();
    }
}
