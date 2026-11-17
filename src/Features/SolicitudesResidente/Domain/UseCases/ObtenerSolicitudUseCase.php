<?php

namespace Features\SolicitudesResidente\Domain\UseCases;

use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Exceptions\SolicitudNoEncontradaException;
use Features\SolicitudesResidente\Domain\Repositories\SolicitudResidenteRepositoryInterface;

final class ObtenerSolicitudUseCase
{
    public function __construct(
        private readonly SolicitudResidenteRepositoryInterface $solicitudes,
    ) {}

    public function execute(string $id): SolicitudResidente
    {
        return $this->solicitudes->obtener($id)
            ?? throw SolicitudNoEncontradaException::porqueNoExiste();
    }
}
