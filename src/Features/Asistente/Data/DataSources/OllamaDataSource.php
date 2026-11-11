<?php

namespace Features\Asistente\Data\DataSources;

use Features\Asistente\Domain\Exceptions\ModeloNoDisponibleException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class OllamaDataSource
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, int $numCtx): string
    {
        $baseUrl = rtrim((string) config('services.ollama.base_url'), '/');

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('services.ollama.timeout', 180))
                ->post("{$baseUrl}/api/chat", [
                    'model' => (string) config('services.ollama.chat_model'),
                    'stream' => false,
                    'messages' => $messages,
                    'options' => [
                        'temperature' => 0.1,
                        'num_ctx' => $numCtx,
                    ],
                ]);
        } catch (Throwable $exception) {
            throw ModeloNoDisponibleException::becauseRequestFailed($exception);
        }

        if (! $response->successful()) {
            throw ModeloNoDisponibleException::becauseResponseIsEmpty();
        }

        $contenido = $response->json('message.content');

        if (! is_string($contenido) || trim($contenido) === '') {
            throw ModeloNoDisponibleException::becauseResponseIsEmpty();
        }

        return trim($contenido);
    }
}
