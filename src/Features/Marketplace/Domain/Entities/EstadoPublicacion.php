<?php

namespace Features\Marketplace\Domain\Entities;

enum EstadoPublicacion: string
{
    case Disponible = 'disponible';
    case Vendida = 'vendida';
    case Retirada = 'retirada';
    case Eliminada = 'eliminada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Vendida => 'Vendida',
            self::Retirada => 'Retirada',
            self::Eliminada => 'Eliminada',
        };
    }

    public function estaDisponible(): bool
    {
        return $this === self::Disponible;
    }

    public function esVisibleEnFavoritos(): bool
    {
        return $this === self::Disponible || $this === self::Vendida;
    }
}
