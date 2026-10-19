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
            $token = $this->loginUseCase->execute(
                $request->string('email')->toString(),
                $request->string('password')->toString(),
            );
        } catch (InvalidCredentialsException) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }
}
