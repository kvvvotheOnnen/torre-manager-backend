<?php

namespace Features\Superadmin\Domain\Repositories;

use Features\Superadmin\Domain\Entities\Administrador;
use Features\Superadmin\Domain\Entities\ProyectoDetalle;
use Features\Superadmin\Domain\Entities\ResumenProyecto;
use Features\Superadmin\Domain\Entities\TipoCondominio;
use Features\Superadmin\Domain\Entities\ModuloSistema;
use Features\Superadmin\Domain\Exceptions\EmailDuplicadoException;
use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;

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

    /**
     * Crea un proyecto (tenant) con su estructura de infraestructura y anexa a
     * los administradores indicados.
     *
     * @param ModuloSistema[] $modulos
     * @param array<int, array{nombre: string, tipo: \Features\Infraestructura\Domain\Entities\TipoAgrupacion, identificadores: string[]}> $agrupaciones
     * @param array<int, array{nombre: string, tipo?: ?string, atributos?: ?array<string, mixed>}> $instalaciones
     * @param string[] $administradoresIds
     *
     * @throws RecursoNoEncontradoException
     */
    public function crearProyectoConAdministradores(
        string $nombre,
        ?string $descripcion,
        TipoCondominio $tipoCondominio,
        array $modulos,
        array $agrupaciones,
        array $instalaciones,
        array $administradoresIds,
    ): ProyectoDetalle;

    /**
     * Obtiene el detalle de un proyecto por su identificador.
     *
     * @throws RecursoNoEncontradoException
     */
    public function buscarProyecto(string $id): ProyectoDetalle;

    /**
     * Actualiza los datos editables de un proyecto (tenant) y su asociación
     * con administradores.
     *
     * @param ModuloSistema[] $modulos
     * @param array<int, array{nombre: string, tipo?: ?string, atributos?: ?array<string, mixed>}> $instalaciones
     * @param string[] $administradoresIds
     *
     * @throws RecursoNoEncontradoException
     */
    public function actualizarProyecto(
        string $id,
        string $nombre,
        ?string $descripcion,
        array $modulos,
        array $instalaciones,
        array $administradoresIds,
    ): ProyectoDetalle;

    /**
     * Lista los proyectos a los que un usuario está anexado.
     *
     * @return ProyectoDetalle[]
     */
    public function listarProyectosDelUsuario(string $usuarioId): array;
}
