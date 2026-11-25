<?php

namespace Features\SolicitudesResidente\Domain\Entities;

final class ProyectoRegistro
{
    public function __construct(
        private readonly string $id,
        private readonly string $nombre,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }
}
