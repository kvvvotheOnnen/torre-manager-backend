<?php

namespace Features\Superadmin\Domain\Entities;

final class ResumenProyecto
{
    public function __construct(
        private readonly string $id,
        private readonly string $nombre,
        private readonly ?string $descripcion,
        private readonly int $agrupaciones,
        private readonly int $unidadesPrincipales,
        private readonly int $unidadesAccesorias,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): ?string
    {
        return $this->descripcion;
    }

    public function agrupaciones(): int
    {
        return $this->agrupaciones;
    }

    public function unidadesPrincipales(): int
    {
        return $this->unidadesPrincipales;
    }

    public function unidadesAccesorias(): int
    {
        return $this->unidadesAccesorias;
    }
}
