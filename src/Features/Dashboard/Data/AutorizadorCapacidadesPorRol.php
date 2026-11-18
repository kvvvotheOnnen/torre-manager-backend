<?php

namespace Features\Dashboard\Data;

use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Dashboard\Domain\Contracts\AutorizadorCapacidades;

/**
 * Autoriza por capacidad leyendo el mapa rol → capacidades de la configuración.
 *
 * El rol dentro del proyecto se resuelve con el servicio de Acceso, de modo
 * que se respetan membresías, legado y vínculos de unidad sin duplicar reglas.
 */
final class AutorizadorCapacidadesPorRol implements AutorizadorCapacidades
{
    public function __construct(
        private readonly AccesoProyectoService $acceso,
    ) {}

    public function puede(string $usuarioId, string $proyectoId, string $capacidad): bool
    {
        $rol = $this->acceso->rolEn($usuarioId, $proyectoId);

        if ($rol === null) {
            return false;
        }

        $concedidas = $this->capacidadesDe($rol->value);

        return in_array('*', $concedidas, true)
            || in_array($capacidad, $concedidas, true);
    }

    /** @return string[] */
    private function capacidadesDe(string $rol): array
    {
        /** @var array<string, array<int, string>> $mapa */
        $mapa = config('capacidades.roles', []);

        return array_values($mapa[$rol] ?? []);
    }
}
