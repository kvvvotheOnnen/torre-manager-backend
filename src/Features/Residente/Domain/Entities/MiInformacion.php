<?php

namespace Features\Residente\Domain\Entities;

final class MiInformacion
{
    /** @param UnidadVinculada[] $unidades */
    public function __construct(
        private readonly array $unidades,
    ) {}

    /** @return UnidadVinculada[] */
    public function unidades(): array
    {
        return $this->unidades;
    }

    public function unidadPrincipal(): ?UnidadVinculada
    {
        foreach ($this->unidades as $unidad) {
            if ($unidad->esActual()) {
                return $unidad;
            }
        }

        return $this->unidades[0] ?? null;
    }

    public function unidad(): string
    {
        return $this->unidadPrincipal()?->unidad() ?? '';
    }

    public function agrupacion(): string
    {
        return $this->unidadPrincipal()?->agrupacion() ?? '';
    }
}
