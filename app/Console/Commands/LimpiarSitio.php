<?php

namespace App\Console\Commands;

use App\Models\Conversacion;
use App\Models\MensajeChat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Borra lo viejo que ya no sirve (programado cada madrugada, ver routes/console.php). */
class LimpiarSitio extends Command
{
    protected $signature = 'sitio:limpiar';

    protected $description = 'Borra la bitácora de correos de más de 90 días y los chats cerrados sin actividad hace más de 6 meses';

    /** Plazos elegidos por el usuario (2026-09-29). */
    public const DIAS_BITACORA = 90;

    public const MESES_CHATS = 6;

    public function handle(): int
    {
        $bitacora = DB::table('bitacora_correos')->where('created_at', '<', now()->subDays(self::DIAS_BITACORA))->delete();

        // Solo cerradas: una abierta sigue esperando respuesta aunque sea vieja.
        $limite = now()->subMonths(self::MESES_CHATS);
        $viejas = Conversacion::query()->where('estado', 'cerrada')
            ->where(fn ($q) => $q->where('ultimo_mensaje_en', '<', $limite)
                ->orWhere(fn ($q) => $q->whereNull('ultimo_mensaje_en')->where('updated_at', '<', $limite)))
            ->pluck('id');
        foreach ($viejas->chunk(500) as $ids) {
            MensajeChat::query()->whereIn('conversacion_id', $ids)->delete();
            Conversacion::query()->whereKey($ids)->delete();
        }

        $this->info("Bitácora de correos: {$bitacora} registros borrados. Chats cerrados: {$viejas->count()} borrados.");

        return self::SUCCESS;
    }
}
