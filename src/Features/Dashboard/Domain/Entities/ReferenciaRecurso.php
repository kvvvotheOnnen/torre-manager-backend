<?php

namespace Features\Dashboard\Domain\Entities;

/**
 * Referencia opaca a un recurso concreto (una unidad, una agrupación, etc.).
 *
 * Permite preguntar si un usuario alcanza un recurso sin que el llamador
 * conozca la forma actual del modelo de datos.
 */
final class ReferenciaRecurso
{
    public function __construct(
        private readonly string $tipo,
        private readonly string $id,
    ) {}

    public function tipo(): string
    {
        return $this->tipo;
    }

    public function id(): string
    {
        return $this->id;
    }
}
