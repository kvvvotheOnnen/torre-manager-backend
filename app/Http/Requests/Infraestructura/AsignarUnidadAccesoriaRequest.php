<?php

namespace App\Http\Requests\Infraestructura;

use Illuminate\Foundation\Http\FormRequest;

class AsignarUnidadAccesoriaRequest extends FormRequest
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
            'asignacion' => ['nullable', 'string', 'in:departamento,visita,otro'],
            'unidad_principal_id' => ['nullable', 'integer', 'exists:unidades_principales,id'],
        ];
    }
}
