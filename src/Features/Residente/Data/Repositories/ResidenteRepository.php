<?php

namespace Features\Residente\Data\Repositories;

use App\Models\UnidadPrincipal;
use App\Models\UnidadUsuario;
use Features\Residente\Domain\Entities\MiInformacion;
use Features\Residente\Domain\Entities\UnidadVinculada;
use Features\Residente\Domain\Exceptions\UnidadNoAsignadaException;
use Features\Residente\Domain\Repositories\ResidenteRepositoryInterface;

final class ResidenteRepository implements ResidenteRepositoryInterface
{
    public function obtenerMiInformacion(string $usuarioId): MiInformacion
    {
        $vinculos = UnidadUsuario::query()
            ->with('unidadPrincipal.agrupacion')
            ->where('usuario_id', $usuarioId)
            ->orderByDesc('es_actual')
            ->orderByDesc('fecha_inicio')
            ->get();

        $unidades = $vinculos
            ->filter(fn (UnidadUsuario $v) => $v->unidadPrincipal?->agrupacion !== null)
            ->map(fn (UnidadUsuario $v) => new UnidadVinculada(
                unidad: $v->unidadPrincipal->identificador,
                agrupacion: $v->unidadPrincipal->agrupacion->nombre,
                proyectoId: (string) $v->unidadPrincipal->agrupacion->proyecto_id,
                tipoVinculo: $v->tipo_vinculo,
                esActual: (bool) $v->es_actual,
                fechaInicio: $v->fecha_inicio?->toDateString(),
                fechaFin: $v->fecha_fin?->toDateString(),
            ))
            ->values()
            ->all();

        if ($unidades === []) {
            $unidad = UnidadPrincipal::query()
                ->where('residente_id', $usuarioId)
                ->with('agrupacion')
                ->first();

            if ($unidad !== null && $unidad->agrupacion !== null) {
                $unidades[] = new UnidadVinculada(
                    unidad: $unidad->identificador,
                    agrupacion: $unidad->agrupacion->nombre,
                    proyectoId: (string) $unidad->agrupacion->proyecto_id,
                    tipoVinculo: 'residente',
                    esActual: true,
                );
            }
        }

        if ($unidades === []) {
            throw UnidadNoAsignadaException::becauseResidenteHasNoUnit();
        }

        return new MiInformacion($unidades);
    }
}
