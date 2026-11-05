<?php

namespace Features\Superadmin\Domain\Repositories;

use Features\Infraestructura\Domain\Entities\TipoAgrupacion;
use Features\Infraestructura\Domain\Entities\TipoUnidadAccesoria;
use Features\Superadmin\Domain\Entities\Administrador;
use Features\Superadmin\Domain\Entities\ModuloSistema;
use Features\Superadmin\Domain\Entities\ProyectoDetalle;
use Features\Superadmin\Domain\Entities\ResumenProyecto;
use Features\Superadmin\Domain\Entities\TipoCondominio;
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
     * @param  ModuloSistema[]  $modulos
     * @param  array<int, array{nombre: string, tipo: TipoAgrupacion, identificadores: string[]}>  $agrupaciones
     * @param  array<int, array{nombre: string, capacidad: int, descripcion?: ?string, atributos?: ?array<string, mixed>}>  $instalaciones
     * @param  string[]  $administradoresIds
     * @param  array<int, array{tipo: TipoUnidadAccesoria, identificadores: string[]}>  $accesorias
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
        array $accesorias,
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
     * @param  ModuloSistema[]  $modulos
     * @param  array<int, array{nombre: string, capacidad: int, descripcion?: ?string, atributos?: ?array<string, mixed>}>  $instalaciones
     * @param  string[]  $administradoresIds
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
     * Inicia un proyecto en estado borrador con los datos mínimos.
     *
     * @throws RecursoNoEncontradoException
     */
    public function iniciarProyectoBorrador(string $nombre, ?string $descripcion): ProyectoDetalle;

    /**
     * Aplica sobre un borrador únicamente los datos recibidos de un paso.
     *
     * @param  array<int, array{nombre: string, tipo: TipoAgrupacion, identificadores: string[]}>|null  $agrupaciones
     * @param  array<int, array{nombre: string, capacidad: int, descripcion?: ?string, atributos?: ?array<string, mixed>}>|null  $instalaciones
     * @param  ModuloSistema[]|null  $modulos
     * @param  string[]|null  $administradoresIds
     *
     * @throws RecursoNoEncontradoException
     */
    public function guardarPasoProyecto(
        string $id,
        ?string $nombre,
        ?string $descripcion,
        ?TipoCondominio $tipoCondominio,
        ?array $agrupaciones,
        ?array $instalaciones,
        ?array $modulos,
        ?array $administradoresIds,
        bool $resetEstructura,
    ): ProyectoDetalle;

    /**
     * @return array{agrupaciones: int, unidades_principales: int, administradores: int, modulos: int}
     *
     * @throws RecursoNoEncontradoException
     */
    public function contarDatosProyecto(string $id): array;

    /**
     * Marca un borrador como proyecto completo.
     *
     * @throws RecursoNoEncontradoException
     */
    public function marcarProyectoCompleto(string $id): ProyectoDetalle;

    /**
     * Elimina un borrador en creación.
     *
     * @throws RecursoNoEncontradoException
     */
    public function descartarProyectoBorrador(string $id): void;

    /**
     * Elimina un proyecto (tenant) junto con su estructura, accesorias,
     * instalaciones extra y asociaciones con administradores.
     *
     * @throws RecursoNoEncontradoException
     */
    public function eliminarProyecto(string $id): void;

    /**
     * Lista los proyectos a los que un usuario está anexado.
     *
     * @return ProyectoDetalle[]
     */
    public function listarProyectosDelUsuario(string $usuarioId): array;
}
