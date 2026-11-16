<?php

namespace Features\Infraestructura\Domain\Entities;

final class UnidadPrincipal
{
    public function __construct(
        private readonly string $id,
        private readonly string $agrupacionId,
        private readonly string $identificador,
        private readonly ?string $propietarioId = null,
        private readonly ?string $propietarioNombre = null,
        private readonly ?string $ocupanteId = null,
        private readonly ?string $ocupanteNombre = null,
        private readonly ?string $ocupanteTipo = null,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function agrupacionId(): string
    {
        return $this->agrupacionId;
    }

    public function identificador(): string
    {
        return $this->identificador;
    }

    public function propietarioId(): ?string
    {
        return $this->propietarioId;
    }

    public function propietarioNombre(): ?string
    {
        return $this->propietarioNombre;
    }

    public function ocupanteId(): ?string
    {
        return $this->ocupanteId;
    }

    public function ocupanteNombre(): ?string
    {
        return $this->ocupanteNombre;
    }

    public function ocupanteTipo(): ?string
    {
        return $this->ocupanteTipo;
    }
}
