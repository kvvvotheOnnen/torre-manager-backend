<?php

namespace Features\Asistente\Domain\Repositories;

use Features\Asistente\Domain\Entities\PaginasDocumento;

interface ExtractorPdfInterface
{
    public function extraer(string $rutaFisica): PaginasDocumento;
}
