<?php

namespace Features\Asistente\Data\Services;

use Features\Asistente\Domain\Entities\PaginasDocumento;
use Features\Asistente\Domain\Repositories\ExtractorPdfInterface;
use Smalot\PdfParser\Parser;

final class PdfTextExtractor implements ExtractorPdfInterface
{
    public function extraer(string $rutaFisica): PaginasDocumento
    {
        $pdf = (new Parser)->parseFile($rutaFisica);

        $paginas = [];

        foreach ($pdf->getPages() as $indice => $pagina) {
            $paginas[$indice + 1] = $pagina->getText();
        }

        return new PaginasDocumento($paginas);
    }
}
