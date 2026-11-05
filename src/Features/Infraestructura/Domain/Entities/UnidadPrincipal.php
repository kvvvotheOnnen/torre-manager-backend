<?php

namespace Features\Infraestructura\Domain\Entities;

final class UnidadPrincipal
{
    public function __construct(
        private readonly string $id,
        private readonly string $agrupacionId,
        private readonly string $identificador,
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
}
