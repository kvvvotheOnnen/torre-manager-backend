<?php

namespace Features\Marketplace\Domain\Entities;

final class PublicacionMarketplace
{
    public function __construct(
        private readonly string $id,
        private readonly string $proyectoId,
        private readonly ?string $usuarioId,
        private readonly string $titulo,
        private readonly string $descripcion,
        private readonly int $precio,
        private readonly CategoriaProducto $categoria,
        private readonly CondicionProducto $condicion,
        private readonly EstadoPublicacion $estado,
        private readonly ?string $fotoPath,
        private readonly ?string $vendidoEn,
        private readonly ?string $motivoEliminacion,
        private readonly ?string $eliminadoPor,
        private readonly ?string $eliminadoPorRol,
        private readonly ?string $eliminadoEn,
        private readonly ?string $motivoRechazo,
        private readonly ?string $rechazadoEn,
        private readonly ?string $creadoEn,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function usuarioId(): ?string
    {
        return $this->usuarioId;
    }

    public function titulo(): string
    {
        return $this->titulo;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function precio(): int
    {
        return $this->precio;
    }

    public function categoria(): CategoriaProducto
    {
        return $this->categoria;
    }

    public function condicion(): CondicionProducto
    {
        return $this->condicion;
    }

    public function estado(): EstadoPublicacion
    {
        return $this->estado;
    }

    public function fotoPath(): ?string
    {
        return $this->fotoPath;
    }

    public function vendidoEn(): ?string
    {
        return $this->vendidoEn;
    }

    public function motivoEliminacion(): ?string
    {
        return $this->motivoEliminacion;
    }

    public function eliminadoPor(): ?string
    {
        return $this->eliminadoPor;
    }

    public function eliminadoPorRol(): ?string
    {
        return $this->eliminadoPorRol;
    }

    public function eliminadoEn(): ?string
    {
        return $this->eliminadoEn;
    }

    public function motivoRechazo(): ?string
    {
        return $this->motivoRechazo;
    }

    public function rechazadoEn(): ?string
    {
        return $this->rechazadoEn;
    }

    public function creadoEn(): ?string
    {
        return $this->creadoEn;
    }

    public function esPropiaDe(string $usuarioId): bool
    {
        return $this->usuarioId !== null && $this->usuarioId === $usuarioId;
    }
}
