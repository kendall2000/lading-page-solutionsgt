<?php

namespace App\Console\Commands;

use App\Services\Respaldos;
use Illuminate\Console\Command;

/**
 * Lista los respaldos o descarga uno ya descifrado (.sql) en storage/app/respaldos.
 * No restaura nada: eso se hace a mano con el cliente de MySQL (ver CLAUDE.md, «Tareas programadas»).
 */
class DescargarRespaldo extends Command
{
    protected $signature = 'respaldo:descargar {numero? : Número de la lista (1 = el más reciente)}';

    protected $description = 'Lista los respaldos de Contabo o descarga uno descifrado';

    public function handle(Respaldos $respaldos): int
    {
        $lista = $respaldos->lista();
        if ($lista->isEmpty()) {
            $this->warn('Todavía no hay respaldos en Contabo.');

            return self::SUCCESS;
        }

        $numero = $this->argument('numero');
        if ($numero === null) {
            $this->table(['#', 'Respaldo'], $lista->map(fn ($r, $i) => [$i + 1, basename($r)])->all());
            $this->line('Para descargar uno: php artisan respaldo:descargar <número>');

            return self::SUCCESS;
        }

        $ruta = $lista->get((int) $numero - 1);
        if (! $ruta) {
            $this->error("No existe el respaldo número {$numero}.");

            return self::FAILURE;
        }

        $destino = storage_path('app/respaldos/'.basename($ruta, '.gz.enc'));
        if (! is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0770, true);
        }
        file_put_contents($destino, $respaldos->leer($ruta));
        $this->info("Descargado en {$destino}");
        $this->warn('Contiene datos privados (usuarios, mensajes): bórralo cuando termines.');

        return self::SUCCESS;
    }
}
