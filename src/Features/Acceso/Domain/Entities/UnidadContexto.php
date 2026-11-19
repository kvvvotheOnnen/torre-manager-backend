<?php

namespace Features\Acceso\Domain\Entities;

/**
 * Contexto de una unidad: a qué proyecto pertenece y quiénes la ocupan hoy.
 *
 * Es la vista de lectura que otros módulos usan para resolver propietario y
 * ocupante sin consultar vínculos por su cuenta.
 */
final class UnidadContexto
{
    public function __construct(
        private readonly string $unidadPrincipalId,
        private readonly string $proyectoId,
        private readonly string $identificador,
        private readonly ?string $agrupacionNombre,
        private readonly ?string $propietarioId,
        private readonly ?string $propietarioNombre,
        private readonly ?string $ocupanteId,
        private readonly ?string $ocupanteNombre,
        private readonly ?string $ocupanteTipo,
    ) {}

    public function unidadPrincipalId(): string
    {
        return $this->unidadPrincipalId;
    }

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function identificador(): string
    {
        return $this->identificador;
    }

    public function agrupacionNombre(): ?string
    {
        return $this->agrupacionNombre;
    }

    public function propietarioId(): ?string
    {
        return $this->propietarioId;
    }

    public function propietarioNombre(): ?string
    {
        return $this->propietarioNombre;
    }

    public function ocupanteId(): ?string
    {
        return $this->ocupanteId;
    }

    public function ocupanteNombre(): ?string
    {
        return $this->ocupanteNombre;
    }

    public function ocupanteTipo(): ?string
    {
        return $this->ocupanteTipo;
    }
}
