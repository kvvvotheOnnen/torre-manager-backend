<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;

class GuardarPasoProyectoBorradorRequest extends FormRequest
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
            'nombre' => ['sometimes', 'string', 'max:255'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'tipo_condominio' => ['sometimes', 'string', 'in:torres,casas,edificio,mixto'],

            'agrupaciones' => ['sometimes', 'array', 'min:1'],
            'agrupaciones.*.nombre' => ['required_with:agrupaciones', 'string', 'max:255'],
            'agrupaciones.*.tipo' => ['required_with:agrupaciones', 'string', 'in:edificio,torre,bloque,manzana'],
            'agrupaciones.*.prefijo' => ['nullable', 'string', 'max:100'],
            'agrupaciones.*.piso_desde' => ['nullable', 'integer', 'min:0'],
            'agrupaciones.*.piso_hasta' => ['nullable', 'integer', 'min:0'],
            'agrupaciones.*.unidades_por_piso' => ['nullable', 'integer', 'min:1', 'max:100'],
            'agrupaciones.*.cantidad' => ['nullable', 'integer', 'min:1', 'max:500'],
            'agrupaciones.*.pisos_excepcion' => ['sometimes', 'array'],
            'agrupaciones.*.pisos_excepcion.*.piso' => ['required', 'integer', 'min:0'],
            'agrupaciones.*.pisos_excepcion.*.sin_unidades' => ['sometimes', 'boolean'],
            'agrupaciones.*.pisos_excepcion.*.unidades' => ['sometimes', 'array'],
            'agrupaciones.*.pisos_excepcion.*.unidades.*.identificador' => ['nullable', 'string', 'max:255'],
            'agrupaciones.*.pisos_excepcion.*.unidades.*.tipo' => ['required', 'string', 'in:simple,instalacion'],

            'instalaciones' => ['sometimes', 'array'],
            'instalaciones.*.nombre' => ['required_with:instalaciones', 'string', 'max:255'],
            'instalaciones.*.capacidad' => ['required_with:instalaciones', 'integer', 'min:1', 'max:500'],
            'instalaciones.*.descripcion' => ['nullable', 'string', 'max:100'],
            'instalaciones.*.atributos' => ['nullable', 'array'],

            'modulos' => ['sometimes', 'array'],
            'modulos.*' => ['string', 'in:infraestructura,paqueteria,quincho,gastos_comunes,control_acceso'],

            'admin_ids' => ['sometimes', 'array'],
            'admin_ids.*' => ['integer'],

            'reset_estructura' => ['sometimes', 'boolean'],
            'finalizar' => ['sometimes', 'boolean'],
        ];
    }
}
