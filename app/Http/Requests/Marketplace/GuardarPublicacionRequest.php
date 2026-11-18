<?php

namespace App\Http\Requests\Marketplace;

use Features\Marketplace\Domain\Entities\CategoriaProducto;
use Features\Marketplace\Domain\Entities\CondicionProducto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GuardarPublicacionRequest extends FormRequest
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
            'titulo' => ['required', 'string', 'max:120'],
            'descripcion' => ['required', 'string', 'max:1000'],
            'precio' => ['required', 'integer', 'min:0', 'max:999999999'],
            'categoria' => ['required', Rule::enum(CategoriaProducto::class)],
            'condicion' => ['required', Rule::enum(CondicionProducto::class)],
            'foto' => ['nullable', 'image', 'max:'.(int) config('archivos.max_kb', 5120)],
        ];
    }
}
