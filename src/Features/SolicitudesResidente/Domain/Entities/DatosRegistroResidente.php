<?php

namespace Features\SolicitudesResidente\Domain\Entities;

final class DatosRegistroResidente
{
    /**
     * @param  DatosPersonaRegistro[]  $personas
     * @param  MascotaRegistro[]  $mascotas
     */
    public function __construct(
        private readonly string $proyectoId,
        private readonly string $unidadPrincipalId,
        private readonly string $password,
        private readonly array $personas,
        private readonly array $mascotas = [],
    ) {}

    public function proyectoId(): string
    {
        return $this->proyectoId;
    }

    public function unidadPrincipalId(): string
    {
        return $this->unidadPrincipalId;
    }

    public function password(): string
    {
        return $this->password;
    }

    /** @return DatosPersonaRegistro[] */
    public function personas(): array
    {
        return $this->personas;
    }

    /** @return MascotaRegistro[] */
    public function mascotas(): array
    {
        return $this->mascotas;
    }

    public function principal(): ?DatosPersonaRegistro
    {
        foreach ($this->personas as $persona) {
            if ($persona->esPrincipal()) {
                return $persona;
            }
        }

        return null;
    }
}
