<?php

namespace Features\Acceso\Domain\Events;

final readonly class VinculoCerrado
{
    public function __construct(
        public string $vinculoId,
        public string $usuarioId,
        public string $unidadPrincipalId,
        public ?string $proyectoId,
        public string $tipoVinculo,
    ) {}
}
