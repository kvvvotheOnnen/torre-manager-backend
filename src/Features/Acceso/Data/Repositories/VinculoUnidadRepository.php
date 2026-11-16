<?php

namespace Features\Acceso\Data\Repositories;

use App\Models\UnidadPrincipal;
use App\Models\UnidadUsuario;
use Features\Acceso\Domain\Entities\TipoVinculo;
use Features\Acceso\Domain\Entities\VinculoUnidad;
use Features\Acceso\Domain\Exceptions\VinculoNoEncontradoException;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;

final class VinculoUnidadRepository implements VinculoUnidadRepositoryInterface
{
    public function listarPorUsuario(string $usuarioId, ?string $proyectoId = null): array
    {
        $query = UnidadUsuario::query()
            ->with(['usuario', 'unidadPrincipal.agrupacion'])
            ->where('usuario_id', $usuarioId)
            ->orderByDesc('es_actual')
            ->orderByDesc('fecha_inicio');

        if ($proyectoId !== null && $proyectoId !== '') {
            $query->whereHas('unidadPrincipal.agrupacion', fn ($q) => $q->where('proyecto_id', $proyectoId));
        }

        return $query->get()->map(fn (UnidadUsuario $v) => $this->toEntity($v))->all();
    }

    public function listarPorUnidad(string $unidadPrincipalId): array
    {
        return UnidadUsuario::query()
            ->with(['usuario', 'unidadPrincipal.agrupacion'])
            ->where('unidad_principal_id', $unidadPrincipalId)
            ->orderByDesc('es_actual')
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(fn (UnidadUsuario $v) => $this->toEntity($v))
            ->all();
    }

    public function crear(
        string $unidadPrincipalId,
        string $usuarioId,
        TipoVinculo $tipo,
        string $fechaInicio,
        ?string $fechaFin = null,
    ): VinculoUnidad {
        $vinculo = UnidadUsuario::create([
            'unidad_principal_id' => $unidadPrincipalId,
            'usuario_id' => $usuarioId,
            'tipo_vinculo' => $tipo->value,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'es_actual' => $fechaFin === null,
        ]);

        $vinculo->load(['usuario', 'unidadPrincipal']);

        return $this->toEntity($vinculo);
    }

    public function cerrar(string $vinculoId, string $fechaFin): VinculoUnidad
    {
        $vinculo = UnidadUsuario::query()->find($vinculoId);

        if ($vinculo === null) {
            throw VinculoNoEncontradoException::becauseVinculoIsMissing();
        }

        $vinculo->update([
            'fecha_fin' => $fechaFin,
            'es_actual' => false,
        ]);

        $vinculo->load(['usuario', 'unidadPrincipal']);

        return $this->toEntity($vinculo);
    }

    public function cerrarActivosDeTipo(string $unidadPrincipalId, TipoVinculo $tipo, string $fechaFin): int
    {
        return UnidadUsuario::query()
            ->where('unidad_principal_id', $unidadPrincipalId)
            ->where('tipo_vinculo', $tipo->value)
            ->where('es_actual', true)
            ->update([
                'fecha_fin' => $fechaFin,
                'es_actual' => false,
                'updated_at' => now(),
            ]);
    }

    public function obtener(string $vinculoId): ?VinculoUnidad
    {
        $vinculo = UnidadUsuario::query()
            ->with(['usuario', 'unidadPrincipal.agrupacion'])
            ->find($vinculoId);

        return $vinculo !== null ? $this->toEntity($vinculo) : null;
    }

    public function unidadPerteneceAProyecto(string $unidadPrincipalId, string $proyectoId): bool
    {
        return UnidadPrincipal::query()
            ->whereKey($unidadPrincipalId)
            ->whereHas('agrupacion', fn ($q) => $q->where('proyecto_id', $proyectoId))
            ->exists();
    }

    public function sincronizarOcupanteLegacy(string $unidadPrincipalId, ?string $usuarioId): void
    {
        UnidadPrincipal::query()
            ->whereKey($unidadPrincipalId)
            ->update(['residente_id' => $usuarioId]);
    }

    public function propietarioActual(string $unidadPrincipalId): ?VinculoUnidad
    {
        $vinculo = UnidadUsuario::query()
            ->with(['usuario', 'unidadPrincipal.agrupacion'])
            ->where('unidad_principal_id', $unidadPrincipalId)
            ->where('tipo_vinculo', TipoVinculo::Propietario->value)
            ->where('es_actual', true)
            ->first();

        return $vinculo !== null ? $this->toEntity($vinculo) : null;
    }

    public function ocupanteActual(string $unidadPrincipalId): ?VinculoUnidad
    {
        $vinculo = UnidadUsuario::query()
            ->with(['usuario', 'unidadPrincipal.agrupacion'])
            ->where('unidad_principal_id', $unidadPrincipalId)
            ->whereIn('tipo_vinculo', [
                TipoVinculo::Arrendatario->value,
                TipoVinculo::Residente->value,
            ])
            ->where('es_actual', true)
            ->orderByRaw("case when tipo_vinculo = 'arrendatario' then 0 else 1 end")
            ->first();

        return $vinculo !== null ? $this->toEntity($vinculo) : null;
    }

    private function toEntity(UnidadUsuario $vinculo): VinculoUnidad
    {
        return new VinculoUnidad(
            id: (string) $vinculo->getKey(),
            unidadPrincipalId: (string) $vinculo->unidad_principal_id,
            usuarioId: (string) $vinculo->usuario_id,
            tipo: TipoVinculo::from($vinculo->tipo_vinculo),
            fechaInicio: $vinculo->fecha_inicio?->toDateString() ?? '',
            fechaFin: $vinculo->fecha_fin?->toDateString(),
            esActual: (bool) $vinculo->es_actual,
            usuarioNombre: $vinculo->usuario?->nombre,
            unidadIdentificador: $vinculo->unidadPrincipal?->identificador,
            proyectoId: $vinculo->unidadPrincipal?->agrupacion?->proyecto_id !== null
                ? (string) $vinculo->unidadPrincipal->agrupacion->proyecto_id
                : null,
            agrupacionNombre: $vinculo->unidadPrincipal?->agrupacion?->nombre,
        );
    }
}
