<?php

namespace Features\Paqueteria\Domain\UseCases;

use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Acceso\Domain\UseCases\ObtenerContextoUnidadUseCase;
use Features\Paqueteria\Domain\Entities\DestinoPaquete;
use Features\Paqueteria\Domain\Exceptions\ResidenteNoCorrespondeException;
use Features\Paqueteria\Domain\Exceptions\UnidadNoEncontradaException;
use Features\Paqueteria\Domain\Exceptions\UnidadNoPerteneceAlProyectoException;

/**
 * Valida que una unidad sea un destino legítimo para un paquete del usuario.
 *
 * La residencia se resuelve desde los vínculos actuales de la unidad; no se
 * usa la columna legada `unidades_principales.residente_id`.
 */
final class ResolverDestinoPaqueteUseCase
{
    public function __construct(
        private readonly ObtenerContextoUnidadUseCase $obtenerContexto,
        private readonly AccesoProyectoService $acceso,
    ) {}

    /**
     * @throws UnidadNoEncontradaException
     * @throws UnidadNoPerteneceAlProyectoException
     * @throws ResidenteNoCorrespondeException
     */
    public function execute(
        string $usuarioId,
        string $unidadPrincipalId,
        ?string $proyectoIdSolicitado = null,
        ?string $residenteIdSolicitado = null,
    ): DestinoPaquete {
        $contexto = $this->obtenerContexto->execute($unidadPrincipalId);

        if ($contexto === null) {
            throw UnidadNoEncontradaException::becauseIsMissing();
        }

        if ($proyectoIdSolicitado !== null && $proyectoIdSolicitado !== $contexto->proyectoId()) {
            throw UnidadNoPerteneceAlProyectoException::becauseMismatch();
        }

        if (! $this->acceso->puedeAcceder($usuarioId, $contexto->proyectoId())) {
            throw UnidadNoEncontradaException::becauseIsMissing();
        }

        $residenteId = $contexto->ocupanteId();

        if ($residenteIdSolicitado !== null && $residenteIdSolicitado !== $residenteId) {
            throw ResidenteNoCorrespondeException::becauseMismatch();
        }

        return new DestinoPaquete(
            proyectoId: $contexto->proyectoId(),
            unidadPrincipalId: $contexto->unidadPrincipalId(),
            residenteId: $residenteId,
            unidadIdentificador: $contexto->identificador(),
        );
    }
}
