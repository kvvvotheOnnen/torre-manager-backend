<?php

namespace Features\Superadmin\Domain\Entities;

enum ModuloSistema: string
{
    case Infraestructura = 'infraestructura';

    public function esExclusivoSuperadmin(): bool
    {
        return $this === self::Infraestructura;
    }

    public function nombre(): string
    {
        return match ($this) {
            self::Infraestructura => 'Infraestructura',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Infraestructura => 'Proyecto, agrupaciones, unidades principales y accesorias.',
        };
    }
}
