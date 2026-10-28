<?php

namespace Features\Infraestructura\Domain\Entities;

final class ArbolInfraestructura
{
    /**
     * @param  ArbolAgrupacion[]  $agrupaciones
     * @param  UnidadAccesoria[]  $unidadesAccesorias
     */
    public function __construct(
        private readonly Proyecto $proyecto,
        private readonly array $agrupaciones,
        private readonly array $unidadesAccesorias = [],
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

    /** @return UnidadAccesoria[] */
    public function unidadesAccesorias(): array
    {
        return $this->unidadesAccesorias;
    }
}
