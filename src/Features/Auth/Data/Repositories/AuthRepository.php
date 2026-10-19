<?php

namespace Features\Auth\Data\Repositories;

use App\Models\User;
use Features\Auth\Domain\Exceptions\InvalidCredentialsException;
use Features\Auth\Domain\Repositories\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;

final class AuthRepository implements AuthRepositoryInterface
{
    public function login(string $email, string $password): string
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw InvalidCredentialsException::becauseCredentialsAreWrong();
        }

        return $user->createToken('api')->plainTextToken;
    }
}
