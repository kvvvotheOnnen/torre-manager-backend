<?php

namespace App\Models;

use Database\Factories\FavoritoMarketplaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FavoritoMarketplace extends Model
{
    /** @use HasFactory<FavoritoMarketplaceFactory> */
    use HasFactory;

    protected $table = 'favoritos_marketplace';

    protected $fillable = [
        'publicacion_id',
        'usuario_id',
    ];

    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(PublicacionMarketplace::class, 'publicacion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
