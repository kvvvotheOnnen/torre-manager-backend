<?php

namespace Features\Asistente\Domain\UseCases;

use Features\Asistente\Domain\Exceptions\DocumentoNoDisponibleException;
use Features\Asistente\Domain\Repositories\DocumentoRepositoryInterface;

final class EliminarDocumentoUseCase
{
    public function __construct(
        private readonly DocumentoRepositoryInterface $repository,
    ) {}

    /**
     * @throws DocumentoNoDisponibleException
     */
    public function execute(string $proyectoId): void
    {
        $documento = $this->repository->obtenerPorProyecto($proyectoId);

        if ($documento === null) {
            throw DocumentoNoDisponibleException::becauseProjectHasNoDocument();
        }

        $this->repository->eliminar($documento);
    }
}
