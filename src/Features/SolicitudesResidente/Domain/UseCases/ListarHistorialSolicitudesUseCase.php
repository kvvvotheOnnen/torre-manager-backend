<?php

namespace Features\SolicitudesResidente\Domain\UseCases;

use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Repositories\SolicitudResidenteRepositoryInterface;

final class ListarHistorialSolicitudesUseCase
{
    public function __construct(
        private readonly SolicitudResidenteRepositoryInterface $solicitudes,
    ) {}

    /** @return SolicitudResidente[] */
    public function execute(string $proyectoId, int $limite = 100): array
    {
        return $this->solicitudes->listarHistorial($proyectoId, min(max($limite, 1), 100));
    }
}
