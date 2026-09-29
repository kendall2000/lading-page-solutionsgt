<?php

namespace App\Services;

use App\Events\ConversacionCerrada;
use App\Events\MensajeEnviado;
use App\Events\MensajesLeidos;
use App\Models\Conversacion;
use App\Models\MensajeChat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Guarda los mensajes del chat, la lectura y el cierre, y los transmite en tiempo real. */
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
                'atendida_por' => $usuario?->id ?? $conversacion->atendida_por,
            ]);
            // Lo que escribe uno queda «no leído» para el otro.
            if ($autor === 'visitante') {
                $conversacion->no_leidos_admin++;
            } elseif ($autor === 'admin') {
                $conversacion->no_leidos_visitante++;
            }
            $conversacion->save();

            return $mensaje->setRelation('conversacion', $conversacion)->setRelation('usuario', $usuario);
        });

        $this->transmitir(new MensajeEnviado($mensaje));

        return $mensaje;
    }

    /**
     * Marca como leídos los mensajes del OTRO lado y avisa en tiempo real (✓✓ Visto).
     * Solo se llama cuando quien lee tiene la conversación a la vista.
     *
     * @param  string  $lector  «admin» o «visitante»
     */
    public function marcarLeidos(Conversacion $conversacion, string $lector): void
    {
        $delOtro = $lector === 'admin' ? 'visitante' : 'admin';
        $pendientes = MensajeChat::query()->where('conversacion_id', $conversacion->id)->where('autor', $delOtro)->whereNull('leido_en');
        $hasta = (int) (clone $pendientes)->max('id');

        if ($hasta > 0) {
            $pendientes->update(['leido_en' => now()]);
            $this->transmitir(new MensajesLeidos($conversacion->id, $lector, $hasta));
        }
        $contador = $lector === 'admin' ? 'no_leidos_admin' : 'no_leidos_visitante';
        if ($conversacion->{$contador} !== 0) {
            $conversacion->forceFill([$contador => 0])->save();
        }
    }

    /**
     * Soporte cierra la conversación: queda un aviso en el historial y el visitante lo
     * recibe al instante (con opción de pedir una copia por correo).
     */
    public function cerrar(Conversacion $conversacion, User $usuario): void
    {
        if ($conversacion->estado === 'cerrada') {
            return;
        }
        $this->enviar($conversacion, 'sistema', 'La conversación fue cerrada por '.strtok($usuario->name, ' ').'.', $usuario);
        $conversacion->forceFill(['estado' => 'cerrada', 'cerrada_en' => now(), 'no_leidos_admin' => 0])->save();
        $this->transmitir(new ConversacionCerrada($conversacion->id));
    }

    /** @return array<int, array<string, mixed>> */
    public function historial(Conversacion $conversacion, int $despuesDe = 0, int $limite = 200): array
    {
        return $conversacion->mensajes()->with('usuario')->where('id', '>', $despuesDe)
            ->limit($limite)->get()->map->paraCliente()->all();
    }

    /** Último id de los mensajes de «autor» que el otro lado ya leyó (para pintar ✓✓ al consultar). */
    public function leidoHasta(Conversacion $conversacion, string $autor): int
    {
        return (int) MensajeChat::query()->where('conversacion_id', $conversacion->id)->where('autor', $autor)->whereNotNull('leido_en')->max('id');
    }

    /** Conversación en texto plano (copia por correo). */
    public function transcripcion(Conversacion $conversacion): string
    {
        return $conversacion->mensajes()->with('usuario')->get()->map(function (MensajeChat $m) use ($conversacion) {
            $quien = match ($m->autor) {
                'visitante' => $conversacion->nombre,
                'admin' => $m->usuario?->name ? strtok($m->usuario->name, ' ') : 'Soporte',
                default => '—',
            };

            return '['.$m->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i').'] '.$quien.': '.$m->cuerpo;
        })->implode("\n");
    }

    /** Sin «return»: broadcast() envía al destruirse, y así un fallo de Reverb queda dentro del rescue. */
    private function transmitir(object $evento): void
    {
        rescue(function () use ($evento) {
            broadcast($evento)->toOthers();
        }, null, true);
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
