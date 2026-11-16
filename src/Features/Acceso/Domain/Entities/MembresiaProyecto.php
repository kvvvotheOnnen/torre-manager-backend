<?php

namespace Features\Acceso\Domain\Entities;

use Features\Auth\Domain\Entities\Rol;

final class MembresiaProyecto
{
    public function __construct(
        private readonly string $id,
        private readonly string $proyectoId,
        private readonly string $usuarioId,
        private readonly Rol $rol,
        private readonly string $estado,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function usuarioId(): string
    {
        return $this->usuarioId;
    }

    public function rol(): Rol
    {
        return $this->rol;
    }

    public function estado(): string
    {
        return $this->estado;
    }

    public function estaActiva(): bool
    {
        return $this->estado === 'activo';
    }
}
