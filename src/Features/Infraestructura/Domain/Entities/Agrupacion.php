<?php

namespace Features\Infraestructura\Domain\Entities;

final class Agrupacion
{
    public function __construct(
        private readonly string $id,
        private readonly string $proyectoId,
        private readonly string $nombre,
        private readonly TipoAgrupacion $tipo,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function tipo(): TipoAgrupacion
    {
        return $this->tipo;
    }
}
