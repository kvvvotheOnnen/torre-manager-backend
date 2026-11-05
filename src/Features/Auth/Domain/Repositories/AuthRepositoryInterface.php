<?php

namespace Features\Auth\Domain\Repositories;

use Features\Auth\Domain\Entities\Sesion;
use Features\Auth\Domain\Exceptions\InvalidCredentialsException;

interface AuthRepositoryInterface
{
    /**
     * Verifica las credenciales y retorna la sesión (token + usuario).
     *
     * @throws InvalidCredentialsException
     */
    public function login(string $email, string $password): Sesion;
}
