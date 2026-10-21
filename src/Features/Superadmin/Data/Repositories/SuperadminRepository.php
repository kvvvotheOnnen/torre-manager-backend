<?php

namespace Features\Superadmin\Data\Repositories;

use App\Models\Proyecto;
use Features\Superadmin\Domain\Entities\ResumenProyecto;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;

final class SuperadminRepository implements SuperadminRepositoryInterface
{
    public function listarProyectosConConteos(): array
    {
        return Proyecto::query()
            ->withCount(['agrupaciones', 'unidadesPrincipales', 'unidadesAccesorias'])
            ->orderBy('nombre')
            ->get()
            ->map(static fn (Proyecto $proyecto) => new ResumenProyecto(
                id: (string) $proyecto->getKey(),
                nombre: $proyecto->nombre,
                descripcion: $proyecto->descripcion,
                agrupaciones: (int) $proyecto->agrupaciones_count,
                unidadesPrincipales: (int) $proyecto->unidades_principales_count,
                unidadesAccesorias: (int) $proyecto->unidades_accesorias_count,
            ))
            ->all();
    }
}
