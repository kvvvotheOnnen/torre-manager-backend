<?php

namespace Features\Infraestructura\Domain\Entities;

final class ArbolInfraestructura
{
    /**
     * @param ArbolAgrupacion[] $agrupaciones
     */
    public function __construct(
        private readonly Proyecto $proyecto,
        private readonly array $agrupaciones,
    ) {}

    public function proyecto(): Proyecto
    {
        return $this->proyecto;
    }

    /** @return ArbolAgrupacion[] */
    public function agrupaciones(): array
    {
        return $this->agrupaciones;
    }
}
