<?php

namespace Features\Superadmin\Domain\Entities;

final class InstalacionExtra
{
    /**
     * @param array<string, mixed> $atributos
     */
    public function __construct(
        private readonly string $id,
        private readonly string $proyectoId,
        private readonly string $nombre,
        private readonly ?string $tipo,
        private readonly array $atributos,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function tipo(): ?string
    {
        return $this->tipo;
    }

    /** @return array<string, mixed> */
    public function atributos(): array
    {
        return $this->atributos;
    }
}
