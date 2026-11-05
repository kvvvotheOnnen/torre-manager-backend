<?php

namespace App\Models;

use Database\Factories\InstalacionExtraFactory;
use Features\Superadmin\Domain\Entities\InstalacionExtra as InstalacionExtraEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalacionExtra extends Model
{
    /** @use HasFactory<InstalacionExtraFactory> */
    use HasFactory;

    protected $table = 'instalaciones_extra';

    protected $fillable = ['proyecto_id', 'nombre', 'capacidad', 'descripcion', 'atributos'];

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'atributos' => 'array',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function toEntity(): InstalacionExtraEntity
    {
        return new InstalacionExtraEntity(
            id: (string) $this->getKey(),
            proyectoId: (string) $this->proyecto_id,
            nombre: $this->nombre,
            capacidad: (int) $this->capacidad,
            descripcion: $this->descripcion,
            atributos: $this->atributos ?? [],
        );
    }
}
