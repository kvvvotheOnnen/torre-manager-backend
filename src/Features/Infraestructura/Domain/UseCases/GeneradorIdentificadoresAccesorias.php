<?php

namespace Features\Infraestructura\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\TipoUnidadAccesoria;
use InvalidArgumentException;

final class GeneradorIdentificadoresAccesorias
{
    public const MAX_POR_LOTE = 999;

    /**
     * @param  string[]  $existentes
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    public function generar(TipoUnidadAccesoria $tipo, int $cantidad, array $existentes = []): array
    {
        if ($cantidad < 1 || $cantidad > self::MAX_POR_LOTE) {
            throw new InvalidArgumentException('La cantidad debe estar entre 1 y '.self::MAX_POR_LOTE.'.');
        }

        $prefijo = $tipo === TipoUnidadAccesoria::Estacionamiento ? 'E' : 'B';

        $maximo = 0;

        foreach ($existentes as $identificador) {
            if (preg_match('/^'.preg_quote($prefijo, '/').'-(\d+)$/', (string) $identificador, $coincidencias) === 1) {
                $maximo = max($maximo, (int) $coincidencias[1]);
            }
        }

        $siguiente = $maximo + 1;
        $resultado = [];
        $usados = array_fill_keys($existentes, true);

        while (count($resultado) < $cantidad) {
            $identificador = sprintf('%s-%03d', $prefijo, $siguiente);
            $siguiente++;

            if (isset($usados[$identificador])) {
                continue;
            }

            $usados[$identificador] = true;
            $resultado[] = $identificador;
        }

        return $resultado;
    }
}
