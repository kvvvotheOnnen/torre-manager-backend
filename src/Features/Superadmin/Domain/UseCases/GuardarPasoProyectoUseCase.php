<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Superadmin\Domain\Entities\ModuloSistema;
use Features\Superadmin\Domain\Entities\ProyectoDetalle;
use Features\Superadmin\Domain\Entities\TipoCondominio;
use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;
use Features\Superadmin\Domain\Services\NormalizadorEstructuraProyecto;
use Features\Superadmin\Domain\Services\NormalizadorInstalacionesProyecto;
use InvalidArgumentException;

/**
 * Guarda un paso del asistente de creación de proyecto sobre un borrador.
 * Solo aplica los campos recibidos; permite corregir pasos anteriores.
 */
final class GuardarPasoProyectoUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
        private readonly NormalizadorEstructuraProyecto $normalizadorEstructura,
        private readonly NormalizadorInstalacionesProyecto $normalizadorInstalaciones,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>|null  $agrupaciones
     * @param  array<int, array<string, mixed>>|null  $instalaciones
     * @param  string[]|null  $modulos
     * @param  string[]|null  $administradoresIds
     *
     * @throws InvalidArgumentException
     * @throws RecursoNoEncontradoException
     */
    public function execute(
        string $id,
        ?string $nombre = null,
        ?string $descripcion = null,
        ?string $tipoCondominio = null,
        ?array $agrupaciones = null,
        ?array $instalaciones = null,
        ?array $modulos = null,
        ?array $administradoresIds = null,
        bool $resetEstructura = false,
        bool $finalizar = false,
    ): ProyectoDetalle {
        $actual = $this->repository->buscarProyecto($id);

        if (! $actual->esBorrador()) {
            throw new InvalidArgumentException('El proyecto ya fue finalizado y no admite guardado por pasos.');
        }

        if ($nombre !== null) {
            $nombre = trim($nombre);

            if ($nombre === '') {
                throw new InvalidArgumentException('El nombre del proyecto es obligatorio.');
            }
        }

        if ($descripcion !== null) {
            $descripcion = trim($descripcion);
        }

        $tipo = null;
        if ($tipoCondominio !== null) {
            $tipo = TipoCondominio::tryFrom($tipoCondominio);

            if ($tipo === null) {
                throw new InvalidArgumentException('El tipo de condominio no es válido.');
            }

            if ($tipo !== $actual->tipoCondominio()) {
                $resetEstructura = true;
            }
        }

        $agrupacionesNormalizadas = null;
        if ($agrupaciones !== null) {
            $tipoParaEstructura = $tipo ?? $actual->tipoCondominio();
            $agrupacionesNormalizadas = $this->normalizadorEstructura->normalizar(
                $tipoParaEstructura,
                $agrupaciones,
            );

            if ($agrupacionesNormalizadas === []) {
                throw new InvalidArgumentException('Debes definir al menos una agrupación para el proyecto.');
            }
        }

        $instalacionesNormalizadas = null;
        if ($instalaciones !== null) {
            $instalacionesNormalizadas = $this->normalizadorInstalaciones->normalizar($instalaciones);
        }

        $modulosValidos = null;
        if ($modulos !== null) {
            $modulosValidos = [];
            foreach ($modulos as $modulo) {
                $enum = ModuloSistema::tryFrom((string) $modulo);

                if ($enum === null) {
                    throw new InvalidArgumentException("El módulo '{$modulo}' no es válido.");
                }

                $modulosValidos[] = $enum;
            }
        }

        $this->repository->guardarPasoProyecto(
            $id,
            $nombre,
            $descripcion,
            $tipo,
            $agrupacionesNormalizadas,
            $instalacionesNormalizadas,
            $modulosValidos,
            $administradoresIds,
            $resetEstructura,
        );

        if (! $finalizar) {
            return $this->repository->buscarProyecto($id);
        }

        $conteos = $this->repository->contarDatosProyecto($id);

        if ($conteos['unidades_principales'] < 1) {
            throw new InvalidArgumentException('Debes definir la estructura del recinto antes de finalizar.');
        }

        if ($conteos['administradores'] < 1) {
            throw new InvalidArgumentException('Debes asignar al menos un administrador antes de finalizar.');
        }

        if ($conteos['modulos'] < 1) {
            throw new InvalidArgumentException('Debes habilitar al menos un módulo antes de finalizar.');
        }

        return $this->repository->marcarProyectoCompleto($id);
    }
}
