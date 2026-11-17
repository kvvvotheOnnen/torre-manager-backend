<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class TelefonoValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('El teléfono debe ser un texto válido.');

            return;
        }

        $normalizado = (string) preg_replace('/[\s\-().]/', '', $value);

        if (! preg_match('/^\+?\d{8,15}$/', $normalizado)) {
            $fail('El teléfono debe contener entre 8 y 15 dígitos, con prefijo opcional.');
        }
    }
}
