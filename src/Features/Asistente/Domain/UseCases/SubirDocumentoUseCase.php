<?php

namespace Features\Asistente\Domain\UseCases;

use Features\Asistente\Domain\Entities\Documento;
use Features\Asistente\Domain\Exceptions\DocumentoMuyGrandeException;
use Features\Asistente\Domain\Exceptions\DocumentoYaExisteException;
use Features\Asistente\Domain\Repositories\DocumentoRepositoryInterface;
use Features\Asistente\Domain\Repositories\ExtractorPdfInterface;

final class SubirDocumentoUseCase
{
    public function __construct(
        private readonly DocumentoRepositoryInterface $repository,
        private readonly ExtractorPdfInterface $extractor,
    ) {}

    /**
     * @throws DocumentoYaExisteException
     * @throws DocumentoMuyGrandeException
     */
    public function execute(
        string $proyectoId,
        ?string $subidoPor,
        string $nombreOriginal,
        string $rutaFisica,
        string $rutaAlmacenada,
    ): Documento {
        if ($this->repository->obtenerPorProyecto($proyectoId) !== null) {
            throw DocumentoYaExisteException::becauseProjectAlreadyHasDocument();
        }

        $paginas = $this->extractor->extraer($rutaFisica);

        if ($paginas->estaVacio()) {
            throw DocumentoMuyGrandeException::becauseHasNoText();
        }

        $texto = $paginas->textoConMarcadores($nombreOriginal);
        $tamanoTokens = max(1, (int) ceil(mb_strlen($texto, 'UTF-8') / 4));
        $limite = (int) config('services.ollama.max_document_tokens', 6000);

        if ($tamanoTokens > $limite) {
            throw DocumentoMuyGrandeException::becauseExceedsTokenBudget($tamanoTokens, $limite);
        }

        return $this->repository->crear(
            proyectoId: $proyectoId,
            subidoPor: $subidoPor,
            nombreOriginal: $nombreOriginal,
            ruta: $rutaAlmacenada,
            paginas: $paginas->cantidad(),
            texto: $texto,
            tamanoTokens: $tamanoTokens,
        );
    }
}
