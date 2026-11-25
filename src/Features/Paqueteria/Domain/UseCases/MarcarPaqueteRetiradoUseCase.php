<?php

namespace Features\Paqueteria\Domain\UseCases;

use Features\Paqueteria\Domain\Entities\Paquete;
use Features\Paqueteria\Domain\Events\PaqueteRetirado;
use Features\Paqueteria\Domain\Exceptions\PaqueteNoEncontradoException;
use Features\Paqueteria\Domain\Repositories\PaqueteriaRepositoryInterface;

final class MarcarPaqueteRetiradoUseCase
{
    public function __construct(
        private readonly PaqueteriaRepositoryInterface $repository,
    ) {}

    /**
     * @throws PaqueteNoEncontradoException
     */
    public function execute(string $paqueteId, string $retiradoPorNombre): Paquete
    {
        $paquete = $this->repository->marcarRetirado($paqueteId, $retiradoPorNombre);

        event(new PaqueteRetirado(
            paqueteId: $paquete->id(),
            proyectoId: $paquete->proyectoId(),
            unidadPrincipalId: $paquete->unidadPrincipalId(),
            unidadIdentificador: $paquete->unidadIdentificador(),
            descripcion: $paquete->descripcion(),
            retiradoPorNombre: $retiradoPorNombre,
            residenteId: $paquete->residenteId(),
        ));

        return $paquete;
    }
}
