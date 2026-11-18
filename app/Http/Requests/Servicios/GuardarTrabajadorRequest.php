<?php

namespace App\Http\Requests\Servicios;

use Features\Servicios\Domain\Entities\TipoServicio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GuardarTrabajadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'nombre_empresa' => ['nullable', 'string', 'max:120'],
            'telefono' => ['required', 'string', 'max:30'],
            'tipo_servicio' => ['required', Rule::enum(TipoServicio::class)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'foto' => ['nullable', 'image', 'max:'.(int) config('archivos.max_kb', 5120)],
        ];
    }
}
