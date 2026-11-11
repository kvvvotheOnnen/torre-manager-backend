<?php

namespace Features\Asistente\Domain\Entities;

final class RespuestaAsistente
{
    /**
     * @param  FuenteRespuesta[]  $fuentes
     */
    public function __construct(
        private readonly string $respuesta,
        private readonly array $fuentes,
    ) {}

    public function respuesta(): string
    {
        return $this->respuesta;
    }

    /**
     * @return FuenteRespuesta[]
     */
    public function fuentes(): array
    {
        return $this->fuentes;
    }
}
