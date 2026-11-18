<?php

namespace Features\Dashboard\Data;

use App\Models\UnidadUsuario;
use Features\Acceso\Domain\Services\AccesoProyectoService;
use Features\Dashboard\Domain\Contracts\ResolutorAmbito;
use Features\Dashboard\Domain\Entities\ReferenciaRecurso;
use Illuminate\Database\Eloquent\Builder;

/**
 * Implementación vigente del resolvedor de ámbito.
 *
 * Si mañana las unidades se agrupan de otra forma (departamentos, torres,
 * sectores), esta es la única clase que cambia: los módulos siguen
 * preguntando por la referencia atómica.
 */
final class ResolutorAmbitoUnidad implements ResolutorAmbito
{
    /** Roles que operan sobre cualquier recurso del proyecto. */
    private const ROLES_DE_PROYECTO_COMPLETO = ['administrador', 'conserje'];

    public function __construct(
        private readonly AccesoProyectoService $acceso,
    ) {}

    public function puedeAccederA(
        string $usuarioId,
        string $proyectoId,
        ReferenciaRecurso $recurso,
    ): bool {
        if (! $this->acceso->puedeAcceder($usuarioId, $proyectoId)) {
            return false;
        }

        if ($recurso->tipo() === 'proyecto') {
            return true;
        }

        $rol = $this->acceso->rolEn($usuarioId, $proyectoId);
        $rolDeProyectoCompleto = $rol !== null
            && in_array($rol->value, self::ROLES_DE_PROYECTO_COMPLETO, true);

        if ($recurso->tipo() === 'propio') {
            return $rolDeProyectoCompleto || $recurso->id() === $usuarioId;
        }

        if ($recurso->tipo() !== 'unidad') {
            return false;
        }

        if ($rolDeProyectoCompleto) {
            return true;
        }

        return UnidadUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('unidad_principal_id', $recurso->id())
            ->where('es_actual', true)
            ->whereHas('unidadPrincipal', function (Builder $unidad) use ($proyectoId): void {
                $unidad->whereHas('agrupacion', function (Builder $agrupacion) use ($proyectoId): void {
                    $agrupacion->where('proyecto_id', $proyectoId);
                });
            })
            ->exists();
    }
}
