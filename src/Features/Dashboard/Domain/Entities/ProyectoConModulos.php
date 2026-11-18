<?php

namespace Features\Dashboard\Domain\Entities;

use Features\Superadmin\Domain\Entities\ModuloSistema;

final class ProyectoConModulos
{
    /**
     * @param  ModuloSistema[]  $modulos
     * @param  string[]  $modulosDeshabilitados
     */
    public function __construct(
        private readonly string $id,
        private readonly array $modulos,
        private readonly array $modulosDeshabilitados = [],
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    /** @return ModuloSistema[] */
    public function modulos(): array
    {
        return $this->modulos;
    }

    /** @return string[] */
    public function modulosDeshabilitados(): array
    {
        return $this->modulosDeshabilitados;
    }
}
