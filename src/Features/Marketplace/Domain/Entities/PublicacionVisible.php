<?php

namespace Features\Marketplace\Domain\Entities;

/**
 * Publicación con las acciones ya resueltas para el usuario autenticado.
 *
 * El cliente dibuja botones según estos flags y no evalúa roles ni
 * capacidades por su cuenta.
 */
final class PublicacionVisible
{
    public function __construct(
        private readonly PublicacionMarketplace $publicacion,
        private readonly bool $esMia,
        private readonly bool $esFavorita,
        private readonly bool $puedeEditar,
        private readonly bool $puedeEliminar,
        private readonly bool $puedeRetirar,
        private readonly bool $puedeMarcarVendida,
    ) {}

    public function publicacion(): PublicacionMarketplace
    {
        return $this->publicacion;
    }

    public function esMia(): bool
    {
        return $this->esMia;
    }

    public function esFavorita(): bool
    {
        return $this->esFavorita;
    }

    public function puedeEditar(): bool
    {
        return $this->puedeEditar;
    }

    public function puedeEliminar(): bool
    {
        return $this->puedeEliminar;
    }

    public function puedeRetirar(): bool
    {
        return $this->puedeRetirar;
    }

    public function puedeMarcarVendida(): bool
    {
        return $this->puedeMarcarVendida;
    }
}
