<?php

namespace App\Http\Requests\Infraestructura;

use Illuminate\Foundation\Http\FormRequest;

class CrearUnidadesAccesoriasLoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', 'string', 'in:estacionamiento,bodega'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:999'],
            'asignacion' => ['nullable', 'string', 'in:departamento,visita,otro'],
            'unidad_principal_id' => ['nullable', 'integer', 'exists:unidades_principales,id'],
        ];
    }
}
