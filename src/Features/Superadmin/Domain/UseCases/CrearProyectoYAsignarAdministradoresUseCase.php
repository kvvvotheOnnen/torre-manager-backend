<?php

namespace Features\Superadmin\Domain\UseCases;

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
    ) {}

    /**
     * Crea el proyecto (tenant) con sus módulos e instalaciones, y lo asocia
     * a los administradores indicados. La asociación se registra desde el
     * proyecto (pivote administrador_proyecto).
     *
     * @param string[] $modulos
     * @param array<int, array{nombre: string, tipo?: ?string, atributos?: ?array<string, mixed>}> $instalaciones
     * @param string[] $administradoresIds
     *
     * @throws InvalidArgumentException
     * @throws AdministradorInvalidoException
     * @throws RecursoNoEncontradoException
     */
    public function execute(
        string $nombre,
        ?string $descripcion,
        string $tipoCondominio,
        array $modulos,
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
            $instalacionesNormalizadas,
            array_values(array_unique($administradoresIds)),
        );
    }
}
