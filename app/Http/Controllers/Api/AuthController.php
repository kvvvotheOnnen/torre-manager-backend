<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Features\Auth\Domain\Exceptions\InvalidCredentialsException;
use Features\Auth\Domain\UseCases\LoginUseCase;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginUseCase $loginUseCase,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $sesion = $this->loginUseCase->execute(
                $request->string('email')->toString(),
                $request->string('password')->toString(),
            );
        } catch (InvalidCredentialsException) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        $usuario = $sesion->usuario();

        return response()->json([
            'access_token' => $sesion->token(),
            'token_type' => 'Bearer',
            'user' => [
                'id' => $usuario->id(),
                'nombre' => $usuario->nombre(),
                'correo' => $usuario->correo(),
                'rol' => $usuario->rol()->value,
            ],
        ], 200);
    }
}
