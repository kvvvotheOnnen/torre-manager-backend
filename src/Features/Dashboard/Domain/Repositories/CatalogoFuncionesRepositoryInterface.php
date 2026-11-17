<?php

namespace Features\Dashboard\Domain\Repositories;

use Features\Dashboard\Domain\Entities\Funcion;

interface CatalogoFuncionesRepositoryInterface
{
    /** @return Funcion[] */
    public function listar(): array;
}
