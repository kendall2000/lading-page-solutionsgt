<?php

namespace App\Console\Commands;

use App\Services\Licencias;
use Illuminate\Console\Command;

/**
 * Revisión diaria de las licencias de Nexus (8:00, ver routes/console.php): avisa por correo al cliente
 * cuando su instalación entra en una etapa (por vencer, gracia, restringido, solo lectura) y a «Avisos a»
 * cuando entra en mora o deja de comunicarse. Las etapas las aplica cada instalación por su cuenta.
 */
class RevisarLicencias extends Command
{
    protected $signature = 'licencias:revisar';

    protected $description = 'Envía los avisos de vencimiento y mora de las licencias de Nexus';

    public function handle(Licencias $licencias): int
    {
        $r = $licencias->revisar();
        $this->info("Avisos a clientes: {$r['clientes']}. Avisos para ti: {$r['avisos']}.");

        return self::SUCCESS;
    }
}
