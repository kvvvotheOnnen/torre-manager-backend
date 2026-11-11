<?php

namespace Features\Asistente\Domain\UseCases;

use Features\Asistente\Domain\Exceptions\ProyectoNoDisponibleException;
use Features\Asistente\Domain\Repositories\DocumentoRepositoryInterface;

final class ResolverProyectoAdministradorUseCase
{
    public function __construct(
        private readonly DocumentoRepositoryInterface $repository,
    ) {}

    /**
     * @throws ProyectoNoDisponibleException
     */
    public function execute(string $usuarioId, ?string $proyectoIdSolicitado): string
    {
        if ($proyectoIdSolicitado !== null && $proyectoIdSolicitado !== '') {
            if (! $this->repository->administradorTieneProyecto($usuarioId, $proyectoIdSolicitado)) {
                throw ProyectoNoDisponibleException::becauseUserHasNoAccess();
            }

            return $proyectoIdSolicitado;
        }

        $proyectoId = $this->repository->primerProyectoDelAdministrador($usuarioId);

        if ($proyectoId === null) {
            throw ProyectoNoDisponibleException::becauseUserHasNoProject();
        }

        return $proyectoId;
    }
}
