<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Superadmin\Domain\Entities\ModuloSistema;
use Features\Superadmin\Domain\Entities\ProyectoDetalle;
use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;
use InvalidArgumentException;

final class ActualizarProyectoUseCase
{
    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
    ) {}

    /**
     * Actualiza los datos editables de un proyecto (tenant): nombre,
     * descripción, módulos habilitados, instalaciones extra y administradores.
     *
     * La estructura del recinto (agrupaciones y unidades) y el tipo de
     * condominio no son editables desde este caso de uso.
     *
     * @param  string[]  $modulos
     * @param  array<int, array{nombre: string, tipo?: ?string, atributos?: ?array<string, mixed>}>  $instalaciones
     * @param  string[]  $administradoresIds
     *
     * @throws InvalidArgumentException
     * @throws RecursoNoEncontradoException
     */
    public function execute(
        string $id,
        string $nombre,
        ?string $descripcion,
        array $modulos,
        array $instalaciones,
        array $administradoresIds,
    ): ProyectoDetalle {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre del proyecto es obligatorio.');
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

        $instalacionesNormalizadas = [];
        foreach ($instalaciones as $instalacion) {
            $nombreInstalacion = trim((string) ($instalacion['nombre'] ?? ''));

            if ($nombreInstalacion === '') {
                throw new InvalidArgumentException('Cada instalación extra debe tener un nombre.');
            }

            $capacidad = (int) ($instalacion['capacidad'] ?? 0);

            if ($capacidad < 1 || $capacidad > 500) {
                throw new InvalidArgumentException("La capacidad de '{$nombreInstalacion}' debe estar entre 1 y 500.");
            }

            $descripcion = $instalacion['descripcion'] ?? null;
            $descripcion = is_string($descripcion) ? trim($descripcion) : null;
            $descripcion = $descripcion !== '' ? $descripcion : null;

            if ($descripcion !== null && mb_strlen($descripcion) > 100) {
                throw new InvalidArgumentException("La descripción de '{$nombreInstalacion}' no puede superar 100 caracteres.");
            }

            $instalacionesNormalizadas[] = [
                'nombre' => $nombreInstalacion,
                'capacidad' => $capacidad,
                'descripcion' => $descripcion,
                'atributos' => $instalacion['atributos'] ?? null,
            ];
        }

        return $this->repository->actualizarProyecto(
            $id,
            $nombre,
            $descripcion !== null ? trim($descripcion) : null,
            $modulosValidos,
            $instalacionesNormalizadas,
            array_values(array_unique(array_map('strval', $administradoresIds))),
        );
    }
}
