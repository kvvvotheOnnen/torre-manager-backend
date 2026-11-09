<?php

namespace Features\Residente\Domain\UseCases;

use Features\Residente\Domain\Entities\MiInformacion;
use Features\Residente\Domain\Exceptions\UnidadNoAsignadaException;
use Features\Residente\Domain\Repositories\ResidenteRepositoryInterface;

final class ObtenerMiInformacionUseCase
{
    public function __construct(
        private readonly ResidenteRepositoryInterface $repository,
    ) {}

    /**
     * @throws UnidadNoAsignadaException
     */
    public function execute(string $usuarioId): MiInformacion
    {
        return $this->repository->obtenerMiInformacion($usuarioId);
    }
}
