<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Features\Auth\Domain\Entities\Rol;
use Features\Dashboard\Domain\Entities\ModuloDashboard;
use Features\Dashboard\Domain\Exceptions\ProyectoNoDisponibleException;
use Features\Dashboard\Domain\UseCases\ObtenerModulosDashboardUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly ObtenerModulosDashboardUseCase $obtenerModulosUseCase,
    ) {}

    public function modulos(Request $request): JsonResponse
    {
        try {
            $modulos = $this->obtenerModulosUseCase->execute(
                (string) $request->user()->getAuthIdentifier(),
                $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id'),
                $request->user()->rol === Rol::Superadmin->value,
            );
        } catch (ProyectoNoDisponibleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json([
            'modulos' => array_map(
                static fn (ModuloDashboard $modulo) => [
                    'id' => $modulo->id(),
                    'nombre' => $modulo->nombre(),
                    'descripcion' => $modulo->descripcion(),
                    'tipo' => $modulo->tipo()->value,
                    'habilitado' => $modulo->habilitado(),
                ],
                $modulos,
            ),
        ]);
    }
}
