<?php

namespace Features\Acceso\Domain\Services;

use App\Models\UnidadUsuario;
use App\Models\User;
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
        $usuario = User::query()->find($usuarioId);

        if ($usuario?->proyecto_id !== null) {
            $ids[] = (int) $usuario->proyecto_id;
        }

        $idsUnidades = UnidadUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('es_actual', true)
            ->whereHas('unidadPrincipal.agrupacion')
            ->with('unidadPrincipal.agrupacion:id,proyecto_id')
            ->get()
            ->map(fn (UnidadUsuario $v) => (int) $v->unidadPrincipal?->agrupacion?->proyecto_id)
            ->filter()
            ->all();

        $ids = array_merge($ids, $idsUnidades);

        return array_values(array_unique(array_filter($ids)));
    }

    public function rolEn(string $usuarioId, string $proyectoId): ?Rol
    {
        $usuario = User::query()->find($usuarioId);

        if ($usuario === null) {
            return null;
        }

        if ($usuario->rol === Rol::Superadmin->value) {
            return Rol::Superadmin;
        }

        $rol = $this->membresias->rolEn($usuarioId, $proyectoId);

        if ($rol !== null) {
            return $rol;
        }

        if ($usuario->proyecto_id !== null && (string) $usuario->proyecto_id === $proyectoId) {
            return Rol::tryFrom($usuario->rol);
        }

        $tieneUnidad = UnidadUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('es_actual', true)
            ->whereHas('unidadPrincipal.agrupacion', fn ($q) => $q->where('proyecto_id', $proyectoId))
            ->exists();

        if ($tieneUnidad) {
            return $usuario->proyecto_id !== null ? Rol::tryFrom($usuario->rol) : Rol::Residente;
        }

        return null;
    }

    public function puedeAcceder(string $usuarioId, string $proyectoId): bool
    {
        return $this->rolEn($usuarioId, $proyectoId) !== null;
    }
}
