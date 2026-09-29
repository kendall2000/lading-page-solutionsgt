<?php

namespace App\Services;

use App\Events\MensajeEnviado;
use App\Models\Conversacion;
use App\Models\MensajeChat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Guarda los mensajes del chat y los transmite en tiempo real. */
class Chat
{
    public const MAXIMO = 2000;

    /**
     * Guarda el mensaje (MySQL) y lo transmite por Reverb a los demás («toOthers»:
     * no le llega de vuelta a quien lo envió). Si Reverb no responde, el mensaje ya
     * quedó guardado y el otro lado lo recibe con la consulta periódica.
     */
    public function enviar(Conversacion $conversacion, string $autor, string $cuerpo, ?User $usuario = null): MensajeChat
    {
        $mensaje = DB::transaction(function () use ($conversacion, $autor, $cuerpo, $usuario) {
            $mensaje = $conversacion->mensajes()->create([
                'autor' => $autor,
                'user_id' => $usuario?->id,
                'cuerpo' => mb_substr(trim($cuerpo), 0, self::MAXIMO),
            ]);
            $conversacion->forceFill([
                'ultimo_mensaje_en' => $mensaje->created_at,
                'estado' => 'abierta',
                'atendida_por' => $usuario?->id ?? $conversacion->atendida_por,
            ]);
            // Lo que escribe uno queda «no leído» para el otro.
            $autor === 'visitante' ? $conversacion->no_leidos_admin++ : $conversacion->no_leidos_visitante++;
            $conversacion->save();

            return $mensaje->setRelation('conversacion', $conversacion)->setRelation('usuario', $usuario);
        });

        // Sin «return»: broadcast() envía al destruirse, y así un fallo de Reverb queda dentro del rescue.
        rescue(function () use ($mensaje) {
            broadcast(new MensajeEnviado($mensaje))->toOthers();
        }, null, true);

        return $mensaje;
    }

    /** @return array<int, array<string, mixed>> */
    public function historial(Conversacion $conversacion, int $despuesDe = 0, int $limite = 200): array
    {
        return $conversacion->mensajes()->with('usuario')->where('id', '>', $despuesDe)
            ->limit($limite)->get()->map->paraCliente()->all();
    }

    /** Configuración pública para Laravel Echo en el navegador (nunca incluye el secreto). */
    public static function configEcho(): array
    {
        $publico = config('broadcasting.connections.reverb.publico');

        return [
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => $publico['host'] ?? null,
            'port' => $publico['port'] ?? 443,
            'tls' => ($publico['scheme'] ?? 'https') === 'https',
            'activo' => config('broadcasting.default') === 'reverb' && filled(config('broadcasting.connections.reverb.key')),
        ];
    }
}
