<?php

namespace Features\SolicitudesResidente\Domain\Repositories;

use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;

interface SolicitudResidenteRepositoryInterface
{
    public function obtener(string $id): ?SolicitudResidente;

    /** @return SolicitudResidente[] */
    public function listarPendientes(string $proyectoId): array;

    /** @return SolicitudResidente[] */
    public function listarHistorial(string $proyectoId, int $limite = 100): array;

    /** @return SolicitudResidente[] */
    public function listarPorUsuario(string $usuarioId): array;

    public function transferirMascotasAUnidad(string $id, string $unidadPrincipalId): void;

    public function marcarAprobada(string $id, string $decididoPorId): void;

    public function marcarRechazada(string $id, string $decididoPorId, string $motivo): void;
}
