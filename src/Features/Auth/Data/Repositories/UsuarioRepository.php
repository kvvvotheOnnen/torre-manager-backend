<?php

namespace Features\Auth\Data\Repositories;

use App\Models\User;
use Features\Auth\Domain\Entities\EstadoUsuario;
use Features\Auth\Domain\Entities\Rol;
use Features\Auth\Domain\Entities\Usuario;
use Features\Auth\Domain\Repositories\UsuarioRepositoryInterface;
use Illuminate\Support\Str;

final class UsuarioRepository implements UsuarioRepositoryInterface
{
    public function buscarPorEmail(string $email): ?Usuario
    {
        return User::query()->where('email', $email)->first()?->toEntity();
    }

    public function buscarPorRut(string $rut): ?Usuario
    {
        return User::query()->where('rut', $rut)->first()?->toEntity();
    }

    public function crearPendiente(
        string $proyectoId,
        string $nombre,
        string $email,
        string $rut,
        string $telefono,
        string $telefonoEmergencia,
        string $nombreEmergencia,
        Rol $rol,
    ): Usuario {
        $usuario = User::query()->create([
            'nombre' => $nombre,
            'email' => $email,
            'password' => Str::password(32),
            'rol' => $rol->value,
            'estado' => EstadoUsuario::Pendiente->value,
            'proyecto_id' => $proyectoId,
            'rut' => $rut,
            'telefono' => $telefono,
            'telefono_emergencia' => $telefonoEmergencia,
            'nombre_emergencia' => $nombreEmergencia,
        ]);

        return $usuario->toEntity();
    }

    public function actualizarDatos(
        string $usuarioId,
        string $nombre,
        string $rut,
        string $telefono,
        string $telefonoEmergencia,
        string $nombreEmergencia,
        ?Rol $rol = null,
        ?EstadoUsuario $estado = null,
    ): void {
        $datos = [
            'nombre' => $nombre,
            'rut' => $rut,
            'telefono' => $telefono,
            'telefono_emergencia' => $telefonoEmergencia,
            'nombre_emergencia' => $nombreEmergencia,
        ];

        if ($rol !== null) {
            $datos['rol'] = $rol->value;
        }

        if ($estado !== null) {
            $datos['estado'] = $estado->value;
        }

        User::query()->whereKey($usuarioId)->update($datos);
    }

    public function activar(string $usuarioId): void
    {
        User::query()->whereKey($usuarioId)->update(['estado' => EstadoUsuario::Activo->value]);
    }

    public function marcarRechazadoSiPendiente(string $usuarioId): void
    {
        User::query()
            ->whereKey($usuarioId)
            ->where('estado', EstadoUsuario::Pendiente->value)
            ->update(['estado' => EstadoUsuario::Rechazado->value]);
    }
}
