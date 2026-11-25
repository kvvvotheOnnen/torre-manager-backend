<?php

namespace Features\Servicios\Domain\UseCases;

use Features\Servicios\Domain\Entities\TipoServicio;
use Features\Servicios\Domain\Entities\TrabajadorServicio;
use Features\Servicios\Domain\Events\TrabajadorPublicado;
use Features\Servicios\Domain\Repositories\ServiciosRepositoryInterface;

final class CrearTrabajadorUseCase
{
    public function __construct(
        private readonly ServiciosRepositoryInterface $repository,
    ) {}

    public function execute(
        string $proyectoId,
        ?string $creadoPor,
        string $nombre,
        ?string $nombreEmpresa,
        string $telefono,
        TipoServicio $tipo,
        ?string $descripcion,
        ?string $fotoPath,
    ): TrabajadorServicio {
        $trabajador = $this->repository->crear([
            'proyecto_id' => $proyectoId,
            'nombre' => $nombre,
            'nombre_empresa' => $nombreEmpresa,
            'telefono' => $telefono,
            'tipo_servicio' => $tipo->value,
            'descripcion' => $descripcion,
            'foto_path' => $fotoPath,
            'creado_por' => $creadoPor,
        ]);

        event(new TrabajadorPublicado(
            trabajadorId: $trabajador->id(),
            proyectoId: $proyectoId,
            nombre: $trabajador->nombre(),
            tipoServicio: $tipo->etiqueta(),
            creadoPorId: $creadoPor,
        ));

        return $trabajador;
    }
}
