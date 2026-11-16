<?php

namespace Features\Acceso\Data\Repositories;

use App\Models\ProyectoUsuario;
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

        if ($membresia->estado !== 'activo') {
            $membresia->update(['estado' => 'activo']);
        }

        return $this->toEntity($membresia->refresh());
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
