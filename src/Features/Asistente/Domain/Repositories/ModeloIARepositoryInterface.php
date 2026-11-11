<?php

namespace Features\Asistente\Domain\Repositories;

interface ModeloIARepositoryInterface
{
    public function responder(string $contexto, string $pregunta): string;
}
