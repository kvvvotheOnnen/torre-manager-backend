<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\TipoAgrupacion;
use Features\Infraestructura\Domain\UseCases\GeneradorIdentificacionesUnidades;
use Features\Superadmin\Domain\Entities\ModuloSistema;
use Features\Superadmin\Domain\Entities\ProyectoDetalle;
use Features\Superadmin\Domain\Entities\TipoCondominio;
use Features\Superadmin\Domain\Exceptions\AdministradorInvalidoException;
use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;
use InvalidArgumentException;

final class CrearProyectoYAsignarAdministradoresUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
        private readonly GeneradorIdentificacionesUnidades $generador,
    ) {}

    /**
     * Crea el proyecto (tenant), su estructura de infraestructura según el tipo
     * de condominio, sus instalaciones y la asociación con administradores.
     *
     * Estructura esperada por agrupación:
     * - torre/bloque/edificio: piso_desde, piso_hasta, unidades_por_piso (y prefijo opcional)
     * - manzana (casas): cantidad (y prefijo opcional, por defecto "Casa")
     *
     * @param string[] $modulos
     * @param array<int, array{nombre: string, tipo: string, prefijo?: ?string, piso_desde?: ?int, piso_hasta?: ?int, unidades_por_piso?: ?int, cantidad?: ?int}> $agrupaciones
     * @param array<int, array{nombre: string, tipo?: ?string, atributos?: ?array<string, mixed>}> $instalaciones
     * @param string[] $administradoresIds
     *
     * @throws InvalidArgumentException
     * @throws RecursoNoEncontradoException
     */
    public function execute(
        string $nombre,
        ?string $descripcion,
        string $tipoCondominio,
        array $modulos,
        array $agrupaciones,
        array $instalaciones,
        array $administradoresIds,
    ): ProyectoDetalle {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre del proyecto es obligatorio.');
        }

        $tipo = TipoCondominio::tryFrom($tipoCondominio);

        if ($tipo === null) {
            throw new InvalidArgumentException('El tipo de condominio no es válido.');
        }

        $modulosValidos = [];
        foreach ($modulos as $modulo) {
            $enum = ModuloSistema::tryFrom((string) $modulo);

            if ($enum === null) {
                throw new InvalidArgumentException("El módulo '{$modulo}' no es válido.");
            }

            $modulosValidos[] = $enum;
        }

        if ($modulosValidos === []) {
            throw new InvalidArgumentException('Debes habilitar al menos un módulo.');
        }

        if ($administradoresIds === []) {
            throw new InvalidArgumentException('Debes asignar al menos un administrador.');
        }

        $agrupacionesNormalizadas = $this->normalizarAgrupaciones($tipo, $agrupaciones);

        if ($agrupacionesNormalizadas === []) {
            throw new InvalidArgumentException('Debes definir al menos una agrupación para el proyecto.');
        }

        $instalacionesNormalizadas = [];
        foreach ($instalaciones as $instalacion) {
            $nombreInstalacion = trim((string) ($instalacion['nombre'] ?? ''));

            if ($nombreInstalacion === '') {
                throw new InvalidArgumentException('Cada instalación extra debe tener un nombre.');
            }

            $instalacionesNormalizadas[] = [
                'nombre' => $nombreInstalacion,
                'tipo' => $instalacion['tipo'] ?? null,
                'atributos' => $instalacion['atributos'] ?? null,
            ];
        }

        return $this->repository->crearProyectoConAdministradores(
            $nombre,
            $descripcion !== null ? trim($descripcion) : null,
            $tipo,
            $modulosValidos,
            $agrupacionesNormalizadas,
            $instalacionesNormalizadas,
            array_values(array_unique($administradoresIds)),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $agrupaciones
     *
     * @return array<int, array{nombre: string, tipo: TipoAgrupacion, identificadores: string[]}>
     */
    private function normalizarAgrupaciones(TipoCondominio $tipoCondominio, array $agrupaciones): array
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
