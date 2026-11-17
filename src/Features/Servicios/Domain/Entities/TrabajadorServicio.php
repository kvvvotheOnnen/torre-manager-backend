<?php

namespace Features\Servicios\Domain\Entities;

final class TrabajadorServicio
{
    public function __construct(
        private readonly string $id,
        private readonly string $proyectoId,
        private readonly string $nombre,
        private readonly ?string $nombreEmpresa,
        private readonly string $telefono,
        private readonly TipoServicio $tipo,
        private readonly ?string $descripcion,
        private readonly ?string $fotoPath,
        private readonly ?string $creadoPor,
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

    public function nombreEmpresa(): ?string
    {
        return $this->nombreEmpresa;
    }

    public function telefono(): string
    {
        return $this->telefono;
    }

    public function tipo(): TipoServicio
    {
        return $this->tipo;
    }

    public function descripcion(): ?string
    {
        return $this->descripcion;
    }

    public function fotoPath(): ?string
    {
        return $this->fotoPath;
    }

    public function creadoPor(): ?string
    {
        return $this->creadoPor;
    }
}
