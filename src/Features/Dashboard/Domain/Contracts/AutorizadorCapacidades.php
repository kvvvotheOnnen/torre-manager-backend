<?php

namespace Features\Dashboard\Domain\Contracts;

/**
 * Responde si un usuario posee una capacidad dentro de un proyecto.
 *
 * Es el único punto de verdad para autorizar por permiso: los módulos nunca
 * comparan nombres de rol. Cambiar el mapa de capacidades no exige tocar
 * los casos de uso ni los controladores.
 */
interface AutorizadorCapacidades
{
    public function puede(string $usuarioId, string $proyectoId, string $capacidad): bool;
}
