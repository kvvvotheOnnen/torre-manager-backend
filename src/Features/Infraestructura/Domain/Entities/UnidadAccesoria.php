<?php

namespace Features\Infraestructura\Domain\Entities;

final class UnidadAccesoria
{
    public function __construct(
        private readonly string $id,
        private readonly string $proyectoId,
        private readonly string $identificador,
        private readonly TipoUnidadAccesoria $tipo,
        private readonly ?string $unidadPrincipalId,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function identificador(): string
    {
        return $this->identificador;
    }

    public function tipo(): TipoUnidadAccesoria
    {
        return $this->tipo;
    }

    public function unidadPrincipalId(): ?string
    {
        return $this->unidadPrincipalId;
    }
}
