<?php

namespace Features\Dashboard\Data;

use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Auth\Domain\Entities\Rol;
use Features\Dashboard\Domain\Contracts\VerificadorModulo;
use Features\Dashboard\Domain\Entities\ModuloGeneral;
use Features\Dashboard\Domain\Entities\ModuloSistema;
use Features\Dashboard\Domain\Exceptions\ProyectoNoDisponibleException;
use Features\Dashboard\Domain\Repositories\DashboardRepositoryInterface;

/**
 * Verifica el estado del módulo contra la configuración del proyecto.
 *
 * - El superadmin está exento: opera con capacidades globales y es quien
 *   administra el estado de los módulos.
 * - Módulos configurables: deben estar presentes en `proyectos.modulos`.
 * - Módulos globales: se consideran habilitados salvo que aparezcan en
 *   `proyectos.modulos_deshabilitados`.
 * - Un identificador desconocido se deniega (fail-safe).
 */
final class VerificadorModuloProyecto implements VerificadorModulo
{
    public function __construct(
        private readonly AccesoProyectoService $acceso,
        private readonly DashboardRepositoryInterface $dashboard,
    ) {}

    public function estaHabilitado(
        string $usuarioId,
        string $proyectoId,
        string $moduloId,
    ): bool {
        if ($this->acceso->rolEn($usuarioId, $proyectoId) === Rol::Superadmin) {
            return true;
        }

        try {
            $proyecto = $this->dashboard->obtenerProyectoDelUsuario($usuarioId, $proyectoId);
        } catch (ProyectoNoDisponibleException) {
            return false;
        }

        $sistema = ModuloSistema::tryFrom($moduloId);

        if ($sistema !== null) {
            $habilitados = array_map(
                static fn (ModuloSistema $modulo): string => $modulo->value,
                $proyecto->modulos(),
            );

            return in_array($sistema->value, $habilitados, true);
        }

        $general = ModuloGeneral::tryFrom($moduloId);

        if ($general !== null) {
            return ! in_array($general->value, $proyecto->modulosDeshabilitados(), true);
        }

        return false;
    }
}
