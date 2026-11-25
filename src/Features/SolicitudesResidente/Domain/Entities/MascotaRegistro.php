<?php

namespace Features\SolicitudesResidente\Domain\Entities;

final class MascotaRegistro
{
    public function __construct(
        private readonly string $nombre,
        private readonly EspecieMascota $especie,
        private readonly string $raza,
        private readonly ?string $microchip = null,
        private readonly ?string $fotoUrl = null,
    ) {}

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function especie(): EspecieMascota
    {
        return $this->especie;
    }

    public function raza(): string
    {
        return $this->raza;
    }

    public function microchip(): ?string
    {
        return $this->microchip;
    }

    public function fotoUrl(): ?string
    {
        return $this->fotoUrl;
    }
}
