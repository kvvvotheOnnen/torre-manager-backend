<?php

namespace App\Http\Requests\SolicitudesResidente;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'proyecto_id' => ['required', 'integer', 'exists:proyectos,id'],
            'unidad_principal_id' => ['required', 'integer', 'exists:unidades_principales,id'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],

            'personas' => ['required', 'array', 'min:1', 'max:6'],
            'personas.*.nombre' => ['required', 'string', 'min:2', 'max:150'],
            'personas.*.rut' => ['required', 'string', 'max:12'],
            'personas.*.email' => ['required', 'string', 'email', 'max:255'],
            'personas.*.telefono' => ['required', 'string', 'max:20'],
            'personas.*.telefono_emergencia' => ['required', 'string', 'max:20'],
            'personas.*.nombre_emergencia' => ['required', 'string', 'max:255'],
            'personas.*.tipo_vinculo' => ['required', 'string', 'in:propietario,arrendatario,residente'],
            'personas.*.es_principal' => ['required', 'boolean'],

            'mascotas' => ['sometimes', 'array', 'max:20'],
            'mascotas.*.nombre' => ['required_with:mascotas', 'string', 'max:100'],
            'mascotas.*.especie' => ['required_with:mascotas', 'string', 'in:canino,felino,pequeno_mamifero,ave_domestica,otro'],
            'mascotas.*.raza' => ['required_with:mascotas', 'string', 'max:100'],
            'mascotas.*.microchip' => ['nullable', 'string', 'digits:15'],
            'mascotas.*.foto_url' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'personas.max' => 'Puedes registrar como máximo 6 residentes.',
            'mascotas.*.microchip.digits' => 'El microchip debe tener 15 dígitos.',
        ];
    }
}
