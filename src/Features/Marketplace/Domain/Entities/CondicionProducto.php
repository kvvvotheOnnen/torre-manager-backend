<?php

namespace Features\Marketplace\Domain\Entities;

enum CondicionProducto: string
{
    case Nuevo = 'nuevo';
    case SemiNuevo = 'semi_nuevo';
    case Usado = 'usado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Nuevo => 'Nuevo',
            self::SemiNuevo => 'Semi nuevo',
            self::Usado => 'Usado',
        };
    }
}
