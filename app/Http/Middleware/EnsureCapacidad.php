<?php

namespace App\Http\Middleware;

use Closure;
use Features\Dashboard\Domain\Contracts\AutorizadorCapacidades;
use Features\Dashboard\Domain\Contracts\VerificadorModulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige una o más capacidades dentro del proyecto activo y, cuando la
 * capacidad pertenece a un módulo, que ese módulo esté habilitado.
 *
 * Uso: ->middleware('puede:control_acceso.registrar')
 * Varias capacidades se evalúan con Y lógico.
 *
 * El mapa capacidad → módulo vive en `config/modulos.php`; así los módulos
 * nuevos quedan protegidos por API sin tocar este middleware ni las rutas.
 */
final class EnsureCapacidad
{
    public function __construct(
        private readonly AutorizadorCapacidades $autorizador,
        private readonly VerificadorModulo $verificadorModulo,
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
        $proyectoId = (string) $proyectoId;

        foreach ($capacidades as $capacidad) {
            if (! $this->autorizador->puede($usuarioId, $proyectoId, $capacidad)) {
                return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
            }

            $moduloId = $this->moduloDeCapacidad($capacidad);

            if ($moduloId !== null
                && ! $this->verificadorModulo->estaHabilitado($usuarioId, $proyectoId, $moduloId)) {
                Log::warning('Acceso denegado a módulo deshabilitado', [
                    'proyecto_id' => $proyectoId,
                    'modulo' => $moduloId,
                    'ruta' => $request->path(),
                ]);

                return response()->json([
                    'message' => 'El módulo no está habilitado para este proyecto.',
                ], 403);
            }
        }

        return $next($request);
    }

    private function moduloDeCapacidad(string $capacidad): ?string
    {
        /** @var array<string, array<int, string>> $mapa */
        $mapa = config('modulos.capacidades', []);

        foreach ($mapa as $modulo => $capacidades) {
            if (in_array($capacidad, $capacidades, true)) {
                return $modulo;
            }
        }

        return null;
    }
}
