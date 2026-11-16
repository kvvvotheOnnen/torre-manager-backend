<?php

namespace App\Models;

use Features\Acceso\Domain\Entities\TipoVinculo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnidadUsuario extends Model
{
    use HasFactory;

    protected $table = 'unidad_usuario';

    protected $fillable = [
        'unidad_principal_id',
        'usuario_id',
        'tipo_vinculo',
        'fecha_inicio',
        'fecha_fin',
        'es_actual',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'es_actual' => 'boolean',
        ];
    }

    public function unidadPrincipal(): BelongsTo
    {
        return $this->belongsTo(UnidadPrincipal::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function tipo(): TipoVinculo
    {
        return TipoVinculo::from($this->tipo_vinculo);
    }
}
