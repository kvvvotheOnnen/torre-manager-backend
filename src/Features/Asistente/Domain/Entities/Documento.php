<?php

namespace Features\Asistente\Domain\Entities;

final class Documento
{
    public function __construct(
        private readonly string $id,
        private readonly string $proyectoId,
        private readonly string $nombreOriginal,
        private readonly int $paginas,
        private readonly int $tamanoTokens,
        private readonly string $ruta,
        private readonly string $texto,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function nombreOriginal(): string
    {
        return $this->nombreOriginal;
    }

    public function paginas(): int
    {
        return $this->paginas;
    }

    public function tamanoTokens(): int
    {
        return $this->tamanoTokens;
    }

    public function ruta(): string
    {
        return $this->ruta;
    }

    public function texto(): string
    {
        return $this->texto;
    }
}
