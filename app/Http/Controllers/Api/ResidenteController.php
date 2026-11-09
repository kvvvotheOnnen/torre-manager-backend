<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        ]);
    }
}
