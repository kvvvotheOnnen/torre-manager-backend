<?php

namespace Features\Residente\Domain\Repositories;

use Features\Residente\Domain\Entities\MiInformacion;
use Features\Residente\Domain\Exceptions\UnidadNoAsignadaException;

interface ResidenteRepositoryInterface
{
    /**
     * @throws UnidadNoAsignadaException
     */
    public function obtenerMiInformacion(string $usuarioId): MiInformacion;
}
