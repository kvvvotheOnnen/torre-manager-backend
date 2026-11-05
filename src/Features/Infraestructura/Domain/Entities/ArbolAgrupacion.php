<?php

namespace Features\Infraestructura\Domain\Entities;

final class ArbolAgrupacion
{
    /**
     * @param UnidadPrincipal[] $unidadesPrincipales
     */
    public function __construct(
        private readonly Agrupacion $agrupacion,
        private readonly array $unidadesPrincipales,
    ) {}

    public function agrupacion(): Agrupacion
    {
        return $this->agrupacion;
    }

    /** @return UnidadPrincipal[] */
    public function unidadesPrincipales(): array
    {
        return $this->unidadesPrincipales;
    }
}
