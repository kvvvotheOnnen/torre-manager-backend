<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudResidentePersona extends Model
{
    protected $table = 'solicitud_residente_personas';

    protected $fillable = [
        'solicitud_id',
        'usuario_id',
        'nombre',
        'rut',
        'email',
        'telefono',
        'telefono_emergencia',
        'nombre_emergencia',
        'tipo_vinculo',
        'es_principal',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudResidente::class, 'solicitud_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
