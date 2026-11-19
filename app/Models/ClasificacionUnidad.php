<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClasificacionUnidad extends Model
{
    protected $table = 'clasificaciones_unidad';

    protected $fillable = ['proyecto_id', 'nombre'];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function unidadesPrincipales(): HasMany
    {
        return $this->hasMany(UnidadPrincipal::class, 'clasificacion_id');
    }
}
