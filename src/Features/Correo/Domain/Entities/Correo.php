<?php

namespace Features\Correo\Domain\Entities;

final class Correo
{
    public function __construct(
        private readonly string $destinatario,
        private readonly string $asunto,
        private readonly string $html,
    ) {}

    public function destinatario(): string
    {
        return $this->destinatario;
    }

    public function asunto(): string
    {
        return $this->asunto;
    }

    public function html(): string
    {
        return $this->html;
    }
}
