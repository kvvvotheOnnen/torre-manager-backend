<?php

namespace Features\SolicitudesResidente\Domain\Events;

final readonly class SolicitudRechazada
{
    public function __construct(
        public string $solicitudId,
        public string $proyectoId,
        public string $usuarioId,
        public string $motivo,
    ) {}
}
