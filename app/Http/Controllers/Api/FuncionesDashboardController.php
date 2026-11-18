<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Features\Dashboard\Domain\Entities\FuncionVisible;
use Features\Dashboard\Domain\Exceptions\ProyectoNoDisponibleException;
use Features\Dashboard\Domain\UseCases\ListarFuncionesDashboardUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FuncionesDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ListarFuncionesDashboardUseCase $casoDeUso,
    ): JsonResponse {
        try {
            $funciones = $casoDeUso->execute(
                (string) $request->user()->getAuthIdentifier(),
                $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id'),
            );
        } catch (ProyectoNoDisponibleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json([
            'funciones' => array_map(
                static fn (FuncionVisible $visible): array => [
                    'id' => $visible->funcion()->id(),
                    'titulo' => $visible->funcion()->titulo(),
                    'descripcion' => $visible->funcion()->descripcion(),
                    'icono' => $visible->funcion()->icono(),
                    'capacidades' => $visible->funcion()->capacidades(),
                    'ambito' => $visible->funcion()->ambito()->value,
                    'ruta' => $visible->funcion()->ruta(),
                    'acciones' => $visible->acciones(),
                ],
                $funciones,
            ),
        ]);
    }
}
