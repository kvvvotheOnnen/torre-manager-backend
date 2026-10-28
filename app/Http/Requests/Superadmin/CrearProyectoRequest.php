<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;

class CrearProyectoRequest extends FormRequest
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
            'tipo_condominio' => ['required', 'string', 'in:torres,casas,edificio,mixto'],
            'modulos' => ['required', 'array', 'min:1'],
            'modulos.*' => ['string', 'in:infraestructura,paqueteria,quincho,gastos_comunes,control_acceso'],

            'agrupaciones' => ['required', 'array', 'min:1'],
            'agrupaciones.*.nombre' => ['required', 'string', 'max:255'],
            'agrupaciones.*.tipo' => ['required', 'string', 'in:edificio,torre,bloque,manzana'],
            'agrupaciones.*.prefijo' => ['nullable', 'string', 'max:100'],
            'agrupaciones.*.piso_desde' => ['nullable', 'integer', 'min:0'],
            'agrupaciones.*.piso_hasta' => ['nullable', 'integer', 'min:0'],
            'agrupaciones.*.unidades_por_piso' => ['nullable', 'integer', 'min:1', 'max:100'],
            'agrupaciones.*.cantidad' => ['nullable', 'integer', 'min:1', 'max:500'],

            'instalaciones' => ['nullable', 'array'],
            'instalaciones.*.nombre' => ['required_with:instalaciones', 'string', 'max:255'],
            'instalaciones.*.tipo' => ['nullable', 'string', 'max:100'],
            'instalaciones.*.atributos' => ['nullable', 'array'],

            'estacionamientos' => ['nullable', 'integer', 'min:0', 'max:999'],
            'bodegas' => ['nullable', 'integer', 'min:0', 'max:999'],

            'admin_ids' => ['required', 'array', 'min:1'],
            'admin_ids.*' => ['integer'],
        ];
    }
}
