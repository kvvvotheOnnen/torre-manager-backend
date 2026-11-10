<?php

namespace Features\Paqueteria\Domain\Entities;

final class Paquete
{
    public function __construct(
        private readonly string $id,
        private readonly string $proyectoId,
        private readonly ?string $unidadPrincipalId,
        private readonly ?string $residenteId,
        private readonly ?string $unidadIdentificador,
        private readonly string $descripcion,
        private readonly ?string $remitente,
        private readonly ?string $fotoPath,
        private readonly string $fechaRecepcion,
        private readonly string $estado,
        private readonly ?string $fechaRetiro,
        private readonly ?string $retiradoPorNombre,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function unidadPrincipalId(): ?string
    {
        return $this->unidadPrincipalId;
    }

    public function residenteId(): ?string
    {
        return $this->residenteId;
    }

    public function unidadIdentificador(): ?string
    {
        return $this->unidadIdentificador;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function remitente(): ?string
    {
        return $this->remitente;
    }

    public function fotoPath(): ?string
    {
        return $this->fotoPath;
    }

    public function fechaRecepcion(): string
    {
        return $this->fechaRecepcion;
    }

    public function estado(): string
    {
        return $this->estado;
    }

    public function fechaRetiro(): ?string
    {
        return $this->fechaRetiro;
    }

    public function retiradoPorNombre(): ?string
    {
        return $this->retiradoPorNombre;
    }

    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }
}
