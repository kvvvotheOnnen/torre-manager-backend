<?php

namespace Features\Infraestructura\Data\Repositories;

use App\Models\Agrupacion;
use App\Models\Proyecto;
use App\Models\UnidadAccesoria;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Features\Infraestructura\Domain\Entities\ArbolAgrupacion;
use Features\Infraestructura\Domain\Entities\ArbolInfraestructura;
use Features\Infraestructura\Domain\Entities\ArbolUnidadPrincipal;
use Features\Infraestructura\Domain\Entities\Proyecto as ProyectoEntity;
use Features\Infraestructura\Domain\Entities\TipoAgrupacion;
use Features\Infraestructura\Domain\Entities\TipoUnidadAccesoria;
use Features\Infraestructura\Domain\Entities\UnidadAccesoria as UnidadAccesoriaEntity;
use Features\Infraestructura\Domain\Entities\UnidadPrincipal as UnidadPrincipalEntity;
use Features\Infraestructura\Domain\Exceptions\RecursoDuplicadoException;
use Features\Infraestructura\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Infraestructura\Domain\Repositories\InfraestructuraRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class InfraestructuraRepository implements InfraestructuraRepositoryInterface
{
    public function listarProyectos(): array
    {
        return Proyecto::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Proyecto $proyecto) => $proyecto->toEntity())
            ->all();
    }

    public function crearProyecto(string $nombre, ?string $descripcion): ProyectoEntity
    {
        try {
            $proyecto = Proyecto::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
            ]);
        } catch (QueryException $exception) {
            Log::error('No fue posible crear el proyecto.', ['exception' => $exception->getMessage()]);

            throw new RecursoDuplicadoException('Ya existe un proyecto con datos similares.');
        }

        return $proyecto->toEntity();
    }

    public function obtenerProyecto(string $proyectoId): ProyectoEntity
    {
        $proyecto = Proyecto::find($proyectoId);

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('El proyecto no existe.');
        }

        return $proyecto->toEntity();
    }

    public function obtenerProyectoDelUsuario(string $usuarioId): ProyectoEntity
    {
        $usuario = \App\Models\User::query()->with('proyecto')->find($usuarioId);

        if ($usuario === null || $usuario->proyecto === null) {
            throw new RecursoNoEncontradoException('El usuario no tiene un proyecto asignado.');
        }

        return $usuario->proyecto->toEntity();
    }

    public function obtenerProyectoActivo(string $usuarioId, ?string $proyectoIdSolicitado): ProyectoEntity
    {
        $usuario = User::query()->with('proyecto')->find($usuarioId);

        if ($usuario === null) {
            throw new RecursoNoEncontradoException('El usuario no existe.');
        }

        $esSuperadmin = $usuario->rol === Rol::Superadmin->value;

        if ($esSuperadmin) {
            if ($proyectoIdSolicitado !== null && $proyectoIdSolicitado !== '') {
                return $this->buscarProyecto($proyectoIdSolicitado);
            }

            return $this->primerProyecto();
        }

        $proyectosIds = $usuario->proyectos()->pluck('proyectos.id')->map(fn ($id) => (string) $id)->all();

        if ($usuario->proyecto_id !== null) {
            array_unshift($proyectosIds, (string) $usuario->proyecto_id);
        }

        $proyectosIds = array_values(array_unique($proyectosIds));

        if ($proyectoIdSolicitado !== null && $proyectoIdSolicitado !== '') {
            if (! in_array((string) $proyectoIdSolicitado, $proyectosIds, true)) {
                throw new RecursoNoEncontradoException('No tienes acceso al proyecto solicitado.');
            }

            return $this->buscarProyecto($proyectoIdSolicitado);
        }

        if ($proyectosIds === []) {
            throw new RecursoNoEncontradoException('El usuario no tiene proyectos asignados.');
        }

        return $this->buscarProyecto($proyectosIds[0]);
    }

    private function buscarProyecto(string $proyectoId): ProyectoEntity
    {
        $proyecto = Proyecto::find($proyectoId);

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('El proyecto solicitado no existe.');
        }

        return $proyecto->toEntity();
    }

    private function primerProyecto(): ProyectoEntity
    {
        $proyecto = Proyecto::query()->orderBy('id')->first();

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('No hay proyectos registrados.');
        }

        return $proyecto->toEntity();
    }

    public function crearAgrupacionEnCascada(string $proyectoId, string $nombre, TipoAgrupacion $tipo, array $unidades): \Features\Infraestructura\Domain\Entities\Agrupacion
    {
        try {
            $agrupacion = DB::transaction(function () use ($proyectoId, $nombre, $tipo, $unidades) {
                if (Agrupacion::where('proyecto_id', $proyectoId)->where('nombre', $nombre)->exists()) {
                    throw new RecursoDuplicadoException('Ya existe una agrupación con ese nombre en el proyecto.');
                }

                $identificadores = array_map(
                    static fn (UnidadPrincipalEntity $unidad) => $unidad->identificador(),
                    $unidades,
                );

                $duplicados = UnidadPrincipal::query()
                    ->whereIn('identificador', $identificadores)
                    ->whereHas('agrupacion', static fn ($query) => $query->where('proyecto_id', $proyectoId))
                    ->pluck('identificador');

                if ($duplicados->isNotEmpty()) {
                    throw new RecursoDuplicadoException('Identificadores ya existentes en el proyecto: '.$duplicados->implode(', '));
                }

                $agrupacion = Agrupacion::create([
                    'proyecto_id' => $proyectoId,
                    'nombre' => $nombre,
                    'tipo' => $tipo->value,
                ]);

                $agrupacion->unidadesPrincipales()->createMany(
                    array_map(
                        static fn (UnidadPrincipalEntity $unidad) => ['identificador' => $unidad->identificador()],
                        $unidades,
                    ),
                );

                return $agrupacion;
            });
        } catch (RecursoDuplicadoException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            Log::error('No fue posible registrar la agrupación en cascada.', ['exception' => $exception->getMessage()]);

            throw new RecursoDuplicadoException('No fue posible registrar la agrupación: datos duplicados.');
        }

        return $agrupacion->toEntity();
    }

    public function listarAgrupaciones(string $proyectoId): array
    {
        return Agrupacion::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Agrupacion $agrupacion) => $agrupacion->toEntity())
            ->all();
    }

    public function crearUnidadAccesoria(string $proyectoId, string $identificador, TipoUnidadAccesoria $tipo, ?string $unidadPrincipalId): UnidadAccesoriaEntity
    {
        if ($unidadPrincipalId !== null) {
            $unidadPrincipal = UnidadPrincipal::query()
                ->whereHas('agrupacion', static fn ($query) => $query->where('proyecto_id', $proyectoId))
                ->find($unidadPrincipalId);

            if ($unidadPrincipal === null) {
                throw new RecursoNoEncontradoException('La unidad principal indicada no existe en el proyecto.');
            }
        }

        try {
            $unidadAccesoria = UnidadAccesoria::create([
                'proyecto_id' => $proyectoId,
                'unidad_principal_id' => $unidadPrincipalId,
                'identificador' => $identificador,
                'tipo' => $tipo->value,
            ]);
        } catch (QueryException $exception) {
            Log::error('No fue posible crear la unidad accesoria.', ['exception' => $exception->getMessage()]);

            throw new RecursoDuplicadoException('Ya existe una unidad accesoria con ese identificador.');
        }

        return $unidadAccesoria->toEntity();
    }

    public function asignarUnidadAccesoria(string $unidadAccesoriaId, ?string $unidadPrincipalId): UnidadAccesoriaEntity
    {
        $unidadAccesoria = UnidadAccesoria::find($unidadAccesoriaId);

        if ($unidadAccesoria === null) {
            throw new RecursoNoEncontradoException('La unidad accesoria no existe.');
        }

        if ($unidadPrincipalId !== null) {
            $unidadPrincipal = UnidadPrincipal::query()
                ->whereHas('agrupacion', static fn ($query) => $query->where('proyecto_id', $unidadAccesoria->proyecto_id))
                ->find($unidadPrincipalId);

            if ($unidadPrincipal === null) {
                throw new RecursoNoEncontradoException('La unidad principal indicada no existe en el proyecto.');
            }
        }

        $unidadAccesoria->update(['unidad_principal_id' => $unidadPrincipalId]);

        return $unidadAccesoria->refresh()->toEntity();
    }

    public function obtenerArbol(string $proyectoId): ArbolInfraestructura
    {
        $proyecto = Proyecto::query()->find($proyectoId);

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('El proyecto no existe.');
        }

        $agrupaciones = $proyecto->agrupaciones()
            ->with('unidadesPrincipales.unidadesAccesorias')
            ->orderBy('nombre')
            ->get()
            ->map(static function (Agrupacion $agrupacion): ArbolAgrupacion {
                $unidades = $agrupacion->unidadesPrincipales
                    ->sortBy('identificador')
                    ->values()
                    ->map(static function (UnidadPrincipal $unidad): ArbolUnidadPrincipal {
                        $accesorias = $unidad->unidadesAccesorias
                            ->sortBy('tipo')
                            ->values()
                            ->map(static fn (UnidadAccesoria $accesoria) => $accesoria->toEntity())
                            ->all();

                        return new ArbolUnidadPrincipal($unidad->toEntity(), $accesorias);
                    })
                    ->all();

                return new ArbolAgrupacion($agrupacion->toEntity(), $unidades);
            })
            ->all();

        return new ArbolInfraestructura($proyecto->toEntity(), $agrupaciones);
    }
}
