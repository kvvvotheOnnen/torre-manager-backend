<?php

namespace Features\Asistente\Domain\Entities;

final class FuenteRespuesta
{
    public function __construct(
        private readonly string $documento,
        private readonly int $pagina,
    ) {}

    public function documento(): string
    {
        return $this->documento;
    }

    public function pagina(): int
    {
        return $this->pagina;
    }
}
