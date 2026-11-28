<?php

namespace Features\Paqueteria\Domain\Events;

final class PaqueteRetirado
{
    public function __construct(
        public readonly string $paqueteId,
        public readonly string $proyectoId,
        public readonly ?string $unidadPrincipalId,
        public readonly ?string $unidadIdentificador,
        public readonly string $descripcion,
        public readonly string $retiradoPorNombre,
        public readonly ?string $residenteId,
    ) {}
}
