<?php

namespace Features\Superadmin\Domain\Entities;

/**
 * Proyecto al que un administrador está anexado (referencia mínima).
 */
final class ProyectoAdministrador
{
    public function __construct(
        private readonly string $id,
        private readonly string $nombre,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }
}
