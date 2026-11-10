<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RucPeru implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ruc = (string) $value;

        if (! preg_match('/^(10|15|16|17|20)\d{9}$/', $ruc)) {
            $fail('El RUC debe contener 11 dígitos y tener un prefijo peruano válido.');

            return;
        }

        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        foreach ($pesos as $indice => $peso) {
            $suma += ((int) $ruc[$indice]) * $peso;
        }

        $resultado = 11 - ($suma % 11);
        $digito = match ($resultado) {
            10 => 0,
            11 => 1,
            default => $resultado,
        };

        if ($digito !== (int) $ruc[10]) {
            $fail('El dígito verificador del RUC no es válido.');
        }
    }
}
