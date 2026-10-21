<?php

namespace Features\Superadmin\Domain\Repositories;

use Features\Superadmin\Domain\Entities\ResumenProyecto;

interface SuperadminRepositoryInterface
{
    /**
     * Lista todos los proyectos (tenants) de la plataforma con sus conteos.
     *
     * @return ResumenProyecto[]
     */
    public function listarProyectosConConteos(): array;
}
