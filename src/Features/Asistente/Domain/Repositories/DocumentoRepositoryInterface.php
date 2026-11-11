<?php

namespace Features\Asistente\Domain\Repositories;

use Features\Asistente\Domain\Entities\Documento;

interface DocumentoRepositoryInterface
{
    public function obtenerPorProyecto(string $proyectoId): ?Documento;

    public function crear(
        string $proyectoId,
        ?string $subidoPor,
        string $nombreOriginal,
        string $ruta,
        int $paginas,
        string $texto,
        int $tamanoTokens,
    ): Documento;

    public function eliminar(Documento $documento): void;

    public function administradorTieneProyecto(string $usuarioId, string $proyectoId): bool;

    public function primerProyectoDelAdministrador(string $usuarioId): ?string;
}
