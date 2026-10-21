<?php

namespace Features\Infraestructura\Domain\Repositories;

use Features\Infraestructura\Domain\Entities\Agrupacion;
use Features\Infraestructura\Domain\Entities\ArbolInfraestructura;
use Features\Infraestructura\Domain\Entities\Proyecto;
use Features\Infraestructura\Domain\Entities\TipoAgrupacion;
use Features\Infraestructura\Domain\Entities\TipoUnidadAccesoria;
use Features\Infraestructura\Domain\Entities\UnidadAccesoria;
use Features\Infraestructura\Domain\Entities\UnidadPrincipal;
use Features\Infraestructura\Domain\Exceptions\RecursoDuplicadoException;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;

interface InfraestructuraRepositoryInterface
{
    /** @return Proyecto[] */
    public function listarProyectos(): array;

    /** @throws RecursoDuplicadoException */
    public function crearProyecto(string $nombre, ?string $descripcion): Proyecto;

    public function obtenerProyecto(string $proyectoId): Proyecto;

    /** @throws RecursoNoEncontradoException */
    public function obtenerProyectoDelUsuario(string $usuarioId): Proyecto;

    /**
     * Resuelve el proyecto activo: el del usuario, o el solicitado (superadmin),
     * o el primero disponible como último recurso.
     *
     * @throws RecursoNoEncontradoException
     */
    public function obtenerProyectoActivo(string $usuarioId, ?string $proyectoIdSolicitado): Proyecto;

    /**
     * @param UnidadPrincipal[] $unidades
     *
     * @throws RecursoDuplicadoException
     */
    public function crearAgrupacionEnCascada(string $proyectoId, string $nombre, TipoAgrupacion $tipo, array $unidades): Agrupacion;

    /**
     * @return Agrupacion[]
     */
    public function listarAgrupaciones(string $proyectoId): array;

    /** @throws RecursoNoEncontradoException */
    public function crearUnidadAccesoria(string $proyectoId, string $identificador, TipoUnidadAccesoria $tipo, ?string $unidadPrincipalId): UnidadAccesoria;

    /**
     * @throws RecursoNoEncontradoException
     */
    public function asignarUnidadAccesoria(string $unidadAccesoriaId, ?string $unidadPrincipalId): UnidadAccesoria;

    /** @throws RecursoNoEncontradoException */
    public function obtenerArbol(string $proyectoId): ArbolInfraestructura;
}
