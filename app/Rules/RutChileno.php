<?php

namespace App\Rules;

use Closure;
use Features\Auth\Domain\Exceptions\RutInvalidoException;
use Features\Auth\Domain\ValueObjects\Rut;
use Illuminate\Contracts\Validation\ValidationRule;

final class RutChileno implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('El RUT debe ser un texto válido.');

            return;
        }

        try {
            Rut::desde($value);
        } catch (RutInvalidoException $exception) {
            $fail($exception->getMessage());
        }
    }
}
