<?php

namespace App\Http\Requests\SolicitudesResidente;

use App\Rules\RutChileno;
use App\Rules\TelefonoValido;
use Features\Auth\Domain\ValueObjects\Rut;
use Illuminate\Foundation\Http\FormRequest;

class CrearSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $personas = $this->input('personas');

        if (! is_array($personas)) {
            return;
        }

        foreach ($personas as $indice => $persona) {
            if (! is_array($persona)) {
                continue;
            }

            if (isset($persona['rut']) && is_string($persona['rut']) && Rut::esValido($persona['rut'])) {
                $personas[$indice]['rut'] = Rut::desde($persona['rut'])->valor();
            }

            if (isset($persona['email']) && is_string($persona['email'])) {
                $personas[$indice]['email'] = mb_strtolower(trim($persona['email']));
            }
        }

        $this->merge(['personas' => $personas]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'proyecto_id' => ['required', 'integer', 'exists:proyectos,id'],
            'unidad_principal_id' => ['required', 'integer', 'exists:unidades_principales,id'],
            'tipo_solicitud' => ['required', 'string', 'in:propietario,arrendatario'],
            'origen' => ['nullable', 'string', 'in:autoregistro,conserje,administrador,importacion'],
            'datos_extra' => ['nullable', 'array'],
            'personas' => ['required', 'array', 'min:1', 'max:6'],
            'personas.*.nombre' => ['required', 'string', 'max:255'],
            'personas.*.rut' => ['required', 'string', new RutChileno],
            'personas.*.email' => ['required', 'string', 'email', 'max:255', 'distinct'],
            'personas.*.telefono' => ['required', 'string', new TelefonoValido],
            'personas.*.telefono_emergencia' => ['required', 'string', new TelefonoValido],
            'personas.*.nombre_emergencia' => ['required', 'string', 'max:255'],
            'personas.*.es_principal' => ['sometimes', 'boolean'],
        ];
    }
}
