<?php

namespace Features\Paqueteria\Domain\Repositories;

use Features\Paqueteria\Domain\Entities\Paquete;

interface PaqueteriaRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function registrar(array $datos): Paquete;

    /**
     * @return Paquete[]
     */
    public function listar(string $usuarioId, string $rol, ?string $proyectoId = null): array;

    public function marcarRetirado(string $paqueteId, string $retiradoPorNombre): Paquete;

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws PaqueteNoEncontradoException
     */
    public function actualizar(string $paqueteId, array $datos): Paquete;
}
