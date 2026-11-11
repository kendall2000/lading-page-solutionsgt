<?php

namespace App\Services;

use App\Models\ConfiguracionDTE;
use App\Models\DocumentoTributario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Certificación FEL con INFILE, igual que en sistema-inventario:
 * factura, notas de crédito / débito / abono sobre una factura certificada y anulación.
 */
class DTEService
{
    public function __construct(
        private XMLGeneratorService $xmlGen,
        private INFILEService $infile
    ) {}

    /**
     * Emite y certifica el documento (factura o nota) en la SAT con INFILE.
     * Si falla, guarda el mensaje de error exacto y el estado 'error' para poder reintentar.
     */
    public function certificar(DocumentoTributario $doc): array
    {
        $cfg = $this->config();

        if ($doc->estaCertificado()) {
            throw new RuntimeException("Este documento ya fue certificado ante la SAT (UUID: {$doc->uuid_sat}).");
        }

        if ($doc->estaAnulado()) {
            throw new RuntimeException('Este documento está anulado; no se puede certificar.');
        }

        // Generar XML (nuevo en cada intento: la fecha de emisión debe ser la del envío)
        $xml = $this->xmlGen->generarDocumento($doc, $cfg);
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

    /**
     * Crea una nota (NCRE, NDEB o NABN) ligada a una factura certificada y la certifica.
     * La nota queda guardada aunque INFILE la rechace (estado 'error', se puede reintentar).
     *
     * @return array{nota: DocumentoTributario, exito: bool, error?: string, uuid?: string}
     */
    public function emitirNota(
        DocumentoTributario $factura,
        string $tipo,
        float $monto,
        string $motivo,
        ?string $descripcion = null
    ): array {
        $cfg = $this->config();

        if (!isset(DocumentoTributario::NOTAS[$tipo])) {
            throw new RuntimeException('Tipo de nota no válido.');
        }

        $habilitada = match ($tipo) {
            DocumentoTributario::TIPO_NOTA_CREDITO => $cfg->habilitar_notas_credito,
            DocumentoTributario::TIPO_NOTA_DEBITO => $cfg->habilitar_notas_debito,
            DocumentoTributario::TIPO_NOTA_ABONO => $cfg->habilitar_notas_abono,
        };
        $nombre = DocumentoTributario::NOTAS[$tipo]['nombre'];

        if (!$habilitada) {
            throw new RuntimeException("La emisión de «{$nombre}» está desactivada en la configuración FEL.");
        }

        if (!$factura->esFactura() || !$factura->estaCertificado()) {
            throw new RuntimeException('Solo se pueden emitir notas sobre una factura certificada y vigente.');
        }

        $monto = round($monto, 2);
        if ($monto <= 0) {
            throw new RuntimeException('El monto de la nota debe ser mayor que cero.');
        }

        if (in_array($tipo, DocumentoTributario::NOTAS_QUE_REBAJAN, true)) {
            $saldo = $factura->saldoAcreditable();
            if ($monto > $saldo) {
                throw new RuntimeException('El monto supera el saldo que aún se puede rebajar de la factura ('
                    . number_format($saldo, 2) . ' ' . $factura->moneda . ').');
            }
        }

        $motivo = trim($motivo);
        $descripcion = trim((string) $descripcion) ?: "{$nombre} - Factura {$factura->serie_sat} {$factura->numero_sat}";

        $nota = DB::transaction(function () use ($factura, $tipo, $monto, $motivo, $descripcion) {
            $nota = DocumentoTributario::create([
                'cuenta_id' => $factura->cuenta_id,
                'documento_origen_id' => $factura->id,
                'tipo' => $tipo,
                'numero_interno' => DocumentoTributario::generarNumeroInterno($tipo),
                'moneda' => $factura->moneda,
                'metodo_pago' => $factura->metodo_pago,
                'condicion_pago' => $factura->condicion_pago,
                'subtotal' => $monto,
                'impuestos' => 0,
                'total' => $monto,
                'tipo_cambio' => $factura->tipo_cambio,
                'nit_receptor' => $factura->nit_receptor,
                'nombre_receptor' => $factura->nombre_receptor,
                'correo_receptor' => $factura->correo_receptor,
                'direccion_receptor' => $factura->direccion_receptor,
                'estado' => 'emitido',
                'fecha_emision' => now(),
                'notas' => $motivo,
            ]);
            $nota->asegurarToken();

            $nota->items()->create([
                'numero_linea' => 1,
                'tipo' => 'S',
                'descripcion' => $descripcion,
                'cantidad' => 1,
                'unidad_medida' => 'UND',
                'precio_unitario' => $monto,
                'descuento' => 0,
                'subtotal' => $monto,
            ]);

            return $nota;
        });

        $resultado = $this->certificar($nota);

        return ['nota' => $nota->fresh()] + $resultado;
    }

    /**
     * Anula ante la SAT un documento certificado: genera el XML de anulación,
     * lo firma (es_anulacion = S) y lo envía a INFILE. Si falla, el documento
     * sigue certificado y se lanza la excepción con el detalle.
     */
    public function anular(DocumentoTributario $doc, string $motivo): DocumentoTributario
    {
        $cfg = $this->config();

        if (!$cfg->permite_anulacion_directa) {
            throw new RuntimeException('La anulación directa está desactivada en la configuración FEL. Emita una nota de crédito.');
        }

        if (!$doc->estaCertificado()) {
            throw new RuntimeException('Solo se pueden anular documentos certificados y vigentes.');
        }

        $notasVigentes = $doc->complementarios()->where('estado', 'certificado')->count();
        if ($notasVigentes > 0) {
            throw new RuntimeException("La factura tiene {$notasVigentes} nota(s) certificada(s). Anúlelas primero.");
        }

        $motivo = trim($motivo);
        $xml = $this->xmlGen->generarAnulacion($doc, $cfg, $motivo);

        try {
            $xmlFirmado = $this->infile->firmar($xml, $cfg, $doc, esAnulacion: true);
            $this->infile->anular($xmlFirmado, $cfg, $doc);
        } catch (Throwable $e) {
            $doc->update(['error_mensaje' => 'Anulación rechazada: ' . $e->getMessage()]);
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        $doc->update([
            'estado' => 'anulado',
            'motivo_anulacion' => $motivo,
            'fecha_anulacion' => now(),
            'xml_anulacion' => $xmlFirmado,
            'error_mensaje' => null,
        ]);

        return $doc->fresh();
    }

    /** Configuración activa y con credenciales completas. */
    private function config(): ConfiguracionDTE
    {
        $cfg = ConfiguracionDTE::actual();

        if (!$cfg->activo) {
            throw new RuntimeException('La certificación FEL (INFILE) se encuentra desactivada en la configuración.');
        }

        if (empty($cfg->alias_ws) || empty($cfg->token_signer) || empty($cfg->llave_ws)) {
            throw new RuntimeException('Faltan credenciales WebService de INFILE (Alias, Token o Llave).');
        }

        return $cfg;
    }
}
