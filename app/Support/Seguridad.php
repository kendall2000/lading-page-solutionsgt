<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Seguridad
{
    /**
     * Cierra las sesiones abiertas del usuario (menos la actual, si se indica).
     * Sin excepción también invalida «Recordar sesión» en todos los dispositivos.
     */
    public static function cerrarSesiones(int $userId, ?string $excepto = null): int
    {
        $cerradas = DB::table('sessions')->where('user_id', $userId)
            ->when($excepto, fn ($q) => $q->where('id', '!=', $excepto))
            ->delete();

        if (! $excepto) {
            DB::table('users')->where('id', $userId)->update(['remember_token' => Str::random(60)]);
        }

        return $cerradas;
    }
}
