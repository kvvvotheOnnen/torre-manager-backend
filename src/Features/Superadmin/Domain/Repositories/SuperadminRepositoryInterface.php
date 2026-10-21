<?php

namespace Features\Superadmin\Domain\Repositories;

use Features\Superadmin\Domain\Entities\Administrador;
use Features\Superadmin\Domain\Entities\ResumenProyecto;
use Features\Superadmin\Domain\Exceptions\EmailDuplicadoException;

interface SuperadminRepositoryInterface
{
    /**
     * Lista todos los proyectos (tenants) de la plataforma con sus conteos.
     *
     * @return ResumenProyecto[]
     */
    public function listarProyectosConConteos(): array;

    /**
     * Lista los usuarios con rol administrador.
     *
     * @return Administrador[]
     */
    public function listarAdministradores(): array;

    /**
     * Crea una cuenta con rol administrador.
     *
     * @throws EmailDuplicadoException
     */
    public function crearAdministrador(string $nombre, string $correo, string $password): Administrador;
}
