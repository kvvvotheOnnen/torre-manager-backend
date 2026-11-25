<?php

namespace Features\Paqueteria\Domain\UseCases;

use Features\Paqueteria\Domain\Entities\Paquete;
use Features\Paqueteria\Domain\Events\PaqueteRegistrado;
use Features\Paqueteria\Domain\Repositories\PaqueteriaRepositoryInterface;

final class RegistrarPaqueteUseCase
{
    public function __construct(
        private readonly PaqueteriaRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     */
    public function execute(array $datos): Paquete
    {
        $paquete = $this->repository->registrar($datos);

        event(new PaqueteRegistrado(
            paqueteId: $paquete->id(),
            proyectoId: $paquete->proyectoId(),
            unidadPrincipalId: $paquete->unidadPrincipalId(),
            unidadIdentificador: $paquete->unidadIdentificador(),
            descripcion: $paquete->descripcion(),
            remitente: $paquete->remitente(),
            residenteId: $paquete->residenteId(),
        ));

        return $paquete;
    }
}
