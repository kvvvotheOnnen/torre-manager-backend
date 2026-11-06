<?php

namespace Features\Dashboard\Data\Repositories;

use App\Models\Proyecto;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Features\Dashboard\Domain\Entities\ProyectoConModulos;
use Features\Dashboard\Domain\Exceptions\ProyectoNoDisponibleException;
use Features\Dashboard\Domain\Repositories\DashboardRepositoryInterface;
use Features\Superadmin\Domain\Entities\EstadoProyecto;
use Features\Superadmin\Domain\Entities\ModuloSistema;
use Illuminate\Database\Eloquent\Collection;

final class DashboardRepository implements DashboardRepositoryInterface
{
    public function obtenerProyectoDelUsuario(
        string $usuarioId,
        ?string $proyectoIdSolicitado = null,
    ): ProyectoConModulos {
        $usuario = User::query()->find($usuarioId);

        if ($usuario === null) {
            throw ProyectoNoDisponibleException::becauseProjectIsMissing();
        }

        $proyectos = $this->proyectosDisponibles($usuario);
        $proyecto = $proyectoIdSolicitado !== null && $proyectoIdSolicitado !== ''
            ? $proyectos->firstWhere('id', (int) $proyectoIdSolicitado)
            : $proyectos->first();

        if ($proyecto === null) {
            throw $proyectoIdSolicitado !== null && $proyectoIdSolicitado !== ''
                ? ProyectoNoDisponibleException::becauseUserHasNoAccess()
                : ProyectoNoDisponibleException::becauseProjectIsMissing();
        }

        return $this->toProyectoConModulos($proyecto);
    }

    /**
     * @return Collection<int, Proyecto>
     */
    private function proyectosDisponibles(User $usuario): Collection
    {
        if ($usuario->rol === Rol::Superadmin->value) {
            return Proyecto::query()
                ->where('estado', EstadoProyecto::Completo->value)
                ->orderBy('nombre')
                ->get();
        }

        $proyectos = $usuario->proyectos()
            ->where('estado', EstadoProyecto::Completo->value)
            ->orderBy('nombre')
            ->get();

        if ($usuario->proyecto_id !== null && ! $proyectos->contains('id', $usuario->proyecto_id)) {
            $proyectoLegado = Proyecto::query()
                ->whereKey($usuario->proyecto_id)
                ->where('estado', EstadoProyecto::Completo->value)
                ->first();

            if ($proyectoLegado !== null) {
                $proyectos->push($proyectoLegado);
                $proyectos = $proyectos->sortBy('nombre')->values();
            }
        }

        return $proyectos;
    }

    private function toProyectoConModulos(Proyecto $proyecto): ProyectoConModulos
    {
        $modulos = array_values(array_filter(array_map(
            static fn (string $modulo) => ModuloSistema::tryFrom($modulo),
            $proyecto->modulos ?? [],
        )));

        return new ProyectoConModulos(
            id: (string) $proyecto->getKey(),
            modulos: $modulos,
        );
    }
}
