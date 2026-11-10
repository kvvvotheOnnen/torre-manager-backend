<?php

namespace Features\Paqueteria\Data\Repositories;

use App\Models\Paquete;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Features\Paqueteria\Domain\Entities\Paquete as PaqueteEntity;
use Features\Paqueteria\Domain\Exceptions\PaqueteNoEncontradoException;
use Features\Paqueteria\Domain\Exceptions\PaqueteYaRetiradoException;
use Features\Paqueteria\Domain\Repositories\PaqueteriaRepositoryInterface;
use Illuminate\Support\Facades\Storage;

final class PaqueteriaRepository implements PaqueteriaRepositoryInterface
{
    public function registrar(array $datos): PaqueteEntity
    {
        $paquete = Paquete::query()->create([
            'proyecto_id' => $datos['proyecto_id'],
            'unidad_principal_id' => $datos['unidad_principal_id'] ?? null,
            'residente_id' => $datos['residente_id'] ?? null,
            'descripcion' => $datos['descripcion'],
            'remitente' => $datos['remitente'] ?? null,
            'foto_path' => $datos['foto_path'] ?? null,
            'fecha_recepcion' => now(),
            'estado' => 'pendiente',
            'registrado_por' => $datos['registrado_por'],
        ]);

        $paquete->load('unidadPrincipal');

        return $paquete->toEntity();
    }

    public function listar(string $usuarioId, string $rol, ?string $proyectoId = null): array
    {
        if ($rol === Rol::Residente->value) {
            $paquetes = Paquete::query()
                ->where('residente_id', $usuarioId)
                ->with(['unidadPrincipal', 'registradoPor'])
                ->orderByDesc('fecha_recepcion')
                ->get();
        } else {
            $query = Paquete::query()
                ->with(['unidadPrincipal', 'residente', 'registradoPor'])
                ->orderByDesc('fecha_recepcion');

            if ($proyectoId !== null && $proyectoId !== '') {
                $query->where('proyecto_id', $proyectoId);
            }

            if ($rol !== Rol::Superadmin->value) {
                $user = User::query()->find($usuarioId);
                $proyectoIds = $user?->proyectos()->pluck('proyectos.id')->toArray() ?? [];
                if ($user?->proyecto_id !== null) {
                    $proyectoIds[] = $user->proyecto_id;
                }
                $proyectoIds = array_values(array_unique($proyectoIds));
                // Un ámbito vacío debe devolver cero paquetes, nunca todos.
                $query->whereIn('proyecto_id', $proyectoIds);
            }

            $paquetes = $query->get();
        }

        return $paquetes->map(fn (Paquete $p) => $p->toEntity())->all();
    }

    public function marcarRetirado(string $paqueteId, string $retiradoPorNombre): PaqueteEntity
    {
        $paquete = Paquete::query()->find($paqueteId);

        if ($paquete === null) {
            throw PaqueteNoEncontradoException::becausePackageIsMissing();
        }

        $paquete->update([
            'estado' => 'retirado',
            'fecha_retiro' => now(),
            'retirado_por_nombre' => $retiradoPorNombre,
        ]);

        $paquete->load('unidadPrincipal');

        return $paquete->fresh()->toEntity();
    }

    public function actualizar(string $paqueteId, array $datos): PaqueteEntity
    {
        $paquete = Paquete::query()->find($paqueteId);

        if ($paquete === null) {
            throw PaqueteNoEncontradoException::becausePackageIsMissing();
        }

        if ($paquete->estado === 'retirado') {
            throw PaqueteYaRetiradoException::becausePackageIsAlreadyPickedUp();
        }

        $fotoAnterior = $paquete->foto_path;
        $campos = [];
        if (array_key_exists('descripcion', $datos)) {
            $campos['descripcion'] = $datos['descripcion'];
        }
        if (array_key_exists('remitente', $datos)) {
            $campos['remitente'] = $datos['remitente'];
        }
        if (array_key_exists('residente_id', $datos)) {
            $campos['residente_id'] = $datos['residente_id'];
        }
        if (array_key_exists('unidad_principal_id', $datos)) {
            $campos['unidad_principal_id'] = $datos['unidad_principal_id'];
        }
        if (array_key_exists('foto_path', $datos) && $datos['foto_path'] !== null) {
            $campos['foto_path'] = $datos['foto_path'];
        }

        if ($campos !== []) {
            $paquete->update($campos);
        }

        if (isset($campos['foto_path']) && $fotoAnterior !== null) {
            Storage::disk('public')->delete($fotoAnterior);
        }

        $paquete->load('unidadPrincipal');

        return $paquete->fresh()->toEntity();
    }
}
