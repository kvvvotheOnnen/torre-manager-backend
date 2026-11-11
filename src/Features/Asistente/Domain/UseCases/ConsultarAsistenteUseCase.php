<?php

namespace Features\Asistente\Domain\UseCases;

use Features\Asistente\Domain\Entities\Documento;
use Features\Asistente\Domain\Entities\FuenteRespuesta;
use Features\Asistente\Domain\Entities\RespuestaAsistente;
use Features\Asistente\Domain\Exceptions\DocumentoNoDisponibleException;
use Features\Asistente\Domain\Repositories\DocumentoRepositoryInterface;
use Features\Asistente\Domain\Repositories\ModeloIARepositoryInterface;

final class ConsultarAsistenteUseCase
{
    public function __construct(
        private readonly DocumentoRepositoryInterface $repository,
        private readonly ModeloIARepositoryInterface $modelo,
    ) {}

    /**
     * @throws DocumentoNoDisponibleException
     */
    public function execute(string $proyectoId, string $pregunta): RespuestaAsistente
    {
        $documento = $this->repository->obtenerPorProyecto($proyectoId);

        if ($documento === null) {
            throw DocumentoNoDisponibleException::becauseProjectHasNoDocument();
        }

        $respuesta = $this->modelo->responder($documento->texto(), $pregunta);

        return new RespuestaAsistente(
            respuesta: $respuesta,
            fuentes: $this->extraerFuentes($documento, $respuesta),
        );
    }

    /**
     * @return FuenteRespuesta[]
     */
    private function extraerFuentes(Documento $documento, string $respuesta): array
    {
        if (! preg_match_all('/\[Página\s+(\d+)\]/iu', $respuesta, $coincidencias)) {
            return [];
        }

        $paginas = array_values(array_unique(array_map('intval', $coincidencias[1])));
        sort($paginas);

        return array_map(
            static fn (int $pagina) => new FuenteRespuesta($documento->nombreOriginal(), $pagina),
            $paginas,
        );
    }
}
