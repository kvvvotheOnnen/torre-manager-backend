<?php

namespace App\Http\Requests\Asistente;

use Illuminate\Foundation\Http\FormRequest;

final class ConsultarAsistenteRequest extends FormRequest
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
            'pregunta' => ['required', 'string', 'min:2', 'max:500'],
        ];
    }
}
