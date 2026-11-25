<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudResidenteMascota extends Model
{
    protected $table = 'solicitud_residente_mascotas';

    protected $fillable = ['solicitud_id', 'nombre', 'especie', 'raza', 'microchip', 'foto_url'];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudResidente::class, 'solicitud_id');
    }
}
