<?php

namespace App\Models;

use Database\Factories\PaqueteFactory;
use Features\Archivos\Domain\Contracts\AlmacenArchivos;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paquete extends Model
{
    /** @use HasFactory<PaqueteFactory> */
    use HasFactory;

    protected $table = 'paquetes';

    protected $fillable = [
        'proyecto_id',
        'unidad_principal_id',
        'residente_id',
        'descripcion',
        'remitente',
        'foto_path',
        'fecha_recepcion',
        'estado',
        'fecha_retiro',
        'retirado_por_nombre',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'datetime',
            'fecha_retiro' => 'datetime',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function unidadPrincipal(): BelongsTo
    {
        return $this->belongsTo(UnidadPrincipal::class);
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'residente_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function fotoUrl(): ?string
    {
        return app(AlmacenArchivos::class)->url($this->foto_path);
    }
}
