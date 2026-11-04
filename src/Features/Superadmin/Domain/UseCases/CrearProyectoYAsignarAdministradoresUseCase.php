<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Infraestructura\Domain\Entities\TipoUnidadAccesoria;
use Features\Infraestructura\Domain\UseCases\GeneradorIdentificadoresAccesorias;
use Features\Superadmin\Domain\Entities\ModuloSistema;
use Features\Superadmin\Domain\Entities\ProyectoDetalle;
use Features\Superadmin\Domain\Entities\TipoCondominio;
use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;
use Features\Superadmin\Domain\Services\NormalizadorEstructuraProyecto;
use Features\Superadmin\Domain\Services\NormalizadorInstalacionesProyecto;
use InvalidArgumentException;

final class CrearProyectoYAsignarAdministradoresUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
        private readonly NormalizadorEstructuraProyecto $normalizadorEstructura,
        private readonly NormalizadorInstalacionesProyecto $normalizadorInstalaciones,
        private readonly GeneradorIdentificadoresAccesorias $generadorAccesorias,
    ) {}

    /**
     * Crea el proyecto (tenant), su estructura de infraestructura según el tipo
     * de condominio, sus instalaciones y la asociación con administradores.
     *
     * Estructura esperada por agrupación:
     * - torre/bloque/edificio: piso_desde, piso_hasta, unidades_por_piso (y prefijo opcional)
     * - manzana (casas): cantidad (y prefijo opcional, por defecto "Casa")
     *
     * @param  string[]  $modulos
     * @param  array<int, array{nombre: string, tipo: string, prefijo?: ?string, piso_desde?: ?int, piso_hasta?: ?int, unidades_por_piso?: ?int, cantidad?: ?int}>  $agrupaciones
     * @param  array<int, array{nombre: string, capacidad: int, descripcion?: ?string, atributos?: ?array<string, mixed>}>  $instalaciones
     * @param  string[]  $administradoresIds
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
        int $estacionamientos = 0,
        int $bodegas = 0,
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

        $agrupacionesNormalizadas = $this->normalizadorEstructura->normalizar($tipo, $agrupaciones);

        if ($agrupacionesNormalizadas === []) {
            throw new InvalidArgumentException('Debes definir al menos una agrupación para el proyecto.');
        }

        $instalacionesNormalizadas = $this->normalizadorInstalaciones->normalizar($instalaciones);

        if ($estacionamientos < 0 || $estacionamientos > GeneradorIdentificadoresAccesorias::MAX_POR_LOTE) {
            throw new InvalidArgumentException('La cantidad de estacionamientos debe estar entre 0 y '.GeneradorIdentificadoresAccesorias::MAX_POR_LOTE.'.');
        }

        if ($bodegas < 0 || $bodegas > GeneradorIdentificadoresAccesorias::MAX_POR_LOTE) {
            throw new InvalidArgumentException('La cantidad de bodegas debe estar entre 0 y '.GeneradorIdentificadoresAccesorias::MAX_POR_LOTE.'.');
        }

        $accesorias = [];

        if ($estacionamientos > 0) {
            $accesorias[] = [
                'tipo' => TipoUnidadAccesoria::Estacionamiento,
                'identificadores' => $this->generadorAccesorias->generar(
                    TipoUnidadAccesoria::Estacionamiento,
                    $estacionamientos,
                ),
            ];
        }

        if ($bodegas > 0) {
            $accesorias[] = [
                'tipo' => TipoUnidadAccesoria::Bodega,
                'identificadores' => $this->generadorAccesorias->generar(
                    TipoUnidadAccesoria::Bodega,
                    $bodegas,
                ),
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
            $accesorias,
        );
    }
}
