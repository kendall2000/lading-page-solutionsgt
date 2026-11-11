<?php

namespace App\Services;

use App\Models\ConfiguracionDTE;
use App\Models\DocumentoTributario;
use Carbon\Carbon;
use RuntimeException;

/**
 * XML de la SAT (FEL 0.2.0) igual que en sistema-inventario:
 * factura (FACT), notas de crédito (NCRE), débito (NDEB) y abono (NABN),
 * y el XML de anulación (GTAnulacionDocumento).
 */
class XMLGeneratorService
{
    public function generarFactura(DocumentoTributario $doc, ConfiguracionDTE $cfg): string
    {
        return $this->generarDocumento($doc, $cfg);
    }

    /**
     * Arma el XML según el tipo del documento.
     * - FACT, NCRE, NDEB: llevan frases; el IVA solo se desglosa con afiliación GEN.
     * - NCRE, NDEB: llevan el complemento «ReferenciasNota» con el UUID de la factura de origen.
     * - NABN: sin frases, sin impuestos y sin complemento.
     */
    public function generarDocumento(DocumentoTributario $doc, ConfiguracionDTE $cfg): string
    {
        $doc->loadMissing('items');

        $tipo = $doc->tipo ?: DocumentoTributario::TIPO_FACTURA;
        $esAbono = $tipo === DocumentoTributario::TIPO_NOTA_ABONO;
        $afiliacionIva = strtoupper(trim($cfg->afiliacion_iva ?: 'PEQ'));
        $desglosaIva = $afiliacionIva === 'GEN' && ! $esAbono;

        $fecha = now()->setTimezone('America/Guatemala')->format('Y-m-d\TH:i:sP');
        $moneda = $this->x($doc->moneda ?: 'USD');
        $granTotal = number_format((float) $doc->total, 2, '.', '');

        $frasesXml = $esAbono ? '' : $this->frases($cfg, $afiliacionIva);

        // Ítems
        $itemsXml = '<dte:Items>';
        $totalIva = 0.00;

        foreach ($doc->items as $idx => $item) {
            $numLinea = $idx + 1;
            $bienOServicio = $item->tipo === 'B' ? 'B' : 'S';
            $desc = $this->x(trim($item->descripcion));
            $cant = number_format((float) $item->cantidad, 2, '.', '');
            $pu = number_format((float) $item->precio_unitario, 2, '.', '');
            $subt = number_format((float) $item->subtotal, 2, '.', '');

            $impuestosXml = '';
            if ($desglosaIva) {
                $base = round((float) $item->subtotal / 1.12, 2);
                $iva = round((float) $item->subtotal - $base, 2);
                $totalIva += $iva;
                $baseFmt = number_format($base, 2, '.', '');
                $ivaFmt = number_format($iva, 2, '.', '');

                $impuestosXml = <<<XML

            <dte:Impuestos>
              <dte:Impuesto>
                <dte:NombreCorto>IVA</dte:NombreCorto>
                <dte:CodigoUnidadGravable>1</dte:CodigoUnidadGravable>
                <dte:MontoGravable>{$baseFmt}</dte:MontoGravable>
                <dte:MontoImpuesto>{$ivaFmt}</dte:MontoImpuesto>
              </dte:Impuesto>
            </dte:Impuestos>
XML;
            }

            $itemsXml .= <<<XML

          <dte:Item BienOServicio="{$bienOServicio}" NumeroLinea="{$numLinea}">
            <dte:Cantidad>{$cant}</dte:Cantidad>
            <dte:UnidadMedida>UND</dte:UnidadMedida>
            <dte:Descripcion>{$desc}</dte:Descripcion>
            <dte:PrecioUnitario>{$pu}</dte:PrecioUnitario>
            <dte:Precio>{$subt}</dte:Precio>
            <dte:Descuento>0.00</dte:Descuento>{$impuestosXml}
            <dte:Total>{$subt}</dte:Total>
          </dte:Item>
XML;
        }
        $itemsXml .= "\n        </dte:Items>";

        $totalesImpuestosXml = '';
        if ($totalIva > 0) {
            $ivaTotalFmt = number_format($totalIva, 2, '.', '');
            $totalesImpuestosXml = <<<XML
          <dte:TotalImpuestos>
            <dte:TotalImpuesto NombreCorto="IVA" TotalMontoImpuesto="{$ivaTotalFmt}"/>
          </dte:TotalImpuestos>
XML;
        }

        $complementoXml = in_array($tipo, [DocumentoTributario::TIPO_NOTA_CREDITO, DocumentoTributario::TIPO_NOTA_DEBITO], true)
            ? $this->complementoNota($doc)
            : '';

        // Emisor
        $codEstab = $this->x((string) ($cfg->codigo_establecimiento ?: '1'));
        $nitEmisor = $this->x(trim((string) $cfg->nit_emisor));
        $nombreEmisor = $this->x(trim((string) $cfg->nombre_emisor));
        $nombreComercial = $this->x(trim((string) $cfg->nombre_comercial));
        $dirEmisor = $this->x(trim((string) $cfg->direccion));
        $muniEmisor = $this->x(trim($cfg->municipio ?: 'GUATEMALA'));
        $depEmisor = $this->x(trim($cfg->departamento ?: 'GUATEMALA'));
        $cpEmisor = $this->x(trim($cfg->codigo_postal ?: '01001'));

        // Receptor
        $idReceptor = $this->nit($doc->nit_receptor);
        $nitReceptor = $this->x($idReceptor);
        $nombreReceptor = $this->x(trim((string) $doc->nombre_receptor));
        $dirReceptor = $this->x(trim($doc->direccion_receptor ?: 'Ciudad'));
        $correoReceptor = trim((string) $doc->correo_receptor);

        // Atributos opcionales: solo si tienen valor (INFILE rechaza atributos vacíos)
        $correoEmisorAttr = ! empty($cfg->correo_emisor) ? ' CorreoEmisor="'.$this->x($cfg->correo_emisor).'"' : '';
        $correoReceptorAttr = $correoReceptor !== '' ? ' CorreoReceptor="'.$this->x($correoReceptor).'"' : '';
        // DPI de 13 dígitos: INFILE pide TipoEspecial="CUI"
        $tipoEspecialAttr = $this->esCUI($idReceptor) ? ' TipoEspecial="CUI"' : '';

        $tipoAttr = $this->x($tipo);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<dte:GTDocumento
  xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
  xmlns:dte="http://www.sat.gob.gt/dte/fel/0.2.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  Version="0.1">
  <dte:SAT ClaseDocumento="dte">
    <dte:DTE ID="DatosCertificados">
      <dte:DatosEmision ID="DatosEmision">
        <dte:DatosGenerales
          CodigoMoneda="{$moneda}"
          FechaHoraEmision="{$fecha}"
          Tipo="{$tipoAttr}"/>
        <dte:Emisor
          AfiliacionIVA="{$afiliacionIva}"
          CodigoEstablecimiento="{$codEstab}"{$correoEmisorAttr}
          NITEmisor="{$nitEmisor}"
          NombreComercial="{$nombreComercial}"
          NombreEmisor="{$nombreEmisor}">
          <dte:DireccionEmisor>
            <dte:Direccion>{$dirEmisor}</dte:Direccion>
            <dte:CodigoPostal>{$cpEmisor}</dte:CodigoPostal>
            <dte:Municipio>{$muniEmisor}</dte:Municipio>
            <dte:Departamento>{$depEmisor}</dte:Departamento>
            <dte:Pais>GT</dte:Pais>
          </dte:DireccionEmisor>
        </dte:Emisor>
        <dte:Receptor{$correoReceptorAttr}
          IDReceptor="{$nitReceptor}"
          NombreReceptor="{$nombreReceptor}"{$tipoEspecialAttr}>
          <dte:DireccionReceptor>
            <dte:Direccion>{$dirReceptor}</dte:Direccion>
            <dte:CodigoPostal>01001</dte:CodigoPostal>
            <dte:Municipio>GUATEMALA</dte:Municipio>
            <dte:Departamento>GUATEMALA</dte:Departamento>
            <dte:Pais>GT</dte:Pais>
          </dte:DireccionReceptor>
        </dte:Receptor>
        {$frasesXml}
        {$itemsXml}
        <dte:Totales>
          {$totalesImpuestosXml}
          <dte:GranTotal>{$granTotal}</dte:GranTotal>
        </dte:Totales>{$complementoXml}
      </dte:DatosEmision>
    </dte:DTE>
  </dte:SAT>
</dte:GTDocumento>
XML;
    }

    /**
     * XML para anular un DTE certificado. La fecha del documento a anular debe ser
     * exactamente la FechaHoraEmision con la que se certificó.
     */
    public function generarAnulacion(DocumentoTributario $doc, ConfiguracionDTE $cfg, string $motivo): string
    {
        $ahora = now()->setTimezone('America/Guatemala')->format('Y-m-d\TH:i:sP');
        $fechaEmision = $this->x($this->fechaEmisionSat($doc));
        $uuid = $this->x((string) $doc->uuid_sat);
        $nitEmisor = $this->x(trim((string) $cfg->nit_emisor));
        $nitReceptor = $this->x($this->nit($doc->nit_receptor));
        $motivoE = $this->x($motivo);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<dte:GTAnulacionDocumento
  xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
  xmlns:dte="http://www.sat.gob.gt/dte/fel/0.1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  Version="0.1">
  <dte:SAT>
    <dte:AnulacionDTE ID="DatosCertificados">
      <dte:DatosGenerales
        FechaEmisionDocumentoAnular="{$fechaEmision}"
        FechaHoraAnulacion="{$ahora}"
        ID="DatosAnulacion"
        IDReceptor="{$nitReceptor}"
        MotivoAnulacion="{$motivoE}"
        NITEmisor="{$nitEmisor}"
        NumeroDocumentoAAnular="{$uuid}"/>
    </dte:AnulacionDTE>
  </dte:SAT>
</dte:GTAnulacionDocumento>
XML;
    }

    /**
     * FechaHoraEmision con la que el documento se certificó (sale del XML guardado);
     * si no se encuentra, la fecha de emisión registrada.
     */
    public function fechaEmisionSat(DocumentoTributario $doc): string
    {
        foreach ([$doc->xml_certificado, $doc->xml_firmado, $doc->xml_generado] as $xml) {
            if ($xml && preg_match('/FechaHoraEmision="([^"]+)"/', $xml, $m)) {
                return $m[1];
            }
        }

        $fecha = $doc->fecha_emision ?? $doc->created_at ?? now();

        return Carbon::parse($fecha)->setTimezone('America/Guatemala')->format('Y-m-d\TH:i:sP');
    }

    /** Complemento «ReferenciasNota» que liga la nota con la factura de origen. */
    private function complementoNota(DocumentoTributario $nota): string
    {
        $origen = $nota->origen;
        if (! $origen || empty($origen->uuid_sat)) {
            throw new RuntimeException('La nota no tiene una factura de origen certificada.');
        }

        $fechaOrigen = $this->x(substr($this->fechaEmisionSat($origen), 0, 10));
        $motivo = $this->x(trim($nota->notas ?: $nota->nombreTipo()));
        $uuid = $this->x((string) $origen->uuid_sat);
        $serie = $this->x((string) $origen->serie_sat);
        $numero = $this->x((string) $origen->numero_sat);

        return <<<XML

        <dte:Complementos>
          <dte:Complemento IDComplemento="Notas" NombreComplemento="Notas" URIComplemento="http://www.sat.gob.gt/fel/notas.xsd">
            <cno:ReferenciasNota xmlns:cno="http://www.sat.gob.gt/face2/ComplementoReferenciaNota/0.1.0"
              FechaEmisionDocumentoOrigen="{$fechaOrigen}"
              MotivoAjuste="{$motivo}"
              NumeroAutorizacionDocumentoOrigen="{$uuid}"
              NumeroDocumentoOrigen="{$numero}"
              SerieDocumentoOrigen="{$serie}"
              Version="0.0"/>
          </dte:Complemento>
        </dte:Complementos>
XML;
    }

    /**
     * Frases configuradas en el panel o, si no hay, según la afiliación IVA
     * (tipo/escenario): PEQ → 3/1 (no genera crédito fiscal) · GEN → 1/1 (pagos trimestrales ISR) · EXE → 1/3.
     * La 2/1 (agente de retención del IVA) solo va si el NIT está calificado: la SAT la rechaza con el error 2614.
     */
    private function frases(ConfiguracionDTE $cfg, string $afiliacionIva): string
    {
        if (! empty($cfg->frases_config) && is_array($cfg->frases_config)) {
            $xml = '<dte:Frases>';
            foreach ($cfg->frases_config as $f) {
                $tf = $this->x((string) $f['tipo_frase']);
                $ce = $this->x((string) $f['codigo_escenario']);
                $xml .= "<dte:Frase CodigoEscenario=\"{$ce}\" TipoFrase=\"{$tf}\"/>";
            }

            return $xml.'</dte:Frases>';
        }

        return match ($afiliacionIva) {
            'GEN' => '<dte:Frases><dte:Frase CodigoEscenario="1" TipoFrase="1"/></dte:Frases>',
            'EXE' => '<dte:Frases><dte:Frase CodigoEscenario="3" TipoFrase="1"/></dte:Frases>',
            default => '<dte:Frases><dte:Frase CodigoEscenario="1" TipoFrase="3"/></dte:Frases>',
        };
    }

    /** NIT sin guiones, puntos ni espacios (p. ej. «1234567-8» → «12345678») o «CF». */
    private function nit(?string $documento): string
    {
        $limpio = (string) preg_replace('/[\s.\/-]/', '', strtoupper(trim((string) $documento)));

        return $limpio === '' ? 'CF' : $limpio;
    }

    /** DPI/CUI: 13 dígitos. */
    private function esCUI(string $id): bool
    {
        return ctype_digit($id) && strlen($id) === 13;
    }

    private function x(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
