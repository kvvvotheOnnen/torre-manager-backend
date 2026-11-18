<?php

namespace Features\Servicios\Domain\UseCases;

use Features\Servicios\Domain\Entities\TrabajadorServicio;
use Features\Servicios\Domain\Repositories\ServiciosRepositoryInterface;

final class ActualizarTrabajadorUseCase
{
    public function __construct(
        private readonly ServiciosRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     * @return array{trabajador: TrabajadorServicio, fotoAnterior: ?string}
     */
    public function execute(string $id, string $proyectoId, array $datos): array
    {
        $anterior = $this->repository->obtener($id, $proyectoId);
        $trabajador = $this->repository->actualizar($id, $proyectoId, $datos);
        $reemplazaFoto = array_key_exists('foto_path', $datos) && $datos['foto_path'] !== null;

        return [
            'trabajador' => $trabajador,
            'fotoAnterior' => $reemplazaFoto ? $anterior->fotoPath() : null,
        ];
    }
}
