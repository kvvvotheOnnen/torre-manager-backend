<?php

namespace Features\Acceso\Domain\Services;

use Features\Acceso\Domain\Entities\MembresiaProyecto;
use Features\Acceso\Domain\Repositories\MembresiaProyectoRepositoryInterface;
use Features\Auth\Domain\Entities\Rol;

final class AccesoProyectoService
{
    public function __construct(
        private readonly MembresiaProyectoRepositoryInterface $membresias,
    ) {}

    /** @return MembresiaProyecto[] */
    public function membresiasDe(string $usuarioId): array
    {
        return $this->membresias->listarPorUsuario($usuarioId);
    }

    /** @return int[] */
    public function proyectosIdsDe(string $usuarioId): array
    {
        $ids = $this->membresias->proyectosIds($usuarioId);

        $proyectoLegacy = $this->membresias->proyectoLegacyDeUsuario($usuarioId);
        if ($proyectoLegacy !== null) {
            $ids[] = (int) $proyectoLegacy;
        }

        $ids = array_merge($ids, $this->membresias->proyectosPorUnidades($usuarioId));

        return array_values(array_unique(array_filter($ids)));
    }

    public function rolEn(string $usuarioId, string $proyectoId): ?Rol
    {
        $rolGlobal = $this->membresias->rolGlobalDeUsuario($usuarioId);

        if ($rolGlobal === null) {
            return null;
        }

        if ($rolGlobal === Rol::Superadmin) {
            return Rol::Superadmin;
        }

        $rol = $this->membresias->rolEn($usuarioId, $proyectoId);

        if ($rol !== null) {
            return $rol;
        }

        $proyectoLegacy = $this->membresias->proyectoLegacyDeUsuario($usuarioId);

        if ($proyectoLegacy !== null && $proyectoLegacy === $proyectoId) {
            return Rol::tryFrom($rolGlobal->value);
        }

        if ($this->membresias->tieneUnidadActualEnProyecto($usuarioId, $proyectoId)) {
            return $proyectoLegacy !== null ? Rol::tryFrom($rolGlobal->value) : Rol::Residente;
        }

        return null;
    }

    public function esSuperadmin(string $usuarioId): bool
    {
        return $this->membresias->rolGlobalDeUsuario($usuarioId) === Rol::Superadmin;
    }

    public function puedeAcceder(string $usuarioId, string $proyectoId): bool
    {
        return $this->rolEn($usuarioId, $proyectoId) !== null;
    }
}
