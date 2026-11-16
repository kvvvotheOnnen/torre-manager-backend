<?php

namespace App\Models;

use Features\Auth\Domain\Entities\Rol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyectoUsuario extends Model
{
    use HasFactory;

    protected $table = 'proyecto_usuario';

    protected $fillable = [
        'proyecto_id',
        'usuario_id',
        'rol',
        'estado',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $membresia): void {
            if (($membresia->rol === null || $membresia->rol === '') && $membresia->usuario_id !== null) {
                $rol = User::query()->whereKey($membresia->usuario_id)->value('rol');
                $membresia->rol = $rol ?? 'residente';
            }

            if ($membresia->estado === null || $membresia->estado === '') {
                $membresia->estado = 'activo';
            }
        });
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function rolEnum(): Rol
    {
        return Rol::from($this->rol);
    }

    public function estaActiva(): bool
    {
        return $this->estado === 'activo';
    }
}
