<?php

namespace Features\Paqueteria\Domain\Entities;

/**
 * Destino validado de un paquete: proyecto, unidad y residente que lo recibe.
 */
final class DestinoPaquete
{
    public function __construct(
        private readonly string $proyectoId,
        private readonly string $unidadPrincipalId,
        private readonly ?string $residenteId,
        private readonly string $unidadIdentificador,
    ) {}

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function unidadPrincipalId(): string
    {
        return $this->unidadPrincipalId;
    }

    public function residenteId(): ?string
    {
        return $this->residenteId;
    }

    public function unidadIdentificador(): string
    {
        return $this->unidadIdentificador;
    }
}
