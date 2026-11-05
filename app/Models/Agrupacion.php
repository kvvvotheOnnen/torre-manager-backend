<?php

namespace App\Models;

use Database\Factories\AgrupacionFactory;
use Features\Infraestructura\Domain\Entities\Agrupacion as AgrupacionEntity;
use Features\Infraestructura\Domain\Entities\TipoAgrupacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agrupacion extends Model
{
    /** @use HasFactory<AgrupacionFactory> */
    use HasFactory;

    protected $table = 'agrupaciones';

    protected $fillable = ['proyecto_id', 'nombre', 'tipo'];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function unidadesPrincipales(): HasMany
    {
        return $this->hasMany(UnidadPrincipal::class);
    }

    public function toEntity(): AgrupacionEntity
    {
        return new AgrupacionEntity(
            id: (string) $this->getKey(),
            proyectoId: (string) $this->proyecto_id,
            nombre: $this->nombre,
            tipo: TipoAgrupacion::from($this->tipo),
        );
    }
}
