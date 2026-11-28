<?php

namespace Features\Marketplace\Domain\Entities;

enum EstadoPublicacion: string
{
    case Pendiente = 'pendiente';
    case Disponible = 'disponible';
    case Vendida = 'vendida';
    case Retirada = 'retirada';
    case Rechazada = 'rechazada';
    case Eliminada = 'eliminada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente de aprobación',
            self::Disponible => 'Disponible',
            self::Vendida => 'Vendida',
            self::Retirada => 'Retirada',
            self::Rechazada => 'Rechazada',
            self::Eliminada => 'Eliminada',
        };
    }

    public function estaDisponible(): bool
    {
        return $this === self::Disponible;
    }

    public function esPendiente(): bool
    {
        return $this === self::Pendiente;
    }

    public function esVisibleEnFavoritos(): bool
    {
        return $this === self::Disponible || $this === self::Vendida;
    }
}
