<?php

namespace Features\Marketplace\Domain\Events;

/**
 * Se dispara cuando una solicitud de publicación es rechazada con un motivo.
 */
final readonly class PublicacionRechazadaMarketplace
{
    public function __construct(
        public string $publicacionId,
        public string $proyectoId,
        public ?string $propietarioId,
        public string $titulo,
        public string $motivo,
        public string $revisorId,
    ) {}
}
