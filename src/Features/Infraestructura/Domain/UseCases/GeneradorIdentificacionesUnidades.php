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
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    public function generar(?string $prefijo, int $pisoDesde, int $pisoHasta, int $unidadesPorPiso): array
    {
        if ($pisoDesde < 0 || $pisoHasta < $pisoDesde) {
            throw new InvalidArgumentException('El rango de pisos es inválido.');
        }

        if ($unidadesPorPiso < 1) {
            throw new InvalidArgumentException('Debe existir al menos una unidad por piso.');
        }

        $total = ($pisoHasta - $pisoDesde + 1) * $unidadesPorPiso;

        if ($total > self::MAX_UNIDADES) {
            throw new InvalidArgumentException('La generación excede el máximo de '.self::MAX_UNIDADES.' unidades.');
        }

        $prefijo = trim((string) $prefijo);
        $identificaciones = [];

        for ($piso = $pisoDesde; $piso <= $pisoHasta; $piso++) {
            for ($n = 1; $n <= $unidadesPorPiso; $n++) {
                $identificaciones[] = ($prefijo !== '' ? $prefijo.'-' : '').$piso.str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            }
        }

        return $identificaciones;
    }
}
