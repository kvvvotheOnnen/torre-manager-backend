<?php

namespace App\Models;

use Database\Factories\UnidadAccesoriaFactory;
use Features\Infraestructura\Domain\Entities\TipoUnidadAccesoria;
use Features\Infraestructura\Domain\Entities\UnidadAccesoria as UnidadAccesoriaEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnidadAccesoria extends Model
{
    /** @use HasFactory<UnidadAccesoriaFactory> */
    use HasFactory;

    protected $table = 'unidades_accesorias';

    protected $fillable = ['proyecto_id', 'unidad_principal_id', 'identificador', 'tipo'];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function unidadPrincipal(): BelongsTo
    {
        return $this->belongsTo(UnidadPrincipal::class);
    }

    public function toEntity(): UnidadAccesoriaEntity
    {
        return new UnidadAccesoriaEntity(
            id: (string) $this->getKey(),
            proyectoId: (string) $this->proyecto_id,
            identificador: $this->identificador,
            tipo: TipoUnidadAccesoria::from($this->tipo),
            unidadPrincipalId: $this->unidad_principal_id !== null ? (string) $this->unidad_principal_id : null,
        );
    }
}
