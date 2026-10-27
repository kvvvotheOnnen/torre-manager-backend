<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarProyectoRequest extends FormRequest
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
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'modulos' => ['required', 'array', 'min:1'],
            'modulos.*' => ['string', 'in:infraestructura,paqueteria,quincho,gastos_comunes,control_acceso'],

            'instalaciones' => ['nullable', 'array'],
            'instalaciones.*.nombre' => ['required_with:instalaciones', 'string', 'max:255', 'distinct'],
            'instalaciones.*.tipo' => ['nullable', 'string', 'max:100'],
            'instalaciones.*.atributos' => ['nullable', 'array'],

            'admin_ids' => ['required', 'array', 'min:1'],
            'admin_ids.*' => ['integer'],
        ];
    }
}
