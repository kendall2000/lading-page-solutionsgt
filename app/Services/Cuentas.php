<?php

namespace App\Services;

use App\Models\Conversacion;
use App\Models\Cuenta;
use App\Models\MensajeContacto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Cuentas de clientes: enlaces de un solo uso (entrar por correo, invitación), confirmación del correo
 * y unión de lo que la persona ya había enviado con ese correo (chats y solicitudes).
 */
class Cuentas
{
    public const MINUTOS_ENLACE = 20;

    public const DIAS_INVITACION = 7;

    public function __construct(private readonly Correos $correos) {}

    /** Crea un token nuevo (anula el anterior) y devuelve el valor en claro para el enlace. */
    public function crearToken(Cuenta $cuenta, string $tipo, \DateTimeInterface $vence): string
    {
        $token = Str::random(64);
        $cuenta->forceFill(['token_hash' => hash('sha256', $token), 'token_tipo' => $tipo, 'token_vence' => $vence])->save();

        return $token;
    }

    /** Cuenta dueña de un token vigente del tipo indicado (sin gastarlo). */
    public function buscarToken(string $token, string $tipo): ?Cuenta
    {
        return Cuenta::query()->where('token_hash', hash('sha256', $token))->where('token_tipo', $tipo)
            ->where('token_vence', '>', now())->where('activa', true)->first();
    }

    /** Gasta el token: ya no sirve otra vez. */
    public function gastarToken(Cuenta $cuenta): void
    {
        $cuenta->forceFill(['token_hash' => null, 'token_tipo' => null, 'token_vence' => null])->save();
    }

    public function enviarEnlace(Cuenta $cuenta): void
    {
        $token = $this->crearToken($cuenta, 'enlace', now()->addMinutes(self::MINUTOS_ENLACE));
        $this->correos->enviar('cuenta_enlace', $cuenta->correo, [
            'nombre' => $cuenta->nombre, 'enlace' => route('cuenta.acceso', $token), 'minutos' => (string) self::MINUTOS_ENLACE,
        ]);
    }

    public function invitar(Cuenta $cuenta): bool
    {
        $token = $this->crearToken($cuenta, 'invitacion', now()->addDays(self::DIAS_INVITACION));
        $cuenta->forceFill(['invitada_en' => now()])->save();

        return $this->correos->enviar('cuenta_invitacion', $cuenta->correo, [
            'nombre' => $cuenta->nombre, 'enlace' => route('cuenta.invitacion', $token), 'dias' => (string) self::DIAS_INVITACION,
        ]);
    }

    public function enviarConfirmacion(Cuenta $cuenta): void
    {
        $this->correos->enviar('cuenta_confirmar', $cuenta->correo, [
            'nombre' => $cuenta->nombre, 'enlace' => $this->enlaceConfirmacion($cuenta),
        ]);
    }

    public function enlaceConfirmacion(Cuenta $cuenta): string
    {
        return URL::temporarySignedRoute('cuenta.verificar', now()->addDay(), ['cuenta' => $cuenta->id, 'hash' => sha1($cuenta->correo)]);
    }

    /**
     * Marca el correo como confirmado y le une lo que antes envió con ese correo sin cuenta
     * (conversaciones del chat y solicitudes). $avisar: aviso «nueva_cuenta» a «Avisos a».
     */
    public function confirmar(Cuenta $cuenta, bool $avisar = false): void
    {
        if ($cuenta->verificada()) {
            return;
        }
        $cuenta->forceFill(['correo_verificado_en' => now()])->save();
        $this->vincular($cuenta);

        if ($avisar) {
            $this->correos->avisar('nueva_cuenta', [
                'nombre' => $cuenta->nombre, 'correo' => $cuenta->correo, 'empresa' => $cuenta->empresa ?: '—',
                'enlace' => route('admin.cuentas.edit', $cuenta),
            ], $cuenta->correo);
        }
    }

    public function vincular(Cuenta $cuenta): void
    {
        $correo = Str::lower($cuenta->correo);
        foreach ([Conversacion::class, MensajeContacto::class] as $modelo) {
            $modelo::query()->whereNull('cuenta_id')->where(DB::raw('LOWER(correo)'), $correo)->update(['cuenta_id' => $cuenta->id]);
        }
    }
}
