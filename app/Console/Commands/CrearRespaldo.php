<?php

namespace App\Console\Commands;

use App\Services\Correos;
use App\Services\Respaldos;
use Illuminate\Console\Command;
use Throwable;

/** Respaldo diario de la base en Contabo (programado a las 3:00, ver routes/console.php). */
class CrearRespaldo extends Command
{
    protected $signature = 'respaldo:crear {--conexion= : Conexión a respaldar (por omisión, la principal)}';

    protected $description = 'Respalda la base (comprimida y cifrada) en Contabo y conserva los últimos 30';

    public function handle(Respaldos $respaldos, Correos $correos): int
    {
        try {
            $ruta = $respaldos->crear($this->option('conexion') ?: config('database.default'));
        } catch (Throwable $e) {
            report($e);
            $this->error('No se pudo crear el respaldo: '.$e->getMessage());
            // Aviso a «Avisos a»: un respaldo que falla en silencio no sirve.
            rescue(fn () => $correos->avisar('respaldo_fallido', ['error' => $e->getMessage(), 'fecha' => now()->format('d/m/Y H:i')]), null, false);

            return self::FAILURE;
        }

        $this->info("Respaldo guardado en Contabo: {$ruta}");

        return self::SUCCESS;
    }
}
