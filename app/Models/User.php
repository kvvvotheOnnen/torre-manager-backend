<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Features\Auth\Domain\Entities\Rol;
use Features\Auth\Domain\Entities\Usuario;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['nombre', 'email', 'password', 'rol'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

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
        );
    }
}
