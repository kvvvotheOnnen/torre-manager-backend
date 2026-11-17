<?php

namespace Features\Auth\Data\Repositories;

use App\Models\User;
use Features\Auth\Domain\Entities\EstadoUsuario;
use Features\Auth\Domain\Entities\Sesion;
use Features\Auth\Domain\Exceptions\InvalidCredentialsException;
use Features\Auth\Domain\Exceptions\UsuarioNoAprobadoException;
use Features\Auth\Domain\Repositories\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;

final class AuthRepository implements AuthRepositoryInterface
{
    public function login(string $email, string $password): Sesion
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw InvalidCredentialsException::becauseCredentialsAreWrong();
        }

        $estado = EstadoUsuario::tryFrom((string) ($user->estado ?? EstadoUsuario::Activo->value))
            ?? EstadoUsuario::Activo;

        if ($estado !== EstadoUsuario::Activo) {
            throw UsuarioNoAprobadoException::porEstado($estado);
        }

        return new Sesion(
            token: $user->createToken('api')->plainTextToken,
            usuario: $user->toEntity(),
        );
    }
}
