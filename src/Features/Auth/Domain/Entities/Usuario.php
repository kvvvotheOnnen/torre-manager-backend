<?php

namespace Features\Auth\Domain\Entities;

final class Usuario
{
    public function __construct(
        private readonly string $id,
        private readonly string $nombre,
        private readonly string $correo,
        private readonly Rol $rol,
        private readonly ?string $rut = null,
        private readonly ?string $telefono = null,
        private readonly ?string $telefonoEmergencia = null,
        private readonly ?string $nombreEmergencia = null,
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

    public function rut(): ?string
    {
        return $this->rut;
    }

    public function telefono(): ?string
    {
        return $this->telefono;
    }

    public function telefonoEmergencia(): ?string
    {
        return $this->telefonoEmergencia;
    }

    public function nombreEmergencia(): ?string
    {
        return $this->nombreEmergencia;
    }
}
