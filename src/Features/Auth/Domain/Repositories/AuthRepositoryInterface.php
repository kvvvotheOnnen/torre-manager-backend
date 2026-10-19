<?php

namespace Features\Auth\Domain\Repositories;

use Features\Auth\Domain\Exceptions\InvalidCredentialsException;

interface AuthRepositoryInterface
{
    /**
     * Verifica las credenciales y retorna un token de acceso.
     *
     * @throws InvalidCredentialsException
     */
    public function login(string $email, string $password): string;
}
