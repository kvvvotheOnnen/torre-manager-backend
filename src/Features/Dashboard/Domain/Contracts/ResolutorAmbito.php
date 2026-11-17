<?php

namespace Features\Dashboard\Domain\Contracts;

use Features\Dashboard\Domain\Entities\ReferenciaRecurso;

/**
 * Responde si un usuario alcanza un recurso concreto dentro del proyecto activo.
 *
 * Aísla a los módulos de la forma actual del modelo de datos: si mañana las
 * unidades se agrupan en departamentos, solo cambia la implementación.
 */
interface ResolutorAmbito
{
    public function puedeAccederA(
        string $usuarioId,
        string $proyectoId,
        ReferenciaRecurso $recurso,
    ): bool;
}
