<?php

namespace Features\Acceso\Domain\UseCases;

use Features\Acceso\Domain\Entities\TipoVinculo;
use Features\Acceso\Domain\Entities\VinculoUnidad;
use Features\Acceso\Domain\Events\VinculoAsignado;
use Features\Acceso\Domain\Exceptions\UnidadNoDisponibleException;
use Features\Acceso\Domain\Repositories\MembresiaProyectoRepositoryInterface;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;
use Features\Auth\Domain\Entities\Rol;
use Illuminate\Support\Facades\DB;

final class AsignarVinculoUnidadUseCase
{
    public function __construct(
        private readonly VinculoUnidadRepositoryInterface $vinculos,
        private readonly MembresiaProyectoRepositoryInterface $membresias,
    ) {}

    public function execute(
        string $unidadPrincipalId,
        string $usuarioId,
        string $proyectoId,
        TipoVinculo $tipo,
        string $fechaInicio,
        ?string $fechaFin = null,
        bool $notificar = true,
    ): VinculoUnidad {
        if (! $this->vinculos->unidadPerteneceAProyecto($unidadPrincipalId, $proyectoId)) {
            throw UnidadNoDisponibleException::becauseUnitIsMissing();
        }

        $vinculo = DB::transaction(function () use (
            $unidadPrincipalId,
            $usuarioId,
            $proyectoId,
            $tipo,
            $fechaInicio,
            $fechaFin,
        ) {
            if ($fechaFin === null && in_array($tipo, [TipoVinculo::Propietario, TipoVinculo::Arrendatario], true)) {
                $this->vinculos->cerrarActivosDeTipo($unidadPrincipalId, $tipo, $fechaInicio);
            }

            $rol = $tipo === TipoVinculo::Propietario ? Rol::Propietario : Rol::Residente;

            $this->membresias->asegurar($proyectoId, $usuarioId, $rol);

            $vinculo = $this->vinculos->crear(
                $unidadPrincipalId,
                $usuarioId,
                $tipo,
                $fechaInicio,
                $fechaFin,
            );

            if ($fechaFin === null && $tipo->esOcupacion()) {
                $this->vinculos->sincronizarOcupanteLegacy($unidadPrincipalId, $usuarioId);
            }

            return $vinculo;
        });

        if ($notificar) {
            event(new VinculoAsignado(
                vinculoId: $vinculo->id(),
                usuarioId: $vinculo->usuarioId(),
                unidadPrincipalId: $vinculo->unidadPrincipalId(),
                proyectoId: $vinculo->proyectoId(),
                tipoVinculo: $vinculo->tipo()->value,
            ));
        }

        return $vinculo;
    }
}
