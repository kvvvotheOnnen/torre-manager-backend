<?php

namespace Features\SolicitudesResidente\Domain\Entities;

use Features\Acceso\Domain\Entities\TipoVinculo;

final class DatosPersonaRegistro
{
    public function __construct(
        private readonly string $nombre,
        private readonly string $rut,
        private readonly string $email,
        private readonly string $telefono,
        private readonly string $telefonoEmergencia,
        private readonly string $nombreEmergencia,
        private readonly TipoVinculo $tipoVinculo,
        private readonly bool $esPrincipal,
    ) {}

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function rut(): string
    {
        return $this->rut;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function telefono(): string
    {
        return $this->telefono;
    }

    public function telefonoEmergencia(): string
    {
        return $this->telefonoEmergencia;
    }

    public function nombreEmergencia(): string
    {
        return $this->nombreEmergencia;
    }

    public function tipoVinculo(): TipoVinculo
    {
        return $this->tipoVinculo;
    }

    public function esPrincipal(): bool
    {
        return $this->esPrincipal;
    }
}
