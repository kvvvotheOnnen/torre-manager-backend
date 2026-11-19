<?php

namespace Features\Dashboard\Domain\UseCases;

use Features\Dashboard\Domain\Contracts\AutorizadorCapacidades;
use Features\Dashboard\Domain\Entities\Funcion;
use Features\Dashboard\Domain\Entities\FuncionVisible;
use Features\Dashboard\Domain\Entities\ModuloGeneral;
use Features\Dashboard\Domain\Entities\ModuloSistema;
use Features\Dashboard\Domain\Repositories\CatalogoFuncionesRepositoryInterface;
use Features\Dashboard\Domain\Repositories\DashboardRepositoryInterface;

/**
 * Devuelve las funciones que el usuario puede ver en el proyecto activo.
 *
 * El filtro es por capacidad y por módulo habilitado; nunca por nombre de rol.
 */
final class ListarFuncionesDashboardUseCase
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboard,
        private readonly CatalogoFuncionesRepositoryInterface $catalogo,
        private readonly AutorizadorCapacidades $autorizador,
    ) {}

    /** @return FuncionVisible[] */
    public function execute(string $usuarioId, ?string $proyectoIdSolicitado = null): array
    {
        $proyecto = $this->dashboard->obtenerProyectoDelUsuario($usuarioId, $proyectoIdSolicitado);

        $proyectoId = $proyecto->id();
        $deshabilitados = $proyecto->modulosDeshabilitados();

        $modulosHabilitados = array_map(
            static fn (ModuloSistema $modulo): string => $modulo->value,
            $proyecto->modulos(),
        );

        foreach (ModuloGeneral::cases() as $modulo) {
            if (! in_array($modulo->value, $deshabilitados, true)) {
                $modulosHabilitados[] = $modulo->value;
            }
        }

        $visibles = [];

        foreach ($this->catalogo->listar() as $funcion) {
            if (! $this->esVisible($funcion, $usuarioId, $proyectoId, $modulosHabilitados)) {
                continue;
            }

            $acciones = [];

            foreach ($funcion->acciones() as $nombre => $capacidad) {
                $acciones[(string) $nombre] = $this->autorizador->puede(
                    $usuarioId,
                    $proyectoId,
                    (string) $capacidad,
                );
            }

            $visibles[] = new FuncionVisible($funcion, $acciones);
        }

        return $visibles;
    }

    /**
     * @param  string[]  $modulosHabilitados
     */
    private function esVisible(
        Funcion $funcion,
        string $usuarioId,
        string $proyectoId,
        array $modulosHabilitados,
    ): bool {
        $modulo = $funcion->requiereModulo();

        if ($modulo !== null && ! in_array($modulo, $modulosHabilitados, true)) {
            return false;
        }

        foreach ($funcion->capacidades() as $capacidad) {
            if (! $this->autorizador->puede($usuarioId, $proyectoId, $capacidad)) {
                return false;
            }
        }

        return true;
    }
}
