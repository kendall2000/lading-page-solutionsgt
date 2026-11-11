<?php

namespace App\Services;

use App\Models\ConfiguracionDTE;
use App\Models\DocumentoTributario;
use Carbon\Carbon;
use RuntimeException;
use Throwable;

class DTEService
{
    public function __construct(
        private XMLGeneratorService $xmlGen,
        private INFILEService $infile
    ) {}

    /**
     * Emite y certifica la factura en la SAT con INFILE.
     * Si falla, guarda el mensaje de error exacto y el estado 'error' para poder reintentar.
     */
    public function certificar(DocumentoTributario $doc): array
    {
        $cfg = ConfiguracionDTE::actual();

        if (!$cfg->activo) {
            throw new RuntimeException('La certificación FEL (INFILE) se encuentra desactivada en la configuración.');
        }

        if (empty($cfg->alias_ws) || empty($cfg->token_signer) || empty($cfg->llave_ws)) {
            throw new RuntimeException('Faltan credenciales WebService de INFILE (Alias, Token o Llave).');
        }

        if ($doc->estado === 'certificado') {
            throw new RuntimeException("Esta factura ya fue certificada ante la SAT (UUID: {$doc->uuid_sat}).");
        }

        // Generar XML
        $xml = $this->xmlGen->generarFactura($doc, $cfg);
        $doc->update([
            'xml_generado' => $xml,
            'estado' => 'pendiente',
            'error_mensaje' => null,
        ]);

        try {
            // 1. Firmar XML
            $xmlFirmado = $this->infile->firmar($xml, $cfg, $doc);
            $doc->update(['xml_firmado' => $xmlFirmado]);

            // 2. Certificar ante SAT
            $res = $this->infile->certificar($xmlFirmado, $cfg, $doc);

            $fechaCert = !empty($res['fecha'])
                ? Carbon::parse($res['fecha'])->setTimezone(config('app.timezone'))
                : now();

            $doc->update([
                'uuid_sat' => $res['uuid'],
                'serie_sat' => $res['serie'],
                'numero_sat' => $res['numero'],
                'fecha_certificacion' => $fechaCert,
                'xml_certificado' => $res['xml_certificado'],
                'estado' => 'certificado',
                'error_mensaje' => null,
            ]);

            return [
                'exito' => true,
                'uuid' => $res['uuid'],
                'serie' => $res['serie'],
                'numero' => $res['numero'],
            ];
        } catch (Throwable $e) {
            $doc->update([
                'estado' => 'error',
                'error_mensaje' => $e->getMessage(),
            ]);

            return [
                'exito' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
