<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mascota extends Model
{
    protected $table = 'mascotas';

    protected $fillable = ['unidad_principal_id', 'nombre', 'especie', 'raza', 'microchip', 'foto_url'];

    public function unidadPrincipal(): BelongsTo
    {
        return $this->belongsTo(UnidadPrincipal::class);
    }
}
