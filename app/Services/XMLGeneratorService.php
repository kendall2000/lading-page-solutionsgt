<?php

namespace App\Services;

use App\Models\ConfiguracionDTE;
use App\Models\DocumentoTributario;

class XMLGeneratorService
{
    /**
     * Construye el XML DTE estándar de la SAT de Guatemala.
     */
    public function generarFactura(DocumentoTributario $doc, ConfiguracionDTE $cfg): string
    {
        $doc->loadMissing('items');

        $fecha = now()->setTimezone('America/Guatemala')->format('Y-m-d\TH:i:sP');
        $nitReceptor = strtoupper(trim($doc->nit_receptor ?: 'CF'));
        $nombreReceptor = htmlspecialchars(trim($doc->nombre_receptor), ENT_XML1, 'UTF-8');
        $correoReceptor = htmlspecialchars(trim($doc->correo_receptor ?? ''), ENT_XML1, 'UTF-8');
        $dirReceptor = htmlspecialchars(trim($doc->direccion_receptor ?? 'Ciudad'), ENT_XML1, 'UTF-8');

        $moneda = $doc->moneda ?: 'USD';
        $granTotal = number_format((float) $doc->total, 2, '.', '');

        // Construir Frases SAT desde la configuración dinámica del emisor
        $frasesXml = '';
        $frases = $cfg->obtenerFrases();
        if (!empty($frases)) {
            $frasesXml .= '<dte:Frases>';
            foreach ($frases as $f) {
                $tf = htmlspecialchars($f['tipo_frase'], ENT_XML1, 'UTF-8');
                $ce = htmlspecialchars($f['codigo_escenario'], ENT_XML1, 'UTF-8');
                $frasesXml .= "<dte:Frase CodigoEscenario=\"{$ce}\" TipoFrase=\"{$tf}\"/>";
            }
            $frasesXml .= '</dte:Frases>';
        }

        // Construir ítems XML
        $itemsXml = '<dte:Items>';
        $totalIva = 0.00;

        foreach ($doc->items as $idx => $item) {
            $numLinea = $idx + 1;
            $bienOServicio = $item->tipo === 'B' ? 'B' : 'S';
            $desc = htmlspecialchars(trim($item->descripcion), ENT_XML1, 'UTF-8');
            $cant = number_format((float) $item->cantidad, 2, '.', '');
            $pu = number_format((float) $item->precio_unitario, 2, '.', '');
            $subt = number_format((float) $item->subtotal, 2, '.', '');

            // Régimen Pequeño Contribuyente no desglosa IVA por ítem
            $impuestosXml = '';
            if ($cfg->afiliacion_iva === 'GEN') {
                $base = round((float)$item->subtotal / 1.12, 2);
                $iva = round((float)$item->subtotal - $base, 2);
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

        $codEstab = htmlspecialchars($cfg->codigo_establecimiento ?: '1', ENT_XML1, 'UTF-8');
        $nitEmisor = htmlspecialchars($cfg->nit_emisor, ENT_XML1, 'UTF-8');
        $nombreEmisor = htmlspecialchars($cfg->nombre_emisor, ENT_XML1, 'UTF-8');
        $nombreComercial = htmlspecialchars($cfg->nombre_comercial, ENT_XML1, 'UTF-8');
        $dirEmisor = htmlspecialchars($cfg->direccion, ENT_XML1, 'UTF-8');
        $muniEmisor = htmlspecialchars($cfg->municipio ?: 'GUATEMALA', ENT_XML1, 'UTF-8');
        $depEmisor = htmlspecialchars($cfg->departamento ?: 'GUATEMALA', ENT_XML1, 'UTF-8');
        $cpEmisor = htmlspecialchars($cfg->codigo_postal ?: '01001', ENT_XML1, 'UTF-8');
        $afiliacionIva = htmlspecialchars($cfg->afiliacion_iva ?: 'PEQ', ENT_XML1, 'UTF-8');

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
          Tipo="FACT"/>
        <dte:Emisor
          AfiliacionIVA="{$afiliacionIva}"
          CodigoEstablecimiento="{$codEstab}"
          CorreoEmisor="{$cfg->correo_emisor}"
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
        <dte:Receptor
          CorreoReceptor="{$correoReceptor}"
          IDReceptor="{$nitReceptor}"
          NombreReceptor="{$nombreReceptor}">
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
        </dte:Totales>
      </dte:DatosEmision>
    </dte:DTE>
  </dte:SAT>
</dte:GTDocumento>
XML;
    }
}
