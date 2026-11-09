<?php

namespace Features\Residente\Domain\Entities;

final class MiInformacion
{
    public function __construct(
        private readonly string $unidad,
        private readonly string $agrupacion,
    ) {}

    public function unidad(): string
    {
        return $this->unidad;
    }

    public function agrupacion(): string
    {
        return $this->agrupacion;
    }
}
