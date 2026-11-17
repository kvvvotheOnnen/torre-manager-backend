<?php

namespace Features\Servicios\Domain\Repositories;

use Features\Servicios\Domain\Entities\TipoServicio;
use Features\Servicios\Domain\Entities\TrabajadorServicio;

interface ServiciosRepositoryInterface
{
    /**
     * @return TrabajadorServicio[]
     */
    public function listar(string $proyectoId, ?TipoServicio $tipo = null, ?string $buscar = null): array;

    public function obtener(string $id, string $proyectoId): TrabajadorServicio;

    /**
     * @param  array<string, mixed>  $datos
     */
    public function crear(array $datos): TrabajadorServicio;

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(string $id, string $proyectoId, array $datos): TrabajadorServicio;

    public function eliminar(string $id, string $proyectoId): void;
}
