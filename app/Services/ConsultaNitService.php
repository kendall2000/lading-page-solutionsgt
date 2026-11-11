<?php

namespace App\Services;

use App\Models\ConfiguracionDTE;
use Illuminate\Support\Facades\Http;
use Throwable;

class ConsultaNitService
{
    /**
     * Consulta el nombre del contribuyente directamente ante el servicio de INFILE / SAT.
     * Endpoint oficial FEEL: POST https://consultareceptores.feel.com.gt/rest/action
     */
    public static function consultar(string $nit): array
    {
        $nitLimpio = strtoupper(trim(str_replace(['-', ' '], '', $nit)));

        if (empty($nitLimpio)) {
            return ['exito' => false, 'mensaje' => 'NIT vacío'];
        }

        if ($nitLimpio === 'CF' || $nitLimpio === 'CONSUMIDORFINAL') {
            return ['exito' => true, 'nit' => 'CF', 'nombre' => 'CONSUMIDOR FINAL'];
        }

        $cfg = ConfiguracionDTE::actual();

        // Si no hay credenciales configuradas en configuracion_dte, usar las de pruebas por defecto
        $alias = !empty($cfg->alias_ws) ? $cfg->alias_ws : 'INFILE_DEMO';
        $llave = !empty($cfg->llave_ws) ? $cfg->llave_ws : '6B7B49B283084C9E';

        try {
            $response = Http::timeout(6)
                ->asJson()
                ->post('https://consultareceptores.feel.com.gt/rest/action', [
                    'emisor_codigo' => $alias,
                    'emisor_clave'  => $llave,
                    'nit_consulta'  => $nitLimpio,
                ]);

            if (!$response->successful()) {
                return ['exito' => false, 'mensaje' => 'Error de conexión con el certificador FEL'];
            }

            $data = $response->json();
            $nombre = null;

            if (isset($data['nombre'])) {
                $nombre = $data['nombre'];
            } elseif (isset($data['receptor']['nombre'])) {
                $nombre = $data['receptor']['nombre'];
            }

            if (!empty($nombre)) {
                return [
                    'exito' => true,
                    'nit' => $nitLimpio,
                    'nombre' => trim($nombre),
                ];
            }

            return ['exito' => false, 'mensaje' => 'NIT no encontrado en la base de la SAT'];
        } catch (Throwable $e) {
            return ['exito' => false, 'mensaje' => 'No fue posible consultar el NIT: ' . $e->getMessage()];
        }
    }
}
