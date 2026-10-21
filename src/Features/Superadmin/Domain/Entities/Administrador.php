<?php

namespace Features\Superadmin\Domain\Entities;

use Features\Auth\Domain\Entities\Rol;

final class Administrador
{
    public function __construct(
        private readonly string $id,
        private readonly string $nombre,
        private readonly string $correo,
        private readonly Rol $rol,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function correo(): string
    {
        return $this->correo;
    }

    public function rol(): Rol
    {
        return $this->rol;
    }
}
