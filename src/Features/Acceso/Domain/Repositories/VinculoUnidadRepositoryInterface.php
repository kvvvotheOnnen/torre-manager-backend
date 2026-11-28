<?php

namespace Features\Acceso\Domain\Repositories;

use Features\Acceso\Domain\Entities\TipoVinculo;
use Features\Acceso\Domain\Entities\UnidadContexto;
use Features\Acceso\Domain\Entities\VinculoUnidad;

interface VinculoUnidadRepositoryInterface
{
    /** @return VinculoUnidad[] */
    public function listarPorUsuario(string $usuarioId, ?string $proyectoId = null): array;

    /** @return VinculoUnidad[] */
    public function listarPorUnidad(string $unidadPrincipalId): array;

    /**
     * IDs de los usuarios con vínculo actual en la unidad.
     *
     * @return string[]
     */
    public function usuariosActualesDeUnidad(string $unidadPrincipalId): array;

    public function crear(
        string $unidadPrincipalId,
        string $usuarioId,
        TipoVinculo $tipo,
        string $fechaInicio,
        ?string $fechaFin = null,
    ): VinculoUnidad;

    public function cerrar(string $vinculoId, string $fechaFin): VinculoUnidad;

    /** Cierra vínculos activos del tipo indicado; devuelve cuántos cerró. */
    public function cerrarActivosDeTipo(string $unidadPrincipalId, TipoVinculo $tipo, string $fechaFin): int;

    public function obtener(string $vinculoId): ?VinculoUnidad;

    public function unidadPerteneceAProyecto(string $unidadPrincipalId, string $proyectoId): bool;

    public function sincronizarOcupanteLegacy(string $unidadPrincipalId, ?string $usuarioId): void;

    public function propietarioActual(string $unidadPrincipalId): ?VinculoUnidad;

    public function ocupanteActual(string $unidadPrincipalId): ?VinculoUnidad;

    public function esPropietarioActual(string $unidadPrincipalId, string $usuarioId): bool;

    public function esOcupanteActual(string $unidadPrincipalId, string $usuarioId): bool;

    /** Contexto de una unidad con su proyecto y ocupantes actuales. */
    public function contextoUnidad(string $unidadPrincipalId): ?UnidadContexto;

    /**
     * Contextos de todas las unidades, opcionalmente acotados a proyectos.
     *
     * `null` significa todos los proyectos (uso exclusivo de superadmin).
     *
     * @param  int[]|null  $proyectoIds
     * @return UnidadContexto[]
     */
    public function contextosDeUnidades(?array $proyectoIds = null): array;
}
