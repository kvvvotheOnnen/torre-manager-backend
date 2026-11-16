<?php

namespace Features\Acceso\Domain\UseCases;

use Features\Acceso\Domain\Entities\VinculoUnidad;
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
        return DB::transaction(function () use ($vinculoId, $fechaFin) {
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
    }
}
