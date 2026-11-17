<?php

namespace App\Http\Requests\SolicitudesResidente;

use Illuminate\Foundation\Http\FormRequest;

class RechazarSolicitudRequest extends FormRequest
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
            'motivo' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
