<?php

namespace Features\SolicitudesResidente\Domain\Entities;

final class UnidadRegistro
{
    public function __construct(
        private readonly string $id,
        private readonly string $identificador,
        private readonly string $agrupacion,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function identificador(): string
    {
        return $this->identificador;
    }

    public function agrupacion(): string
    {
        return $this->agrupacion;
    }

    public function etiqueta(): string
    {
        return $this->agrupacion !== ''
            ? $this->agrupacion.' - '.$this->identificador
            : $this->identificador;
    }
}
