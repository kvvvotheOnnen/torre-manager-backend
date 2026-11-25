<?php

namespace Features\Marketplace\Domain\Repositories;

use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\EstadoPublicacion;
use Features\Marketplace\Domain\Entities\PublicacionMarketplace;

interface MarketplaceRepositoryInterface
{
    /**
     * @return PublicacionMarketplace[]
     */
    public function listar(
        string $proyectoId,
        ?CategoriaProducto $categoria = null,
        ?string $buscar = null,
        ?EstadoPublicacion $estado = null,
        bool $todas = false,
        ?string $excluirUsuarioId = null,
    ): array;

    /**
     * Publicaciones de un usuario en los estados indicados.
     *
     * @return PublicacionMarketplace[]
     */
    public function listarDeUsuario(string $usuarioId, string $proyectoId, EstadoPublicacion ...$estados): array;

    /**
     * @return PublicacionMarketplace[]
     */
    public function listarFavoritas(string $usuarioId, string $proyectoId): array;

    /**
     * Solicitudes de publicación pendientes de aprobación, la más antigua primero.
     *
     * @return PublicacionMarketplace[]
     */
    public function listarPendientes(string $proyectoId): array;

    public function contarPendientes(string $proyectoId): int;

    public function obtener(string $id, string $proyectoId): PublicacionMarketplace;

    /**
     * @param  array<string, mixed>  $datos
     */
    public function crear(array $datos): PublicacionMarketplace;

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(string $id, string $proyectoId, array $datos): PublicacionMarketplace;

    public function esFavorita(string $publicacionId, string $usuarioId): bool;

    /**
     * @return string[]
     */
    public function idsFavoritas(string $usuarioId, string $proyectoId): array;

    public function agregarFavorito(string $publicacionId, string $usuarioId): void;

    public function quitarFavorito(string $publicacionId, string $usuarioId): void;
}
