<?php

namespace App\Models;

use Database\Factories\PublicacionMarketplaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PublicacionMarketplace extends Model
{
    /** @use HasFactory<PublicacionMarketplaceFactory> */
    use HasFactory;

    protected $table = 'publicaciones_marketplace';

    protected $fillable = [
        'proyecto_id',
        'usuario_id',
        'titulo',
        'descripcion',
        'precio',
        'categoria',
        'condicion',
        'estado',
        'foto_path',
        'vendido_en',
        'motivo_eliminacion',
        'eliminado_por',
        'eliminado_por_rol',
        'eliminado_en',
        'motivo_rechazo',
        'rechazado_en',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'integer',
            'vendido_en' => 'datetime',
            'eliminado_en' => 'datetime',
            'rechazado_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function eliminadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }

    public function favoritos(): HasMany
    {
        return $this->hasMany(FavoritoMarketplace::class, 'publicacion_id');
    }
}
