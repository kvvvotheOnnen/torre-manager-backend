<?php

namespace App\Models;

use Database\Factories\PaqueteFactory;
use Features\Paqueteria\Domain\Entities\Paquete as PaqueteEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        return $this->foto_path !== null
            ? Storage::disk('public')->url($this->foto_path)
            : null;
    }

    public function toEntity(): PaqueteEntity
    {
        return new PaqueteEntity(
            id: (string) $this->getKey(),
            proyectoId: (string) $this->proyecto_id,
            unidadPrincipalId: $this->unidad_principal_id !== null ? (string) $this->unidad_principal_id : null,
            residenteId: $this->residente_id !== null ? (string) $this->residente_id : null,
            unidadIdentificador: $this->unidadPrincipal?->identificador,
            descripcion: $this->descripcion,
            remitente: $this->remitente,
            fotoPath: $this->foto_path,
            fechaRecepcion: $this->fecha_recepcion?->toDateTimeString() ?? '',
            estado: $this->estado,
            fechaRetiro: $this->fecha_retiro?->toDateTimeString(),
            retiradoPorNombre: $this->retirado_por_nombre,
        );
    }
}
