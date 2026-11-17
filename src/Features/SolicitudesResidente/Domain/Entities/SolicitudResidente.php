<?php

namespace Features\SolicitudesResidente\Domain\Entities;

final class SolicitudResidente
{
    /**
     * @param  PersonaSolicitud[]  $personas
     * @param  InstalacionUnidad[]  $instalaciones
     */
    public function __construct(
        private readonly string $id,
        private readonly string $proyectoId,
        private readonly string $unidadPrincipalId,
        private readonly string $unidad,
        private readonly string $agrupacion,
        private readonly TipoSolicitud $tipoSolicitud,
        private readonly OrigenSolicitud $origen,
        private readonly EstadoSolicitud $estado,
        private readonly ?string $motivoRechazo,
        private readonly array $personas,
        private readonly array $instalaciones,
        private readonly ?string $solicitadoPorId,
        private readonly ?string $solicitadoPorNombre,
        private readonly ?string $decididoPorId,
        private readonly ?string $decididoPorNombre,
        private readonly ?string $decididoAt,
        private readonly string $createdAt,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function unidadPrincipalId(): string
    {
        return $this->unidadPrincipalId;
    }

    public function unidad(): string
    {
        return $this->unidad;
    }

    public function agrupacion(): string
    {
        return $this->agrupacion;
    }

    public function tipoSolicitud(): TipoSolicitud
    {
        return $this->tipoSolicitud;
    }

    public function origen(): OrigenSolicitud
    {
        return $this->origen;
    }

    public function estado(): EstadoSolicitud
    {
        return $this->estado;
    }

    public function motivoRechazo(): ?string
    {
        return $this->motivoRechazo;
    }

    /** @return PersonaSolicitud[] */
    public function personas(): array
    {
        return $this->personas;
    }

    /** @return InstalacionUnidad[] */
    public function instalaciones(): array
    {
        return $this->instalaciones;
    }

    public function solicitadoPorId(): ?string
    {
        return $this->solicitadoPorId;
    }

    public function solicitadoPorNombre(): ?string
    {
        return $this->solicitadoPorNombre;
    }

    public function decididoPorId(): ?string
    {
        return $this->decididoPorId;
    }

    public function decididoPorNombre(): ?string
    {
        return $this->decididoPorNombre;
    }

    public function decididoAt(): ?string
    {
        return $this->decididoAt;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }
}
