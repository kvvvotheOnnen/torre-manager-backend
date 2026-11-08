<?php

namespace Features\Correo\Data\Repositories;

use Features\Correo\Domain\Entities\Correo;
use Features\Correo\Domain\Exceptions\EnvioCorreoException;
use Features\Correo\Domain\Repositories\CorreoRepositoryInterface;
use Throwable;

class CorreoResendRepository implements CorreoRepositoryInterface
{
    public function enviar(Correo $correo): void
    {
        $apiKey = (string) config('services.resend.key');

        if ($apiKey === '') {
            throw EnvioCorreoException::porApiKeyNoConfigurada();
        }

        try {
            $this->despachar($apiKey, [
                'from' => (string) config('services.resend.from'),
                'to' => [$correo->destinatario()],
                'subject' => $correo->asunto(),
                'html' => $correo->html(),
            ]);
        } catch (EnvioCorreoException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw EnvioCorreoException::porFalloDelProveedor($exception);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function despachar(string $apiKey, array $payload): void
    {
        \Resend::client($apiKey)->emails->send($payload);
    }
}
