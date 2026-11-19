<?php

namespace Features\Paqueteria\Domain\UseCases;

use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Paqueteria\Domain\Entities\Paquete;
use Features\Paqueteria\Domain\Repositories\PaqueteriaRepositoryInterface;

final class ObtenerPaqueteUseCase
{
    public function __construct(
        private readonly PaqueteriaRepositoryInterface $repository,
        private readonly AccesoProyectoService $acceso,
    ) {}

    /**
     * Devuelve el paquete solo si el usuario alcanza su proyecto.
     */
    public function execute(string $usuarioId, string $paqueteId): ?Paquete
    {
        $paquete = $this->repository->obtenerPorId($paqueteId);

        if ($paquete === null) {
            return null;
        }

        return $this->acceso->puedeAcceder($usuarioId, $paquete->proyectoId())
            ? $paquete
            : null;
    }
}
