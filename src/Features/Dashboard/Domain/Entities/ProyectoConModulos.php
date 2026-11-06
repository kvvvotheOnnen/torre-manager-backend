<?php

namespace Features\Dashboard\Domain\Entities;

use Features\Superadmin\Domain\Entities\ModuloSistema;

final class ProyectoConModulos
{
    /**
     * @param  ModuloSistema[]  $modulos
     */
    public function __construct(
        private readonly string $id,
        private readonly array $modulos,
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
}
