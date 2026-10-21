<?php

namespace App\Models;

use Database\Factories\ProyectoFactory;
use Features\Infraestructura\Domain\Entities\Proyecto as ProyectoEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Proyecto extends Model
{
    /** @use HasFactory<ProyectoFactory> */
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion'];

    public function agrupaciones(): HasMany
    {
        return $this->hasMany(Agrupacion::class);
    }

    public function unidadesPrincipales(): HasManyThrough
    {
        return $this->hasManyThrough(UnidadPrincipal::class, Agrupacion::class);
    }

    public function unidadesAccesorias(): HasMany
    {
        return $this->hasMany(UnidadAccesoria::class);
    }

    public function toEntity(): ProyectoEntity
    {
        return new ProyectoEntity(
            id: (string) $this->getKey(),
            nombre: $this->nombre,
            descripcion: $this->descripcion,
        );
    }
}
