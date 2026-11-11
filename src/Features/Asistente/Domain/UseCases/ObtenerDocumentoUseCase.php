<?php

namespace Features\Asistente\Domain\UseCases;

use Features\Asistente\Domain\Entities\Documento;
use Features\Asistente\Domain\Repositories\DocumentoRepositoryInterface;

final class ObtenerDocumentoUseCase
{
    public function __construct(
        private readonly DocumentoRepositoryInterface $repository,
    ) {}

    public function execute(string $proyectoId): ?Documento
    {
        return $this->repository->obtenerPorProyecto($proyectoId);
    }
}
