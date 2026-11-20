<?php

namespace App\Http\Middleware;

use Closure;
use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Dashboard\Domain\Contracts\VerificadorModulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que un módulo esté habilitado para el proyecto activo.
 *
 * Uso: ->middleware('modulo:infraestructura')
 *
 * Está pensado para rutas que no se autorizan por capacidad. Cuando una ruta
 * usa `puede:<capacidad>`, el enforcement del módulo ya ocurre en
 * EnsureCapacidad según `config/modulos.php`.
 */
final class EnsureModulo
{
    public function __construct(
        private readonly VerificadorModulo $verificadorModulo,
        private readonly AccesoProyectoService $acceso,
    ) {}

    public function handle(Request $request, Closure $next, string $moduloId): Response
    {
        $usuario = $request->user();

        if ($usuario === null) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $usuarioId = (string) $usuario->getAuthIdentifier();

        // El superadmin está exento del estado de módulo (igual que en
        // VerificadorModuloProyecto). No se le exige un proyecto activo para
        // operar en módulos transversales como infraestructura.
        if ($this->acceso->esSuperadmin($usuarioId)) {
            return $next($request);
        }

        $proyectoId = $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id');

        if ($proyectoId === null || $proyectoId === '') {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $proyectoId = (string) $proyectoId;

        if (! $this->verificadorModulo->estaHabilitado($usuarioId, $proyectoId, $moduloId)) {
            Log::warning('Acceso denegado a módulo deshabilitado', [
                'proyecto_id' => $proyectoId,
                'modulo' => $moduloId,
                'ruta' => $request->path(),
            ]);

            return response()->json([
                'message' => 'El módulo no está habilitado para este proyecto.',
            ], 403);
        }

        return $next($request);
    }
}
