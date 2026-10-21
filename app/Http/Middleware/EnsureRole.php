<?php

namespace App\Http\Middleware;

use Closure;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        try {
            $rol = Rol::from($user->rol);
        } catch (\ValueError) {
            return response()->json(['message' => 'El rol del usuario no es válido.'], 403);
        }

        foreach ($roles as $role) {
            if (Rol::tryFrom($role) === $rol) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
    }
}
