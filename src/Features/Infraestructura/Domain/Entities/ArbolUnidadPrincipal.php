<?php

namespace Features\Infraestructura\Domain\Entities;

final class ArbolUnidadPrincipal
{
    /**
     * @param UnidadAccesoria[] $unidadesAccesorias
     */
    public function __construct(
        private readonly UnidadPrincipal $unidadPrincipal,
        private readonly array $unidadesAccesorias,
    ) {}

    public function unidadPrincipal(): UnidadPrincipal
    {
        return $this->unidadPrincipal;
    }

    /** @return UnidadAccesoria[] */
    public function unidadesAccesorias(): array
    {
        return $this->unidadesAccesorias;
    }
}
