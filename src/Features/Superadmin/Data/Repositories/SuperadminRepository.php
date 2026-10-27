<?php

namespace Features\Superadmin\Data\Repositories;

use App\Models\Proyecto;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Features\Superadmin\Domain\Entities\Administrador;
use Features\Superadmin\Domain\Entities\InstalacionExtra;
use Features\Superadmin\Domain\Entities\ModuloSistema;
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

final class SuperadminRepository implements SuperadminRepositoryInterface
{
    public function listarProyectosConConteos(): array
    {
        return Proyecto::query()
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
            ->orderBy('nombre')
            ->get()
            ->map(static fn (User $user) => new Administrador(
                id: (string) $user->getKey(),
                nombre: $user->nombre,
                correo: $user->email,
                rol: Rol::Administrador,
            ))
            ->all();
    }

    public function crearAdministrador(string $nombre, string $correo, string $password): Administrador
    {
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

        return new Administrador(
            id: (string) $user->getKey(),
            nombre: $user->nombre,
            correo: $user->email,
            rol: Rol::Administrador,
        );
    }

    public function crearProyectoConAdministradores(
        string $nombre,
        ?string $descripcion,
        TipoCondominio $tipoCondominio,
        array $modulos,
        array $agrupaciones,
        array $instalaciones,
        array $administradoresIds,
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
        ) {
            $proyecto = Proyecto::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'tipo_condominio' => $tipoCondominio->value,
                'modulos' => array_map(static fn (ModuloSistema $modulo) => $modulo->value, $modulos),
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
                    'tipo' => $instalacion['tipo'] ?? null,
                    'atributos' => $instalacion['atributos'] ?? null,
                ]);
            }

            $proyecto->administradores()->sync($administradores->modelKeys());

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
            $instalaciones,
            $administradores,
        ) {
            $proyecto->update([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'modulos' => array_map(static fn (ModuloSistema $modulo) => $modulo->value, $modulos),
            ]);

            $existentes = $proyecto->instalacionesExtra()->get()->keyBy('nombre');
            $nombresRecibidos = [];

            foreach ($instalaciones as $instalacion) {
                $nombresRecibidos[] = $instalacion['nombre'];
                $existente = $existentes->get($instalacion['nombre']);

                if ($existente !== null) {
                    $existente->update(['tipo' => $instalacion['tipo'] ?? null]);

                    continue;
                }

                $proyecto->instalacionesExtra()->create([
                    'nombre' => $instalacion['nombre'],
                    'tipo' => $instalacion['tipo'] ?? null,
                    'atributos' => $instalacion['atributos'] ?? null,
                ]);
            }

            $proyecto->instalacionesExtra()
                ->whereNotIn('nombre', $nombresRecibidos)
                ->delete();

            $proyecto->administradores()->sync($administradores->modelKeys());
        });

        return $this->toProyectoDetalle(
            $proyecto->refresh()->load('instalacionesExtra', 'administradores'),
        );
    }

    public function listarProyectosDelUsuario(string $usuarioId): array
    {
        $usuario = User::query()->find($usuarioId);

        if ($usuario === null) {
            throw new RecursoNoEncontradoException('El usuario no existe.');
        }

        if ($usuario->rol === Rol::Superadmin->value) {
            $proyectos = Proyecto::query()->with('instalacionesExtra', 'administradores')->orderBy('nombre')->get();
        } else {
            $proyectos = $usuario->proyectos()
                ->with('instalacionesExtra', 'administradores')
                ->orderBy('nombre')
                ->get();
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
            modulos: array_map(
                static fn (string $modulo) => ModuloSistema::from($modulo),
                $proyecto->modulos ?? [],
            ),
            instalacionesExtra: $proyecto->instalacionesExtra
                ->map(static fn (\App\Models\InstalacionExtra $instalacion) => $instalacion->toEntity())
                ->all(),
            administradoresIds: $proyecto->administradores
                ->map(static fn (User $user) => (string) $user->getKey())
                ->all(),
        );
    }
}
