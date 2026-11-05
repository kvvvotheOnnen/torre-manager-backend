<?php

namespace App\Http\Requests\Infraestructura;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarAgrupacionRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'in:torre,bloque,manzana'],
            'prefijo' => ['nullable', 'string', 'max:50'],
            'piso_desde' => ['required', 'integer', 'min:0'],
            'piso_hasta' => ['required', 'integer', 'gte:piso_desde'],
            'unidades_por_piso' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
