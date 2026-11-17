<?php

namespace Features\SolicitudesResidente\Domain\Repositories;

use Features\SolicitudesResidente\Domain\Entities\OrigenSolicitud;
use Features\SolicitudesResidente\Domain\Entities\SolicitudResidente;
use Features\SolicitudesResidente\Domain\Entities\TipoSolicitud;

interface SolicitudResidenteRepositoryInterface
{
    public function unidadPerteneceAProyecto(string $unidadId, string $proyectoId): bool;

    /** @param string[] $emails @param string[] $ruts */
    public function existePendienteDuplicada(string $unidadId, array $emails, array $ruts): bool;

    /**
     * @param array<int, array{
     *     usuario_id: ?string,
     *     nombre: string,
     *     rut: string,
     *     email: string,
     *     telefono: string,
     *     telefono_emergencia: string,
     *     nombre_emergencia: string,
     *     tipo_vinculo: string,
     *     es_principal: bool
     * }> $personas
     * @param  array<string, mixed>|null  $datosExtra
     */
    public function crear(
        string $proyectoId,
        string $unidadId,
        TipoSolicitud $tipoSolicitud,
        OrigenSolicitud $origen,
        ?string $solicitadoPorId,
        array $personas,
        ?array $datosExtra = null,
    ): SolicitudResidente;

    public function obtener(string $id): ?SolicitudResidente;

    /** @return SolicitudResidente[] */
    public function listarPendientes(string $proyectoId): array;

    /** @return SolicitudResidente[] */
    public function listarHistorial(string $proyectoId, int $limite = 100): array;

    public function marcarAprobada(string $id, string $decididoPorId): void;

    public function marcarRechazada(string $id, string $decididoPorId, string $motivo): void;

    public function actualizarUsuarioIdPersona(string $personaId, string $usuarioId): void;
}
