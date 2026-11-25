<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudResidente extends Model
{
    protected $table = 'solicitudes_residente';

    protected $fillable = [
        'proyecto_id',
        'unidad_principal_id',
        'tipo_solicitud',
        'origen',
        'estado',
        'motivo_rechazo',
        'datos_extra',
        'solicitado_por_id',
        'decidido_por_id',
        'decidido_at',
    ];

    protected function casts(): array
    {
        return [
            'datos_extra' => 'array',
            'decidido_at' => 'datetime',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function unidadPrincipal(): BelongsTo
    {
        return $this->belongsTo(UnidadPrincipal::class);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(SolicitudResidentePersona::class, 'solicitud_id')
            ->orderByDesc('es_principal')
            ->orderBy('id');
    }

    public function mascotas(): HasMany
    {
        return $this->hasMany(SolicitudResidenteMascota::class, 'solicitud_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por_id');
    }

    public function decididoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por_id');
    }
}
