<?php

namespace App\Http\Middleware;

use Closure;
use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureProjectRole
{
    public function __construct(
        private readonly AccesoProyectoService $acceso,
    ) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $proyectoId = $request->header('X-Proyecto-Id') ?? $request->input('proyecto_id');

        if ($proyectoId === null || $proyectoId === '') {
            $rol = Rol::tryFrom((string) $user->rol);

            if ($rol !== null && $this->coincide($rol, $roles)) {
                return $next($request);
            }

            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $rol = $this->acceso->rolEn((string) $user->getAuthIdentifier(), (string) $proyectoId);

        if ($rol === null) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        if ($rol === Rol::Superadmin || $this->coincide($rol, $roles)) {
            return $next($request);
        }

        return response()->json(['message' => 'No tienes permisos en el proyecto activo.'], 403);
    }

    /** @param string[] $roles */
    private function coincide(Rol $rol, array $roles): bool
    {
        foreach ($roles as $role) {
            if (Rol::tryFrom($role) === $rol) {
                return true;
            }
        }

        return false;
    }
}
