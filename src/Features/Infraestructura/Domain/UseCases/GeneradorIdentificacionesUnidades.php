<?php

namespace Features\Infraestructura\Domain\UseCases;

use InvalidArgumentException;

/**
 * Genera las identificaciones de unidades principales a partir de un rango de pisos
 * y una cantidad de unidades por piso. Es un servicio de dominio puro.
 *
 * Ejemplo: prefijo "Torre A -", pisos 1..2, 3 unidades por piso genera:
 * "Torre A-101", "Torre A-102", "Torre A-103", "Torre A-201", ...
 */
final class GeneradorIdentificacionesUnidades
{
    public const MAX_UNIDADES = 500;

    /**
     * @param  int[]  $pisosExcluidos
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    public function generar(?string $prefijo, int $pisoDesde, int $pisoHasta, int $unidadesPorPiso, array $pisosExcluidos = []): array
    {
        if ($pisoDesde < 0 || $pisoHasta < $pisoDesde) {
            throw new InvalidArgumentException('El rango de pisos es inválido.');
        }

        if ($unidadesPorPiso < 1) {
            throw new InvalidArgumentException('Debe existir al menos una unidad por piso.');
        }

        $pisosExcluidos = array_map(static fn ($piso) => (int) $piso, $pisosExcluidos);
        $pisosExcluidos = array_values(array_unique($pisosExcluidos));

        $cantidadPisos = 0;
        for ($piso = $pisoDesde; $piso <= $pisoHasta; $piso++) {
            if (! in_array($piso, $pisosExcluidos, true)) {
                $cantidadPisos++;
            }
        }

        $total = $cantidadPisos * $unidadesPorPiso;

        if ($total > self::MAX_UNIDADES) {
            throw new InvalidArgumentException('La generación excede el máximo de '.self::MAX_UNIDADES.' unidades.');
        }

        $prefijo = trim((string) $prefijo);
        $identificaciones = [];

        for ($piso = $pisoDesde; $piso <= $pisoHasta; $piso++) {
            if (in_array($piso, $pisosExcluidos, true)) {
                continue;
            }

            for ($n = 1; $n <= $unidadesPorPiso; $n++) {
                $identificaciones[] = $this->generarIdentificador($prefijo, $piso, $n);
            }
        }

        return $identificaciones;
    }

    public function generarIdentificador(?string $prefijo, int $piso, int $numero): string
    {
        $prefijo = trim((string) $prefijo);

        return ($prefijo !== '' ? $prefijo.'-' : '').$piso.str_pad((string) $numero, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Genera identificaciones secuenciales para casas (ej. "Casa 1".."Casa 6").
     *
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    public function generarSecuencial(string $prefijo, int $cantidad): array
    {
        if ($cantidad < 1) {
            throw new InvalidArgumentException('Debe existir al menos una unidad.');
        }

        if ($cantidad > self::MAX_UNIDADES) {
            throw new InvalidArgumentException('La generación excede el máximo de '.self::MAX_UNIDADES.' unidades.');
        }

        $prefijo = trim($prefijo);
        $identificaciones = [];

        for ($n = 1; $n <= $cantidad; $n++) {
            $identificaciones[] = ($prefijo !== '' ? $prefijo.' ' : '').$n;
        }

        return $identificaciones;
    }
}
