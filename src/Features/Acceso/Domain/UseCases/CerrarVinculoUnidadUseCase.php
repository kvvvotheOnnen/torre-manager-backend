<?php

namespace Features\Acceso\Domain\UseCases;

use Features\Acceso\Domain\Entities\VinculoUnidad;
use Features\Acceso\Domain\Events\VinculoCerrado;
use Features\Acceso\Domain\Exceptions\VinculoNoEncontradoException;
use Features\Acceso\Domain\Repositories\VinculoUnidadRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class CerrarVinculoUnidadUseCase
{
    public function __construct(
        private readonly VinculoUnidadRepositoryInterface $vinculos,
    ) {}

    public function execute(string $vinculoId, ?string $fechaFin = null): VinculoUnidad
    {
        $vinculo = DB::transaction(function () use ($vinculoId, $fechaFin) {
            $actual = $this->vinculos->obtener($vinculoId);

            if ($actual === null) {
                throw VinculoNoEncontradoException::becauseVinculoIsMissing();
            }

            $vinculo = $this->vinculos->cerrar(
                $vinculoId,
                $fechaFin ?? now()->toDateString(),
            );

            if ($actual->esActual() && $actual->tipo()->esOcupacion()) {
                $this->vinculos->sincronizarOcupanteLegacy($actual->unidadPrincipalId(), null);
            }

            return $vinculo;
        });

        event(new VinculoCerrado(
            vinculoId: $vinculo->id(),
            usuarioId: $vinculo->usuarioId(),
            unidadPrincipalId: $vinculo->unidadPrincipalId(),
            proyectoId: $vinculo->proyectoId(),
            tipoVinculo: $vinculo->tipo()->value,
        ));

        return $vinculo;
    }
}
