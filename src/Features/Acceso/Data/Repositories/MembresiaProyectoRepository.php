<?php

namespace Features\Acceso\Data\Repositories;

use App\Models\ProyectoUsuario;
use App\Models\UnidadUsuario;
use App\Models\User;
use Features\Acceso\Domain\Entities\MembresiaProyecto;
use Features\Acceso\Domain\Repositories\MembresiaProyectoRepositoryInterface;
use Features\Auth\Domain\Entities\Rol;

final class MembresiaProyectoRepository implements MembresiaProyectoRepositoryInterface
{
    public function listarPorUsuario(string $usuarioId): array
    {
        return ProyectoUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('estado', 'activo')
            ->orderBy('proyecto_id')
            ->get()
            ->map(fn (ProyectoUsuario $membresia) => $this->toEntity($membresia))
            ->all();
    }

    public function proyectosIds(string $usuarioId): array
    {
        return ProyectoUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('estado', 'activo')
            ->pluck('proyecto_id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function rolEn(string $usuarioId, string $proyectoId): ?Rol
    {
        $membresia = ProyectoUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('proyecto_id', $proyectoId)
            ->where('estado', 'activo')
            ->first();

        return $membresia !== null ? Rol::tryFrom($membresia->rol) : null;
    }

    public function asegurar(string $proyectoId, string $usuarioId, Rol $rol): MembresiaProyecto
    {
        $membresia = ProyectoUsuario::query()->firstOrCreate(
            ['proyecto_id' => $proyectoId, 'usuario_id' => $usuarioId],
            ['rol' => $rol->value, 'estado' => 'activo'],
        );

        $cambios = [];

        if ($membresia->estado !== 'activo') {
            $cambios['estado'] = 'activo';
        }

        if ($membresia->rol === Rol::Residente->value && $rol === Rol::Propietario) {
            $cambios['rol'] = Rol::Propietario->value;
        }

        if ($cambios !== []) {
            $membresia->update($cambios);
        }

        return $this->toEntity($membresia->refresh());
    }

    public function rolGlobalDeUsuario(string $usuarioId): ?Rol
    {
        $rol = User::query()->whereKey($usuarioId)->value('rol');

        return $rol !== null ? Rol::tryFrom((string) $rol) : null;
    }

    public function proyectoLegacyDeUsuario(string $usuarioId): ?string
    {
        $proyectoId = User::query()->whereKey($usuarioId)->value('proyecto_id');

        return $proyectoId !== null ? (string) $proyectoId : null;
    }

    public function proyectosPorUnidades(string $usuarioId): array
    {
        return UnidadUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('es_actual', true)
            ->whereHas('unidadPrincipal.agrupacion')
            ->with('unidadPrincipal.agrupacion:id,proyecto_id')
            ->get()
            ->map(fn (UnidadUsuario $v) => (int) $v->unidadPrincipal?->agrupacion?->proyecto_id)
            ->filter()
            ->values()
            ->all();
    }

    public function tieneUnidadActualEnProyecto(string $usuarioId, string $proyectoId): bool
    {
        return UnidadUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('es_actual', true)
            ->whereHas(
                'unidadPrincipal.agrupacion',
                fn ($q) => $q->where('proyecto_id', $proyectoId),
            )
            ->exists();
    }

    private function toEntity(ProyectoUsuario $membresia): MembresiaProyecto
    {
        return new MembresiaProyecto(
            id: (string) $membresia->getKey(),
            proyectoId: (string) $membresia->proyecto_id,
            usuarioId: (string) $membresia->usuario_id,
            rol: Rol::from($membresia->rol),
            estado: $membresia->estado,
        );
    }
}
