<?php

namespace Features\Superadmin\Data\Repositories;

use App\Models\Proyecto;
use App\Models\User;
use Features\Auth\Domain\Entities\Rol;
use Features\Superadmin\Domain\Entities\Administrador;
use Features\Superadmin\Domain\Entities\ResumenProyecto;
use Features\Superadmin\Domain\Exceptions\EmailDuplicadoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;
use Illuminate\Database\QueryException;
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
}
