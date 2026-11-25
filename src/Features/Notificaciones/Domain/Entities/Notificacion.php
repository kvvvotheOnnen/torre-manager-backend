<?php

namespace Features\Notificaciones\Domain\Entities;

use DateTimeImmutable;

final class Notificacion
{
    public function __construct(
        private readonly string $id,
        private readonly string $usuarioId,
        private readonly ?string $proyectoId,
        private readonly string $tipo,
        private readonly string $titulo,
        private readonly string $mensaje,
        private readonly ?array $datos,
        private readonly bool $leida,
        private readonly ?DateTimeImmutable $creadoEn,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function usuarioId(): string
    {
        return $this->usuarioId;
    }

    public function proyectoId(): ?string
    {
        return $this->proyectoId;
    }

    public function tipo(): string
    {
        return $this->tipo;
    }

    public function titulo(): string
    {
        return $this->titulo;
    }

    public function mensaje(): string
    {
        return $this->mensaje;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function datos(): ?array
    {
        return $this->datos;
    }

    public function leida(): bool
    {
        return $this->leida;
    }

    public function creadoEn(): ?DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
