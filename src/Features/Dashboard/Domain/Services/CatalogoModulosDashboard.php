<?php

namespace Features\Dashboard\Domain\Services;

use Features\Dashboard\Domain\Entities\ModuloDashboard;
use Features\Dashboard\Domain\Entities\ModuloGeneral;
use Features\Dashboard\Domain\Entities\ProyectoConModulos;
use Features\Dashboard\Domain\Entities\TipoModuloDashboard;
use Features\Superadmin\Domain\Entities\ModuloSistema;

final class CatalogoModulosDashboard
{
    /**
     * Combina los módulos configurables del proyecto con los módulos globales.
     *
     * Los módulos configurables siempre se informan; si no están presentes en
     * `proyectos.modulos` se devuelven bloqueados. Los módulos globales siempre
     * se devuelven habilitados.
     *
     * @return ModuloDashboard[]
     */
    public function construir(ProyectoConModulos $proyecto, bool $esSuperadmin = false): array
    {
        $habilitados = array_map(
            static fn (ModuloSistema $modulo) => $modulo->value,
            $proyecto->modulos(),
        );

        $modulos = [];

        foreach (ModuloSistema::cases() as $modulo) {
            if (! $esSuperadmin && $modulo->esExclusivoSuperadmin()) {
                continue;
            }

            $modulos[] = new ModuloDashboard(
                id: $modulo->value,
                nombre: $modulo->nombre(),
                descripcion: $modulo->descripcion(),
                tipo: TipoModuloDashboard::Proyecto,
                habilitado: in_array($modulo->value, $habilitados, true),
            );
        }

        foreach (ModuloGeneral::cases() as $modulo) {
            $modulos[] = new ModuloDashboard(
                id: $modulo->value,
                nombre: $modulo->nombre(),
                descripcion: $modulo->descripcion(),
                tipo: TipoModuloDashboard::General,
                habilitado: true,
            );
        }

        return $modulos;
    }
}
