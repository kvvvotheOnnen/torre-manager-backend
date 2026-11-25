<?php

namespace Features\Servicios\Domain\Events;

final readonly class TrabajadorPublicado
{
    public function __construct(
        public string $trabajadorId,
        public string $proyectoId,
        public string $nombre,
        public string $tipoServicio,
        public ?string $creadoPorId = null,
    ) {}
}
