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

    protected $fillable = ['agrupacion_id', 'identificador', 'residente_id', 'clasificacion_id'];

    public function agrupacion(): BelongsTo
    {
        return $this->belongsTo(Agrupacion::class);
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'residente_id');
    }

    public function clasificacion(): BelongsTo
    {
        return $this->belongsTo(ClasificacionUnidad::class, 'clasificacion_id');
    }

    public function unidadesAccesorias(): HasMany
    {
        return $this->hasMany(UnidadAccesoria::class);
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(UnidadUsuario::class);
    }

    public function propietarioActual(): ?UnidadUsuario
    {
        return $this->vinculos()
            ->where('tipo_vinculo', 'propietario')
            ->where('es_actual', true)
            ->first();
    }

    public function ocupanteActual(): ?UnidadUsuario
    {
        return $this->vinculos()
            ->whereIn('tipo_vinculo', ['arrendatario', 'residente'])
            ->where('es_actual', true)
            ->orderByRaw("case when tipo_vinculo = 'arrendatario' then 0 else 1 end")
            ->first();
    }

    public function toEntity(): UnidadPrincipalEntity
    {
        $propietario = null;
        $ocupante = null;

        if ($this->relationLoaded('vinculos')) {
            $propietario = $this->vinculos
                ->first(fn (UnidadUsuario $v) => $v->tipo_vinculo === 'propietario' && $v->es_actual);
            $ocupante = $this->vinculos
                ->where('es_actual', true)
                ->whereIn('tipo_vinculo', ['arrendatario', 'residente'])
                ->sortBy(fn (UnidadUsuario $v) => $v->tipo_vinculo === 'arrendatario' ? 0 : 1)
                ->first();
        }

        return new UnidadPrincipalEntity(
            id: (string) $this->getKey(),
            agrupacionId: (string) $this->agrupacion_id,
            identificador: $this->identificador,
            propietarioId: $propietario?->usuario_id !== null ? (string) $propietario->usuario_id : null,
            propietarioNombre: $propietario?->usuario?->nombre,
            ocupanteId: $ocupante?->usuario_id !== null ? (string) $ocupante->usuario_id : null,
            ocupanteNombre: $ocupante?->usuario?->nombre,
            ocupanteTipo: $ocupante?->tipo_vinculo,
        );
    }
}
