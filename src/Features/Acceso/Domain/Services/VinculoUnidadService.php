<?php

namespace Features\Acceso\Domain\Services;

use App\Models\UnidadPrincipal;
use App\Models\UnidadUsuario;
use Features\Acceso\Domain\Entities\TipoVinculo;

final class VinculoUnidadService
{
    public function esPropietarioActual(string $usuarioId, UnidadPrincipal $unidad): bool
    {
        return UnidadUsuario::query()
            ->where('unidad_principal_id', $unidad->getKey())
            ->where('usuario_id', $usuarioId)
            ->where('tipo_vinculo', TipoVinculo::Propietario->value)
            ->where('es_actual', true)
            ->exists();
    }

    public function esOcupanteActual(string $usuarioId, UnidadPrincipal $unidad): bool
    {
        return UnidadUsuario::query()
            ->where('unidad_principal_id', $unidad->getKey())
            ->where('usuario_id', $usuarioId)
            ->whereIn('tipo_vinculo', [
                TipoVinculo::Arrendatario->value,
                TipoVinculo::Residente->value,
                TipoVinculo::Familiar->value,
            ])
            ->where('es_actual', true)
            ->exists();
    }

    public function propietarioActual(UnidadPrincipal $unidad): ?UnidadUsuario
    {
        return UnidadUsuario::query()
            ->with('usuario')
            ->where('unidad_principal_id', $unidad->getKey())
            ->where('tipo_vinculo', TipoVinculo::Propietario->value)
            ->where('es_actual', true)
            ->first();
    }

    public function ocupanteActual(UnidadPrincipal $unidad): ?UnidadUsuario
    {
        return UnidadUsuario::query()
            ->with('usuario')
            ->where('unidad_principal_id', $unidad->getKey())
            ->whereIn('tipo_vinculo', [
                TipoVinculo::Arrendatario->value,
                TipoVinculo::Residente->value,
            ])
            ->where('es_actual', true)
            ->orderByRaw("case when tipo_vinculo = 'arrendatario' then 0 else 1 end")
            ->first();
    }
}
