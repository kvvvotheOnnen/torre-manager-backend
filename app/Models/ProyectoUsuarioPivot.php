<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProyectoUsuarioPivot extends Pivot
{
    protected $table = 'proyecto_usuario';

    public $incrementing = true;

    protected $fillable = [
        'proyecto_id',
        'usuario_id',
        'rol',
        'estado',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            if (($pivot->rol === null || $pivot->rol === '') && $pivot->usuario_id !== null) {
                $rol = User::query()->whereKey($pivot->usuario_id)->value('rol');
                $pivot->rol = $rol ?? 'residente';
            }

            if ($pivot->estado === null || $pivot->estado === '') {
                $pivot->estado = 'activo';
            }
        });
    }
}
