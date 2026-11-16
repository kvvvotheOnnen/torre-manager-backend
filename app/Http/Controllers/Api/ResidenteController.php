<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Features\Residente\Domain\Entities\UnidadVinculada;
use Features\Residente\Domain\Exceptions\UnidadNoAsignadaException;
use Features\Residente\Domain\UseCases\ObtenerMiInformacionUseCase;
use Illuminate\Http\JsonResponse;

final class ResidenteController extends Controller
{
    public function __construct(
        private readonly ObtenerMiInformacionUseCase $obtenerMiInformacionUseCase,
    ) {}

    public function miInformacion(): JsonResponse
    {
        try {
            $informacion = $this->obtenerMiInformacionUseCase->execute(
                (string) request()->user()->getAuthIdentifier(),
            );
        } catch (UnidadNoAsignadaException) {
            return response()->json([
                'message' => 'No tienes una unidad asignada.',
            ], 404);
        }

        return response()->json([
            'unidad' => $informacion->unidad(),
            'agrupacion' => $informacion->agrupacion(),
            'unidades' => array_map(
                static fn (UnidadVinculada $unidad) => [
                    'unidad' => $unidad->unidad(),
                    'agrupacion' => $unidad->agrupacion(),
                    'proyecto_id' => $unidad->proyectoId(),
                    'tipo_vinculo' => $unidad->tipoVinculo(),
                    'es_actual' => $unidad->esActual(),
                    'fecha_inicio' => $unidad->fechaInicio(),
                    'fecha_fin' => $unidad->fechaFin(),
                ],
                $informacion->unidades(),
            ),
        ]);
    }
}
