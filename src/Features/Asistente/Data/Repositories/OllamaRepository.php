<?php

namespace Features\Asistente\Data\Repositories;

use Features\Asistente\Data\DataSources\OllamaDataSource;
use Features\Asistente\Domain\Repositories\ModeloIARepositoryInterface;

final class OllamaRepository implements ModeloIARepositoryInterface
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres el asistente de infraestructura de un condominio. Debes responder
ÚNICAMENTE con la información contenida en el CONTEXTO entregado.

Reglas obligatorias:
- No uses conocimiento externo ni inventes datos.
- Si la respuesta no está en el CONTEXTO, responde exactamente:
  "No encuentro esa información en el documento."
- Cuando uses información del contexto, cita la página entre corchetes,
  por ejemplo: [Página 2].
- Responde en español, de forma breve y clara.
PROMPT;

    public function __construct(
        private readonly OllamaDataSource $dataSource,
    ) {}

    public function responder(string $contexto, string $pregunta): string
    {
        return $this->dataSource->chat(
            [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                [
                    'role' => 'user',
                    'content' => "CONTEXTO:\n{$contexto}\n\nPREGUNTA:\n{$pregunta}",
                ],
            ],
            (int) config('services.ollama.num_ctx', 8192),
        );
    }
}
