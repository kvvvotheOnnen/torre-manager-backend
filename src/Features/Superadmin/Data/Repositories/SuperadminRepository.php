<?php

namespace Features\Superadmin\Data\Repositories;

use App\Models\InstalacionExtra;
use App\Models\Proyecto;
use App\Models\ProyectoUsuario;
use App\Models\UnidadPrincipal;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Features\Dashboard\Domain\Entities\ModuloGeneral;
use Features\Dashboard\Domain\Entities\ModuloSistema;
use Features\Superadmin\Domain\Entities\Administrador;
use Features\Superadmin\Domain\Entities\EstadoProyecto;
use Features\Superadmin\Domain\Entities\ProyectoAdministrador;
use Features\Superadmin\Domain\Entities\ProyectoDetalle;
use Features\Superadmin\Domain\Entities\ResumenProyecto;
use Features\Superadmin\Domain\Entities\TipoCondominio;
use Features\Superadmin\Domain\Exceptions\EmailDuplicadoException;
use Features\Superadmin\Domain\Exceptions\RecursoNoEncontradoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class SuperadminRepository implements SuperadminRepositoryInterface
{
    public function listarProyectosConConteos(): array
    {
        return Proyecto::query()
            ->where('estado', EstadoProyecto::Completo->value)
            ->withCount(['agrupaciones', 'unidadesPrincipales', 'unidadesAccesorias'])
            ->orderBy('nombre')
            ->get()
            ->map(static fn (Proyecto $proyecto) => new ResumenProyecto(
                id: (string) $proyecto->getKey(),
                nombre: $proyecto->nombre,
                descripcion: $proyecto->descripcion,
                agrupaciones: (int) $proyecto->agrupaciones_count,
                unidadesPrincipales: (int) $proyecto->unidades_principales_count,
                unidadesAccesorias: (int) $proyecto->unidades_accesorias_count,
            ))
            ->all();
    }

    public function listarAdministradores(): array
    {
        return User::query()
            ->where('rol', Rol::Administrador->value)
            ->with(['proyectos' => fn ($query) => $query->select('proyectos.id', 'proyectos.nombre')])
            ->orderBy('nombre')
            ->get()
            ->map(fn (User $user) => $this->toAdministrador($user))
            ->all();
    }

    private function toAdministrador(User $user): Administrador
    {
        $proyectos = $user->relationLoaded('proyectos')
            ? $user->proyectos
                ->map(static fn (Proyecto $proyecto) => new ProyectoAdministrador(
                    id: (string) $proyecto->getKey(),
                    nombre: (string) $proyecto->nombre,
                ))
                ->all()
            : [];

        return new Administrador(
            id: (string) $user->getKey(),
            nombre: $user->nombre,
            correo: $user->email,
            rol: Rol::Administrador,
            proyectos: $proyectos,
        );
    }

    public function crearAdministrador(
        string $nombre,
        string $correo,
        string $password,
        ?array $proyectosIds = null,
    ): Administrador {
        $proyectosIds = array_values(array_unique(array_map(
            static fn ($id) => (string) $id,
            $proyectosIds ?? [],
        )));

        return DB::transaction(function () use ($nombre, $correo, $password, $proyectosIds) {
            if ($proyectosIds !== []) {
                $encontrados = Proyecto::query()
                    ->whereIn('id', $proyectosIds)
                    ->pluck('id')
                    ->map(static fn ($id) => (string) $id)
                    ->all();

                if (count($encontrados) !== count($proyectosIds)) {
                    throw new RecursoNoEncontradoException('Uno o más proyectos no existen.');
                }
            }

            try {
                $user = User::create([
                    'nombre' => $nombre,
                    'email' => $correo,
                    'password' => Hash::make($password),
                    'rol' => Rol::Administrador->value,
                    'proyecto_id' => null,
                ]);
            } catch (QueryException $exception) {
                Log::error('No fue posible crear el administrador.', ['exception' => $exception->getMessage()]);

                throw new EmailDuplicadoException('Ya existe un usuario con ese correo electrónico.');
            }

            foreach ($proyectosIds as $proyectoId) {
                $user->proyectos()->attach($proyectoId, [
                    'rol' => Rol::Administrador->value,
                    'estado' => 'activo',
                ]);
            }

            $user->load(['proyectos' => fn ($query) => $query->select('proyectos.id', 'proyectos.nombre')]);

            return $this->toAdministrador($user);
        });
    }

    public function crearProyectoConAdministradores(
        string $nombre,
        ?string $descripcion,
        TipoCondominio $tipoCondominio,
        array $modulos,
        array $agrupaciones,
        array $instalaciones,
        array $administradoresIds,
        array $accesorias,
    ): ProyectoDetalle {
        $administradores = User::query()
            ->whereIn('id', $administradoresIds)
            ->where('rol', Rol::Administrador->value)
            ->get();

        if ($administradores->count() !== count($administradoresIds)) {
            throw new RecursoNoEncontradoException('Uno o más administradores no existen o no tienen el rol correcto.');
        }

        $proyecto = DB::transaction(function () use (
            $nombre,
            $descripcion,
            $tipoCondominio,
            $modulos,
            $agrupaciones,
            $instalaciones,
            $administradores,
            $accesorias,
        ) {
            $proyecto = Proyecto::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'tipo_condominio' => $tipoCondominio->value,
                'modulos' => array_map(static fn (ModuloSistema $modulo) => $modulo->value, $modulos),
                'estado' => EstadoProyecto::Completo->value,
            ]);

            foreach ($agrupaciones as $agrupacion) {
                $creada = $proyecto->agrupaciones()->create([
                    'nombre' => $agrupacion['nombre'],
                    'tipo' => $agrupacion['tipo']->value,
                ]);

                $creada->unidadesPrincipales()->createMany(
                    array_map(
                        static fn (string $identificador) => ['identificador' => $identificador],
                        $agrupacion['identificadores'],
                    ),
                );
            }

            foreach ($instalaciones as $instalacion) {
                $proyecto->instalacionesExtra()->create([
                    'nombre' => $instalacion['nombre'],
                    'capacidad' => $instalacion['capacidad'],
                    'descripcion' => $instalacion['descripcion'] ?? null,
                    'atributos' => $instalacion['atributos'] ?? null,
                ]);
            }

            foreach ($accesorias as $grupo) {
                $proyecto->unidadesAccesorias()->createMany(
                    array_map(
                        static fn (string $identificador) => [
                            'identificador' => $identificador,
                            'tipo' => $grupo['tipo']->value,
                            'asignacion' => null,
                            'unidad_principal_id' => null,
                        ],
                        $grupo['identificadores'],
                    ),
                );
            }

            $this->sincronizarAdministradores($proyecto, $administradores->modelKeys());

            return $proyecto;
        });

        return $this->toProyectoDetalle($proyecto->load('instalacionesExtra', 'administradores'));
    }

    public function buscarProyecto(string $id): ProyectoDetalle
    {
        $proyecto = Proyecto::query()
            ->with('instalacionesExtra', 'administradores')
            ->find($id);

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('El proyecto no existe.');
        }

        return $this->toProyectoDetalle($proyecto);
    }

    public function actualizarProyecto(
        string $id,
        string $nombre,
        ?string $descripcion,
        array $modulos,
        array $modulosDeshabilitados,
        array $instalaciones,
        array $administradoresIds,
    ): ProyectoDetalle {
        $proyecto = Proyecto::query()->find($id);

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('El proyecto no existe.');
        }

        $administradores = User::query()
            ->whereIn('id', $administradoresIds)
            ->where('rol', Rol::Administrador->value)
            ->get();

        if ($administradores->count() !== count($administradoresIds)) {
            throw new RecursoNoEncontradoException('Uno o más administradores no existen o no tienen el rol correcto.');
        }

        DB::transaction(function () use (
            $proyecto,
            $nombre,
            $descripcion,
            $modulos,
            $modulosDeshabilitados,
            $instalaciones,
            $administradores,
        ) {
            $proyecto->update([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'modulos' => array_map(static fn (ModuloSistema $modulo) => $modulo->value, $modulos),
                'modulos_deshabilitados' => array_values($modulosDeshabilitados),
            ]);

            $existentes = $proyecto->instalacionesExtra()->get()->keyBy('nombre');
            $nombresRecibidos = [];

            foreach ($instalaciones as $instalacion) {
                $nombresRecibidos[] = $instalacion['nombre'];
                $existente = $existentes->get($instalacion['nombre']);

                if ($existente !== null) {
                    $existente->update([
                        'capacidad' => $instalacion['capacidad'],
                        'descripcion' => $instalacion['descripcion'] ?? null,
                    ]);

                    continue;
                }

                $proyecto->instalacionesExtra()->create([
                    'nombre' => $instalacion['nombre'],
                    'capacidad' => $instalacion['capacidad'],
                    'descripcion' => $instalacion['descripcion'] ?? null,
                    'atributos' => $instalacion['atributos'] ?? null,
                ]);
            }

            $proyecto->instalacionesExtra()
                ->whereNotIn('nombre', $nombresRecibidos)
                ->delete();

            $this->sincronizarAdministradores($proyecto, $administradores->modelKeys());
        });

        return $this->toProyectoDetalle(
            $proyecto->refresh()->load('instalacionesExtra', 'administradores'),
        );
    }

    public function iniciarProyectoBorrador(string $nombre, ?string $descripcion): ProyectoDetalle
    {
        $proyecto = Proyecto::create([
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'tipo_condominio' => TipoCondominio::Torres->value,
            'modulos' => [],
            'estado' => EstadoProyecto::Borrador->value,
        ]);

        return $this->toProyectoDetalle(
            $proyecto->load('instalacionesExtra', 'administradores'),
        );
    }

    public function guardarPasoProyecto(
        string $id,
        ?string $nombre,
        ?string $descripcion,
        ?TipoCondominio $tipoCondominio,
        ?array $agrupaciones,
        ?array $clasificaciones,
        ?array $instalaciones,
        ?array $modulos,
        ?array $modulosDeshabilitados,
        ?array $administradoresIds,
        bool $resetEstructura,
    ): ProyectoDetalle {
        $proyecto = Proyecto::query()->find($id);

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('El proyecto no existe.');
        }

        $administradores = null;
        if ($administradoresIds !== null) {
            $administradores = User::query()
                ->whereIn('id', $administradoresIds)
                ->where('rol', Rol::Administrador->value)
                ->get();

            if ($administradores->count() !== count($administradoresIds)) {
                throw new RecursoNoEncontradoException('Uno o más administradores no existen o no tienen el rol correcto.');
            }
        }

        DB::transaction(function () use (
            $proyecto,
            $nombre,
            $descripcion,
            $tipoCondominio,
            $agrupaciones,
            $clasificaciones,
            $instalaciones,
            $modulos,
            $modulosDeshabilitados,
            $administradores,
            $resetEstructura,
        ) {
            $atributos = [];

            if ($nombre !== null) {
                $atributos['nombre'] = $nombre;
            }

            if ($descripcion !== null) {
                $atributos['descripcion'] = $descripcion !== '' ? $descripcion : null;
            }

            if ($tipoCondominio !== null) {
                $atributos['tipo_condominio'] = $tipoCondominio->value;
            }

            if ($modulos !== null) {
                $atributos['modulos'] = array_map(
                    static fn (ModuloSistema $modulo) => $modulo->value,
                    $modulos,
                );
            }

            if ($modulosDeshabilitados !== null) {
                $atributos['modulos_deshabilitados'] = array_values($modulosDeshabilitados);
            }

            if ($atributos !== []) {
                $proyecto->update($atributos);
            }

            if ($resetEstructura || $agrupaciones !== null) {
                $proyecto->agrupaciones()->delete();
            }

            if ($agrupaciones !== null) {
                foreach ($agrupaciones as $agrupacion) {
                    $creada = $proyecto->agrupaciones()->create([
                        'nombre' => $agrupacion['nombre'],
                        'tipo' => $agrupacion['tipo']->value,
                    ]);

                    $creada->unidadesPrincipales()->createMany(
                        array_map(
                            static fn (string $identificador) => ['identificador' => $identificador],
                            $agrupacion['identificadores'],
                        ),
                    );
                }
            }

            if ($agrupaciones !== null || $resetEstructura) {
                $this->sincronizarClasificaciones($proyecto, $clasificaciones ?? []);
            }

            if ($instalaciones !== null) {
                $proyecto->instalacionesExtra()->delete();

                foreach ($instalaciones as $instalacion) {
                    $proyecto->instalacionesExtra()->create([
                        'nombre' => $instalacion['nombre'],
                        'capacidad' => $instalacion['capacidad'],
                        'descripcion' => $instalacion['descripcion'] ?? null,
                        'atributos' => $instalacion['atributos'] ?? null,
                    ]);
                }
            }

            if ($administradores !== null) {
                $this->sincronizarAdministradores($proyecto, $administradores->modelKeys());
            }
        });

        return $this->buscarProyecto($id);
    }

    /**
     * Reemplaza las clasificaciones del proyecto y asigna cada unidad a una sola.
     *
     * @param  array<int, array{nombre: string, unidades: array<int, array{agrupacion: string, identificador: string}>}>  $clasificaciones
     *
     * @throws InvalidArgumentException
     */
    private function sincronizarClasificaciones(Proyecto $proyecto, array $clasificaciones): void
    {
        $agrupaciones = $proyecto->agrupaciones()->pluck('nombre', 'id');

        $unidades = UnidadPrincipal::query()
            ->whereIn('agrupacion_id', $agrupaciones->keys())
            ->get(['id', 'agrupacion_id', 'identificador']);

        $mapa = [];

        foreach ($unidades as $unidad) {
            $nombreAgrupacion = (string) $agrupaciones->get($unidad->agrupacion_id);
            $mapa[$nombreAgrupacion.'|'.$unidad->identificador] = (int) $unidad->getKey();
        }

        foreach ($clasificaciones as $clasificacion) {
            foreach ($clasificacion['unidades'] as $unidad) {
                $clave = $unidad['agrupacion'].'|'.$unidad['identificador'];

                if (! isset($mapa[$clave])) {
                    throw new InvalidArgumentException(
                        "La unidad '{$unidad['identificador']}' de '{$unidad['agrupacion']}' no existe en la estructura del proyecto.",
                    );
                }
            }
        }

        UnidadPrincipal::query()
            ->whereIn('agrupacion_id', $agrupaciones->keys())
            ->update(['clasificacion_id' => null]);

        $proyecto->clasificaciones()->delete();

        foreach ($clasificaciones as $clasificacion) {
            $creada = $proyecto->clasificaciones()->create([
                'nombre' => $clasificacion['nombre'],
            ]);

            $ids = array_map(
                static fn (array $unidad): int => $mapa[$unidad['agrupacion'].'|'.$unidad['identificador']],
                $clasificacion['unidades'],
            );

            if ($ids !== []) {
                UnidadPrincipal::query()
                    ->whereIn('id', $ids)
                    ->update(['clasificacion_id' => $creada->getKey()]);
            }
        }
    }

    public function contarDatosProyecto(string $id): array
    {
        $proyecto = Proyecto::query()->find($id);

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('El proyecto no existe.');
        }

        return [
            'agrupaciones' => $proyecto->agrupaciones()->count(),
            'unidades_principales' => $proyecto->unidadesPrincipales()->count(),
            'administradores' => $proyecto->administradores()->count(),
            'modulos' => count($proyecto->modulos ?? []),
        ];
    }

    public function marcarProyectoCompleto(string $id): ProyectoDetalle
    {
        $proyecto = Proyecto::query()->find($id);

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('El proyecto no existe.');
        }

        $proyecto->update(['estado' => EstadoProyecto::Completo->value]);

        return $this->buscarProyecto($id);
    }

    public function descartarProyectoBorrador(string $id): void
    {
        $proyecto = Proyecto::query()->find($id);

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('El proyecto no existe.');
        }

        if ($proyecto->estado !== EstadoProyecto::Borrador->value) {
            throw new RecursoNoEncontradoException('El proyecto indicado no está en creación.');
        }

        $proyecto->delete();
    }

    public function eliminarProyecto(string $id): void
    {
        $proyecto = Proyecto::query()->find($id);

        if ($proyecto === null) {
            throw new RecursoNoEncontradoException('El proyecto no existe.');
        }

        DB::transaction(static function () use ($proyecto): void {
            $proyecto->delete();
        });
    }

    /** @param int[] $administradoresIds */
    private function sincronizarAdministradores(Proyecto $proyecto, array $administradoresIds): void
    {
        $administradoresIds = array_map('intval', $administradoresIds);

        foreach ($administradoresIds as $usuarioId) {
            ProyectoUsuario::query()->updateOrCreate(
                ['proyecto_id' => $proyecto->getKey(), 'usuario_id' => $usuarioId],
                ['rol' => 'administrador', 'estado' => 'activo'],
            );
        }

        $removidos = ProyectoUsuario::query()
            ->where('proyecto_id', $proyecto->getKey())
            ->where('rol', 'administrador')
            ->whereNotIn('usuario_id', $administradoresIds)
            ->get();

        foreach ($removidos as $membresia) {
            $tieneVinculos = DB::table('unidad_usuario')
                ->where('usuario_id', $membresia->usuario_id)
                ->where('es_actual', true)
                ->whereExists(function ($query) use ($proyecto): void {
                    $query->select(DB::raw(1))
                        ->from('unidades_principales')
                        ->join('agrupaciones', 'agrupaciones.id', '=', 'unidades_principales.agrupacion_id')
                        ->whereColumn('unidades_principales.id', 'unidad_usuario.unidad_principal_id')
                        ->where('agrupaciones.proyecto_id', $proyecto->getKey());
                })
                ->exists();

            $membresia->update([
                'rol' => $tieneVinculos ? 'residente' : $membresia->rol,
                'estado' => $tieneVinculos ? 'activo' : 'inactivo',
            ]);
        }

        // Compatibilidad temporal con la tabla legacy durante la transición.
        DB::table('administrador_proyecto')->where('proyecto_id', $proyecto->getKey())->delete();
        foreach ($administradoresIds as $usuarioId) {
            DB::table('administrador_proyecto')->insertOrIgnore([
                'proyecto_id' => $proyecto->getKey(),
                'usuario_id' => $usuarioId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function listarProyectosDelUsuario(string $usuarioId): array
    {
        $usuario = User::query()->find($usuarioId);

        if ($usuario === null) {
            throw new RecursoNoEncontradoException('El usuario no existe.');
        }

        if ($usuario->rol === Rol::Superadmin->value) {
            $proyectos = Proyecto::query()
                ->where('proyectos.estado', EstadoProyecto::Completo->value)
                ->with('instalacionesExtra', 'administradores')
                ->orderBy('proyectos.nombre')
                ->get();
        } else {
            $proyectos = $usuario->proyectos()
                ->where('proyectos.estado', EstadoProyecto::Completo->value)
                ->with('instalacionesExtra', 'administradores')
                ->orderBy('proyectos.nombre')
                ->get();

            if ($usuario->proyecto_id !== null && ! $proyectos->contains('id', $usuario->proyecto_id)) {
                $proyectoLegado = Proyecto::query()
                    ->whereKey($usuario->proyecto_id)
                    ->where('estado', EstadoProyecto::Completo->value)
                    ->with('instalacionesExtra', 'administradores')
                    ->first();

                if ($proyectoLegado !== null) {
                    $proyectos->push($proyectoLegado);
                    $proyectos = $proyectos->sortBy('nombre')->values();
                }
            }
        }

        return $proyectos->map(fn (Proyecto $proyecto) => $this->toProyectoDetalle($proyecto))->all();
    }

    private function toProyectoDetalle(Proyecto $proyecto): ProyectoDetalle
    {
        return new ProyectoDetalle(
            id: (string) $proyecto->getKey(),
            nombre: $proyecto->nombre,
            descripcion: $proyecto->descripcion,
            tipoCondominio: TipoCondominio::from($proyecto->tipo_condominio ?? 'torres'),
            modulos: array_values(array_filter(array_map(
                static fn (string $modulo) => ModuloSistema::tryFrom($modulo),
                $proyecto->modulos ?? [],
            ))),
            instalacionesExtra: $proyecto->instalacionesExtra
                ->map(static fn (InstalacionExtra $instalacion) => $instalacion->toEntity())
                ->all(),
            administradoresIds: $proyecto->administradores
                ->map(static fn (User $user) => (string) $user->getKey())
                ->all(),
            estado: EstadoProyecto::tryFrom((string) $proyecto->estado) ?? EstadoProyecto::Completo,
            modulosDeshabilitados: array_values(array_filter(
                array_map('strval', $proyecto->modulos_deshabilitados ?? []),
                static fn (string $modulo) => ModuloGeneral::tryFrom($modulo) !== null,
            )),
        );
    }
}
