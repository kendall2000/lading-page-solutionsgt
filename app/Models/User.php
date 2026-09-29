<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use App\Services\Correos;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    use RegistraCambios;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'ultimo_acceso' => 'datetime',
        ];
    }

    /**
     * «¿Olvidaste tu contraseña?» con la plantilla «recuperar_contrasena» y el
     * servidor de Panel → Correos. Si no se puede enviar, queda en la bitácora.
     */
    public function sendPasswordResetNotification($token): void
    {
        app(Correos::class)->enviar('recuperar_contrasena', $this->email, [
            'nombre' => $this->name,
            'enlace' => url(route('password.reset', ['token' => $token, 'email' => $this->getEmailForPasswordReset()], false)),
            'minutos' => (string) config('auth.passwords.users.expire', 60),
        ], $this->id);
    }
}
