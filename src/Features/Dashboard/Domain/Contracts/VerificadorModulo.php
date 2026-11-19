<?php

namespace Features\Dashboard\Domain\Contracts;

/**
 * Responde si un módulo está habilitado para el proyecto activo.
 *
 * Es la fuente de verdad para el enforcement a nivel de API: los endpoints
 * no deben asumir que un módulo está activo solo porque el usuario tenga la
 * capacidad. Un módulo deshabilitado por el superadmin no debe responder.
 */
interface VerificadorModulo
{
    public function estaHabilitado(
        string $usuarioId,
        string $proyectoId,
        string $moduloId,
    ): bool;
}
