<?php

namespace App\Http\Requests\Infraestructura;

use Illuminate\Foundation\Http\FormRequest;

class CrearUnidadAccesoriaRequest extends FormRequest
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
            'identificador' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'in:estacionamiento,bodega'],
            'unidad_principal_id' => ['nullable', 'integer', 'exists:unidades_principales,id'],
        ];
    }
}
