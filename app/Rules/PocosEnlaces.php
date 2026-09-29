<?php

namespace App\Rules;

use App\Support\Antispam;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Rechaza textos con más enlaces de los permitidos (0 = ninguno, p. ej. en el nombre). */
class PocosEnlaces implements ValidationRule
{
    public function __construct(private int $maximo = Antispam::MAXIMO_ENLACES) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Antispam::enlaces(is_string($value) ? $value : null) <= $this->maximo) {
            return;
        }
        $fail($this->maximo === 0
            ? 'El campo :attribute no puede llevar enlaces.'
            : "El :attribute tiene demasiados enlaces (máximo {$this->maximo}).");
    }
}
