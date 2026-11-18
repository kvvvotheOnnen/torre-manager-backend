<?php

namespace App\Http\Middleware;

use Closure;
use Features\Dashboard\Domain\Contracts\AutorizadorCapacidades;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige una o más capacidades dentro del proyecto activo.
 *
 * Uso: ->middleware('puede:control_acceso.registrar')
 * Varias capacidades se evalúan con Y lógico.
 */
final class EnsureCapacidad
{
    public function __construct(
        private readonly AutorizadorCapacidades $autorizador,
    ) {}

    public function handle(Request $request, Closure $next, string ...$capacidades): Response
    {
        $usuario = $request->user();

        if ($usuario === null) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $proyectoId = $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id');

        if ($proyectoId === null || $proyectoId === '') {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $usuarioId = (string) $usuario->getAuthIdentifier();

        foreach ($capacidades as $capacidad) {
            if (! $this->autorizador->puede($usuarioId, (string) $proyectoId, $capacidad)) {
                return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
            }
        }

        return $next($request);
    }
}
