<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TrabajadorServicio extends Model
{
    protected $table = 'trabajadores_servicio';

    protected $fillable = [
        'proyecto_id',
        'nombre',
        'nombre_empresa',
        'telefono',
        'tipo_servicio',
        'descripcion',
        'foto_path',
        'creado_por',
    ];
}
