<?php

namespace Features\Superadmin\Domain\Services;

use Features\Infraestructura\Domain\Entities\TipoAgrupacion;
use Features\Infraestructura\Domain\UseCases\GeneradorIdentificacionesUnidades;
use Features\Superadmin\Domain\Entities\TipoCondominio;
use InvalidArgumentException;

/**
 * Valida y normaliza las agrupaciones (torres, manzanas, edificios) de un
 * proyecto, generando en cascada los identificadores de sus unidades.
 */
final class NormalizadorEstructuraProyecto
{
    public function __construct(
        private readonly GeneradorIdentificacionesUnidades $generador,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $agrupaciones
     * @return array<int, array{nombre: string, tipo: TipoAgrupacion, identificadores: string[]}>
     *
     * @throws InvalidArgumentException
     */
    public function normalizar(TipoCondominio $tipoCondominio, array $agrupaciones): array
    {
        $resultado = [];

        foreach ($agrupaciones as $agrupacion) {
            $nombre = trim((string) ($agrupacion['nombre'] ?? ''));

            if ($nombre === '') {
                throw new InvalidArgumentException('Cada agrupación debe tener un nombre.');
            }

            $tipoAgrupacion = TipoAgrupacion::tryFrom((string) ($agrupacion['tipo'] ?? ''));

            if ($tipoAgrupacion === null) {
                throw new InvalidArgumentException("El tipo de agrupación '{$nombre}' no es válido.");
            }

            $this->validarTipoDeAgrupacion($tipoCondominio, $tipoAgrupacion, $nombre);

            $prefijo = isset($agrupacion['prefijo']) ? (string) $agrupacion['prefijo'] : null;

            if ($tipoAgrupacion === TipoAgrupacion::Manzana) {
                $cantidad = (int) ($agrupacion['cantidad'] ?? 0);

                if ($cantidad < 1) {
                    throw new InvalidArgumentException("Indica la cantidad de casas de '{$nombre}'.");
                }

                $identificadores = $this->generador->generarSecuencial($prefijo ?: 'Casa', $cantidad);
            } else {
                $identificadores = $this->generador->generar(
                    $prefijo ?: $nombre,
                    (int) ($agrupacion['piso_desde'] ?? 0),
                    (int) ($agrupacion['piso_hasta'] ?? 0),
                    (int) ($agrupacion['unidades_por_piso'] ?? 0),
                );
            }

            $resultado[] = [
                'nombre' => $nombre,
                'tipo' => $tipoAgrupacion,
                'identificadores' => $identificadores,
            ];
        }

        return $resultado;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validarTipoDeAgrupacion(
        TipoCondominio $tipoCondominio,
        TipoAgrupacion $tipoAgrupacion,
        string $nombre,
    ): void {
        $permitidos = match ($tipoCondominio) {
            TipoCondominio::Torres => [TipoAgrupacion::Torre, TipoAgrupacion::Bloque],
            TipoCondominio::Casas => [TipoAgrupacion::Manzana],
            TipoCondominio::Edificio => [TipoAgrupacion::Edificio],
            TipoCondominio::Mixto => [
                TipoAgrupacion::Torre,
                TipoAgrupacion::Bloque,
                TipoAgrupacion::Manzana,
            ],
        };

        if (! in_array($tipoAgrupacion, $permitidos, true)) {
            throw new InvalidArgumentException(
                "El tipo de agrupación '{$tipoAgrupacion->value}' no es válido para un condominio de tipo '{$tipoCondominio->value}' (agrupación: {$nombre}).",
            );
        }
    }
}
