<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Features\Auth\Domain\Entities\Rol;
use Features\Auth\Domain\Entities\Usuario;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['nombre', 'email', 'password', 'rol', 'proyecto_id', 'rut', 'telefono', 'telefono_emergencia', 'nombre_emergencia'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_usuario', 'usuario_id', 'proyecto_id')
            ->using(ProyectoUsuarioPivot::class)
            ->withPivot(['rol', 'estado'])
            ->withTimestamps()
            ->wherePivot('estado', 'activo');
    }

    public function membresias(): HasMany
    {
        return $this->hasMany(ProyectoUsuario::class, 'usuario_id');
    }

    public function vinculosUnidad(): HasMany
    {
        return $this->hasMany(UnidadUsuario::class, 'usuario_id');
    }

    public function membresiaEn(int|string $proyectoId): ?ProyectoUsuario
    {
        return $this->membresias()
            ->where('proyecto_id', $proyectoId)
            ->where('estado', 'activo')
            ->first();
    }

    public function rolEnProyecto(int|string $proyectoId): ?Rol
    {
        $membresia = $this->membresiaEn($proyectoId);

        return $membresia !== null ? Rol::tryFrom($membresia->rol) : null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function toEntity(): Usuario
    {
        return new Usuario(
            id: (string) $this->getKey(),
            nombre: $this->nombre,
            correo: $this->email,
            rol: Rol::from($this->rol),
            rut: $this->rut,
            telefono: $this->telefono,
            telefonoEmergencia: $this->telefono_emergencia,
            nombreEmergencia: $this->nombre_emergencia,
        );
    }
}
