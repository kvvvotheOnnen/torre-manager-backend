<?php

namespace Features\Correo\Domain\Repositories;

use Features\Correo\Domain\Entities\Correo;
use Features\Correo\Domain\Exceptions\EnvioCorreoException;

interface CorreoRepositoryInterface
{
    /**
     * Envía un correo transaccional.
     *
     * @throws EnvioCorreoException
     */
    public function enviar(Correo $correo): void;
}
