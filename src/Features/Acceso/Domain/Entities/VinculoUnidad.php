<?php

namespace Features\Acceso\Domain\Entities;

final class VinculoUnidad
{
    public function __construct(
        private readonly string $id,
        private readonly string $unidadPrincipalId,
        private readonly string $usuarioId,
        private readonly TipoVinculo $tipo,
        private readonly string $fechaInicio,
        private readonly ?string $fechaFin,
        private readonly bool $esActual,
        private readonly ?string $usuarioNombre = null,
        private readonly ?string $unidadIdentificador = null,
        private readonly ?string $proyectoId = null,
        private readonly ?string $agrupacionNombre = null,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function unidadPrincipalId(): string
    {
        return $this->unidadPrincipalId;
    }

    public function usuarioId(): string
    {
        return $this->usuarioId;
    }

    public function tipo(): TipoVinculo
    {
        return $this->tipo;
    }

    public function fechaInicio(): string
    {
        return $this->fechaInicio;
    }

    public function fechaFin(): ?string
    {
        return $this->fechaFin;
    }

    public function esActual(): bool
    {
        return $this->esActual;
    }

    public function usuarioNombre(): ?string
    {
        return $this->usuarioNombre;
    }

    public function unidadIdentificador(): ?string
    {
        return $this->unidadIdentificador;
    }

    public function proyectoId(): ?string
    {
        return $this->proyectoId;
    }

    public function agrupacionNombre(): ?string
    {
        return $this->agrupacionNombre;
    }
}
