<?php

namespace App\Models;

use Database\Factories\UnidadPrincipalFactory;
use Features\Infraestructura\Domain\Entities\UnidadPrincipal as UnidadPrincipalEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadPrincipal extends Model
{
    /** @use HasFactory<UnidadPrincipalFactory> */
    use HasFactory;

    protected $table = 'unidades_principales';

    protected $fillable = ['agrupacion_id', 'identificador'];

    public function agrupacion(): BelongsTo
    {
        return $this->belongsTo(Agrupacion::class);
    }

    public function unidadesAccesorias(): HasMany
    {
        return $this->hasMany(UnidadAccesoria::class);
    }

    public function toEntity(): UnidadPrincipalEntity
    {
        return new UnidadPrincipalEntity(
            id: (string) $this->getKey(),
            agrupacionId: (string) $this->agrupacion_id,
            identificador: $this->identificador,
        );
    }
}
