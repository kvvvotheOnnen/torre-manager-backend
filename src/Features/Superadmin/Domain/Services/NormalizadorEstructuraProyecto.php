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

            $prefijo = isset($agrupacion['prefijo']) ? trim((string) $agrupacion['prefijo']) : null;
            $prefijo = $prefijo === '' ? null : $prefijo;

            if ($tipoAgrupacion === TipoAgrupacion::Manzana) {
                $this->validarSinPisosExcepcion($agrupacion, $nombre);

                $cantidad = (int) ($agrupacion['cantidad'] ?? 0);

                if ($cantidad < 1) {
                    throw new InvalidArgumentException("Indica la cantidad de casas de '{$nombre}'.");
                }

                $identificadores = $this->generador->generarSecuencial($prefijo ?: 'Casa', $cantidad);
            } else {
                // Un edificio es uno solo: sus unidades no se llaman "Edificio-103"
                // sino "Unidad-103". Torres y bloques conservan su nombre.
                $base = $prefijo
                    ?? ($tipoAgrupacion === TipoAgrupacion::Edificio ? 'Unidad' : $nombre);

                $pisosExcepcion = $this->normalizarPisosExcepcion($agrupacion, $nombre, $base);

                $identificadores = $this->generador->generar(
                    $base,
                    (int) ($agrupacion['piso_desde'] ?? 0),
                    (int) ($agrupacion['piso_hasta'] ?? 0),
                    (int) ($agrupacion['unidades_por_piso'] ?? 0),
                    array_keys($pisosExcepcion),
                );

                foreach ($pisosExcepcion as $unidades) {
                    foreach ($unidades as $unidad) {
                        if ($unidad['tipo'] === 'simple') {
                            $identificadores[] = $unidad['identificador'];
                        }
                    }
                }

                $this->validarIdentificadoresUnicos($identificadores, $nombre);
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
     * @param  array<string, mixed>  $agrupacion
     *
     * @throws InvalidArgumentException
     */
    private function validarSinPisosExcepcion(array $agrupacion, string $nombre): void
    {
        $pisosExcepcion = $agrupacion['pisos_excepcion'] ?? [];

        if (is_array($pisosExcepcion) && $pisosExcepcion !== []) {
            throw new InvalidArgumentException("Las manzanas no admiten pisos con excepción (agrupación: {$nombre}).");
        }
    }

    /**
     * @param  array<string, mixed>  $agrupacion
     * @return array<int, array<int, array{identificador: string, tipo: string}>>
     *
     * @throws InvalidArgumentException
     */
    private function normalizarPisosExcepcion(array $agrupacion, string $nombre, string $base): array
    {
        $pisosExcepcion = $agrupacion['pisos_excepcion'] ?? [];

        if (! is_array($pisosExcepcion) || $pisosExcepcion === []) {
            return [];
        }

        $pisoDesde = (int) ($agrupacion['piso_desde'] ?? 0);
        $pisoHasta = (int) ($agrupacion['piso_hasta'] ?? 0);
        $resultado = [];

        foreach ($pisosExcepcion as $item) {
            $piso = (int) ($item['piso'] ?? -1);

            if ($piso < $pisoDesde || $piso > $pisoHasta) {
                throw new InvalidArgumentException(
                    "El piso con excepción {$piso} de '{$nombre}' está fuera del rango {$pisoDesde}-{$pisoHasta}.",
                );
            }

            if (isset($resultado[$piso])) {
                throw new InvalidArgumentException(
                    "El piso {$piso} está repetido en las excepciones de '{$nombre}'.",
                );
            }

            $sinUnidades = filter_var($item['sin_unidades'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($sinUnidades) {
                $resultado[$piso] = [];

                continue;
            }

            $unidades = $item['unidades'] ?? null;

            if (! is_array($unidades) || $unidades === []) {
                // Un piso con excepción puede quedar vacío: sin unidades ni
                // instalaciones. Se excluye igual de la generación automática.
                $resultado[$piso] = [];

                continue;
            }

            $normalizadas = [];
            $secuenciaSimple = 0;

            foreach ($unidades as $unidad) {
                $tipo = (string) ($unidad['tipo'] ?? '');

                if (! in_array($tipo, ['simple', 'instalacion'], true)) {
                    throw new InvalidArgumentException(
                        "El tipo de una unidad del piso {$piso} de '{$nombre}' no es válido.",
                    );
                }

                if ($tipo === 'simple') {
                    $secuenciaSimple++;

                    $normalizadas[] = [
                        'identificador' => $this->generador->generarIdentificador(
                            $base,
                            $piso,
                            $secuenciaSimple,
                        ),
                        'tipo' => 'simple',
                    ];

                    continue;
                }

                $normalizadas[] = [
                    'identificador' => trim((string) ($unidad['identificador'] ?? '')),
                    'tipo' => 'instalacion',
                ];
            }

            $resultado[$piso] = $normalizadas;
        }

        return $resultado;
    }

    /**
     * @param  string[]  $identificadores
     *
     * @throws InvalidArgumentException
     */
    private function validarIdentificadoresUnicos(array $identificadores, string $nombre): void
    {
        if (count($identificadores) !== count(array_unique($identificadores))) {
            throw new InvalidArgumentException(
                "La agrupación '{$nombre}' tiene identificadores de unidad duplicados.",
            );
        }
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
