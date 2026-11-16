<?php

namespace Features\Residente\Domain\Entities;

final class UnidadVinculada
{
    public function __construct(
        private readonly string $unidad,
        private readonly string $agrupacion,
        private readonly string $proyectoId,
        private readonly string $tipoVinculo,
        private readonly bool $esActual,
        private readonly ?string $fechaInicio = null,
        private readonly ?string $fechaFin = null,
    ) {}

    public function unidad(): string
    {
        return $this->unidad;
    }

    public function agrupacion(): string
    {
        return $this->agrupacion;
    }

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function tipoVinculo(): string
    {
        return $this->tipoVinculo;
    }

    public function esActual(): bool
    {
        return $this->esActual;
    }

    public function fechaInicio(): ?string
    {
        return $this->fechaInicio;
    }

    public function fechaFin(): ?string
    {
        return $this->fechaFin;
    }
}
