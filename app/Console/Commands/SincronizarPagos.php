<?php

namespace App\Console\Commands;

use App\Models\ConfiguracionPagos;
use App\Services\Pagos;
use Illuminate\Console\Command;

/**
 * Respaldo de los avisos de PayPal (programado cada 6 horas, ver routes/console.php): revisa las
 * suscripciones con cobro cercano, los pagos que quedaron a medias y borra intentos abandonados.
 */
class SincronizarPagos extends Command
{
    protected $signature = 'pagos:sincronizar';

    protected $description = 'Revisa con PayPal las suscripciones por cobrar y los pagos pendientes, y extiende o corta el acceso';

    public function handle(Pagos $pagos): int
    {
        if (! ConfiguracionPagos::actual()->tieneCredenciales()) {
            $this->info('PayPal no está configurado: nada que revisar.');

            return self::SUCCESS;
        }
        $r = $pagos->sincronizarTodo();
        $this->info("Revisados: {$r['revisadas']}. Errores de PayPal: {$r['errores']}. Intentos abandonados borrados: {$r['borradas']}.");

        return $r['errores'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
