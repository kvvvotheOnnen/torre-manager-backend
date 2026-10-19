<?php

namespace Features\Auth\Domain\UseCases;

use Features\Auth\Domain\Exceptions\InvalidCredentialsException;
use Features\Auth\Domain\Repositories\AuthRepositoryInterface;
use InvalidArgumentException;

final class LoginUseCase
{
    public function __construct(
        private readonly AuthRepositoryInterface $repository,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws InvalidCredentialsException
     */
    public function execute(string $email, string $password): string
    {
        $email = trim($email);

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }

        if ($password === '') {
            throw new InvalidArgumentException('La contraseña es obligatoria.');
        }

        return $this->repository->login($email, $password);
    }
}
