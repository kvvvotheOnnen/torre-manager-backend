<?php

namespace Features\SolicitudesResidente\Domain\Entities;

final class InstalacionUnidad
{
    public function __construct(
        private readonly string $id,
        private readonly string $identificador,
        private readonly string $tipo,
        private readonly ?string $asignacion,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function identificador(): string
    {
        return $this->identificador;
    }

    public function tipo(): string
    {
        return $this->tipo;
    }

    public function asignacion(): ?string
    {
        return $this->asignacion;
    }
}
