<?php

namespace App\Models;

use Database\Factories\ProyectoFactory;
use Features\Infraestructura\Domain\Entities\Proyecto as ProyectoEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Proyecto extends Model
{
    /** @use HasFactory<ProyectoFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_condominio',
        'modulos',
        'modulos_deshabilitados',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'modulos' => 'array',
            'modulos_deshabilitados' => 'array',
        ];
    }

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

    public function instalacionesExtra(): HasMany
    {
        return $this->hasMany(InstalacionExtra::class);
    }

    public function miembros(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proyecto_usuario', 'proyecto_id', 'usuario_id')
            ->using(ProyectoUsuarioPivot::class)
            ->withPivot(['rol', 'estado'])
            ->withTimestamps()
            ->wherePivot('estado', 'activo');
    }

    public function administradores(): BelongsToMany
    {
        return $this->miembros()->wherePivot('rol', 'administrador');
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
