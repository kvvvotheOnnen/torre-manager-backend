<?php

namespace Features\Residente\Data\Repositories;

use App\Models\UnidadPrincipal;
use Features\Residente\Domain\Entities\MiInformacion;
use Features\Residente\Domain\Exceptions\UnidadNoAsignadaException;
use Features\Residente\Domain\Repositories\ResidenteRepositoryInterface;

final class ResidenteRepository implements ResidenteRepositoryInterface
{
    public function obtenerMiInformacion(string $usuarioId): MiInformacion
    {
        $unidad = UnidadPrincipal::query()
            ->where('residente_id', $usuarioId)
            ->with('agrupacion')
            ->first();

        if ($unidad === null) {
            throw UnidadNoAsignadaException::becauseResidenteHasNoUnit();
        }

        return new MiInformacion(
            unidad: $unidad->identificador,
            agrupacion: $unidad->agrupacion?->nombre ?? '',
        );
    }
}
