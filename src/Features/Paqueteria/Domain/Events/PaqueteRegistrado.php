<?php

namespace Features\Paqueteria\Domain\Events;

final class PaqueteRegistrado
{
    public function __construct(
        public readonly string $paqueteId,
        public readonly string $proyectoId,
        public readonly ?string $unidadPrincipalId,
        public readonly ?string $unidadIdentificador,
        public readonly string $descripcion,
        public readonly ?string $remitente,
        public readonly ?string $residenteId,
    ) {}
}
