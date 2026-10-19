<?php

namespace Features\Auth\Domain\Entities;

final class Sesion
{
    public function __construct(
        private readonly string $token,
        private readonly Usuario $usuario,
    ) {}

    public function token(): string
    {
        return $this->token;
    }

    public function usuario(): Usuario
    {
        return $this->usuario;
    }
}
