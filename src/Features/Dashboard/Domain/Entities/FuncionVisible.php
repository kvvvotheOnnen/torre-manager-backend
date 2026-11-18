<?php

namespace Features\Dashboard\Domain\Entities;

/**
 * Función del dashboard con sus acciones ya autorizadas.
 *
 * Mantiene separada la definición (catálogo, capacidades declaradas) de la
 * resolución (qué puede hacer hoy el usuario). Así el cliente nunca recibe
 * nombres de rol ni evalúa permisos por su cuenta.
 */
final class FuncionVisible
{
    /**
     * @param  array<string, bool>  $acciones  Permisos ya resueltos por nombre de acción.
     */
    public function __construct(
        private readonly Funcion $funcion,
        private readonly array $acciones,
    ) {}

    public function funcion(): Funcion
    {
        return $this->funcion;
    }

    /** @return array<string, bool> */
    public function acciones(): array
    {
        return $this->acciones;
    }
}
