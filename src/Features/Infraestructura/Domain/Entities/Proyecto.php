<?php

namespace Features\Infraestructura\Domain\Entities;

final class Proyecto
{
    public function __construct(
        private readonly string $id,
        private readonly string $nombre,
        private readonly ?string $descripcion,
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
}
