<?php

namespace App\Http\Requests\Asistente;

use Illuminate\Foundation\Http\FormRequest;

final class SubirDocumentoRequest extends FormRequest
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
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'proyecto_id' => ['nullable', 'integer', 'exists:proyectos,id'],
        ];
    }
}
