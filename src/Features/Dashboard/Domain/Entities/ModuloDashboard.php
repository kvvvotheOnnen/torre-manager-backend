<?php

namespace Features\Dashboard\Domain\Entities;

final class ModuloDashboard
{
    public function __construct(
        private readonly string $id,
        private readonly string $nombre,
        private readonly string $descripcion,
        private readonly TipoModuloDashboard $tipo,
        private readonly bool $habilitado,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function tipo(): TipoModuloDashboard
    {
        return $this->tipo;
    }

    public function habilitado(): bool
    {
        return $this->habilitado;
    }
}
