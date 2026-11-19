<?php

namespace Features\Dashboard\Domain\Services;

use Features\Dashboard\Domain\Entities\ModuloDashboard;
use Features\Dashboard\Domain\Entities\ModuloGeneral;
use Features\Dashboard\Domain\Entities\ModuloSistema;
use Features\Dashboard\Domain\Entities\ProyectoConModulos;
use Features\Dashboard\Domain\Entities\TipoModuloDashboard;

final class CatalogoModulosDashboard
{
    /**
     * Combina los módulos configurables del proyecto con los módulos globales.
     *
     * Los módulos configurables siempre se informan; si no están presentes en
     * `proyectos.modulos` se devuelven bloqueados. Los módulos globales se
     * devuelven habilitados salvo que el superadmin los haya deshabilitado
     * para el proyecto en `proyectos.modulos_deshabilitados`.
     *
     * @return ModuloDashboard[]
     */
    public function construir(ProyectoConModulos $proyecto, bool $esSuperadmin = false): array
    {
        $habilitados = array_map(
            static fn (ModuloSistema $modulo) => $modulo->value,
            $proyecto->modulos(),
        );
        $deshabilitados = $proyecto->modulosDeshabilitados();

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
            if ($esSuperadmin && $modulo === ModuloGeneral::AsistenteIaAdmin) {
                continue;
            }

            $modulos[] = new ModuloDashboard(
                id: $modulo->value,
                nombre: $modulo->nombre(),
                descripcion: $modulo->descripcion(),
                tipo: TipoModuloDashboard::General,
                habilitado: ! in_array($modulo->value, $deshabilitados, true),
            );
        }

        return $modulos;
    }
}
