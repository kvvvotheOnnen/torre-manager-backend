<?php

namespace Features\SolicitudesResidente\Domain\UseCases;

use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Repositories\SolicitudResidenteRepositoryInterface;

final class ListarMisSolicitudesUseCase
{
    public function __construct(
        private readonly SolicitudResidenteRepositoryInterface $solicitudes,
    ) {}

    /** @return SolicitudResidente[] */
    public function execute(string $usuarioId): array
    {
        return $this->solicitudes->listarPorUsuario($usuarioId);
    }
}
