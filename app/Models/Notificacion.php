<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'proyecto_id',
        'tipo',
        'titulo',
        'mensaje',
        'datos',
        'leida',
        'leida_at',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'leida' => 'boolean',
            'leida_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
