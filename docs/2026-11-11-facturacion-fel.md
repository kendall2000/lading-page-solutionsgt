# Facturación FEL — cambios del 2026-11-11 (cómo y por qué)

Traspaso para quien siga trabajando en este proyecto. Resume lo que se hizo en una sesión con el usuario,
en el orden en que lo pidió, con el **porqué** de cada decisión. El resumen corto está en `CLAUDE.md`
(sección «Facturación FEL con INFILE»); aquí está el detalle.

> Estado al cierre: 145 pruebas pasan (`php artisan config:clear && php artisan test --testsuite=Feature`).
> **Nada está en git todavía.** La migración nueva ya se corrió en la base **local** (lerd).

---

## 0. Antes de tocar nada

- **La base es local.** El `.env` de esta computadora apunta a `DB_HOST=lerd-mariadb-11-8` (desde el 11-nov;
  copia del anterior en `.env.bkp.20261111-142444`). Antes `CLAUDE.md` decía que apuntaba a producción; se corrigió.
  Regla: antes de `migrate` o `db:seed`, mirar `DB_HOST`; si es la IP de producción, preguntar.
- **PHP corre en el contenedor de lerd**: `php artisan …` funciona desde esta carpeta. Desde otras rutas en
  `/run/media` lerd responde «does not mount temporary system directories».
- El modelo a seguir es `sistema-inventario` (`app/Services/DTEService.php`, `XMLGeneratorService.php`,
  `INFILEService.php`, `resources/views/ventas/pdf.blade.php` e `imprimir.blade.php`). Se copió el
  comportamiento, no el código tal cual: aquí los documentos son libres (no hay `Venta`/`Devolucion`).

---

## 1. Certificación «igual que sistema-inventario»

**Pedido:** que la landing certifique como el inventario. Ya emitía facturas (FACT); faltaban anulación,
notas de crédito / débito / abono y ver los XML.

### Modelo de datos — por qué una nota es otro `DocumentoTributario`
- Migración `2026_11_11_180000_add_anulacion_y_notas_a_documentos_tributarios`:
  `documento_origen_id` (FK a la factura), `fecha_anulacion`, `xml_anulacion`.
- Una nota (NCRE, NDEB, NABN) es **otro registro** de `documentos_tributarios` con una línea en
  `documento_tributario_items` y el motivo en `notas`. Así reutiliza todo lo que ya existía: certificar,
  reintentar, logs, PDF, QR y listado. No hizo falta una tabla nueva.
- `DocumentoTributario`: constantes `TIPO_*`, `NOTAS` (nombre y prefijo NC-/ND-/NA-), relaciones `origen()` y
  `complementarios()` (no se llamó `notas()` porque ya existe la columna `notas`), `nombreTipo()`,
  `estaCertificado()`, `estaAnulado()`, `saldoAcreditable()`.

### Servicios
- `XMLGeneratorService::generarDocumento()` arma FACT/NCRE/NDEB/NABN con un solo esqueleto (como el `envelope`
  del inventario):
  - NCRE y NDEB llevan el complemento `ReferenciasNota` con UUID, serie y número de la factura de origen.
  - NABN va **sin frases, sin IVA y sin complemento** (regla SAT, igual que el inventario).
  - El IVA solo se desglosa con afiliación **GEN**. *Diferencia a propósito con el inventario*: allá las notas
    llevan IVA siempre, lo que la SAT rechazaría para un Pequeño Contribuyente.
  - NIT sin guiones/puntos/espacios (`1234567-8` → `12345678`); DPI de 13 dígitos → `TipoEspecial="CUI"`.
  - `generarAnulacion()`: XML `GTAnulacionDocumento` (namespace 0.1.0). **Por qué `fechaEmisionSat()`**: la SAT
    exige que `FechaEmisionDocumentoAnular` sea exactamente la `FechaHoraEmision` certificada; esa fecha se
    genera al enviar (no es `fecha_emision` de la BD), así que se lee del XML guardado.
- `INFILEService`: `firmar(..., esAnulacion)` manda `es_anulacion = S|N`; nuevo `anular()` (mismo formato que
  certificar, identificador `anul-{id}` para no chocar con el de la certificación).
- `DTEService`:
  - `certificar()` sirve para factura y notas; el XML se rehace en cada intento.
  - `emitirNota()` valida: tipo habilitado en la configuración, factura certificada, monto > 0 y que
    NCRE + NABN no pasen del saldo (NDEB sí puede, es un cargo extra). Crea la nota en transacción y la certifica;
    si INFILE la rechaza, la nota queda en `error` para reintentar.
  - `anular()` respeta `permite_anulacion_directa`, solo anula certificados y **bloquea si la factura tiene notas
    certificadas** (hay que anularlas antes). Si INFILE rechaza, el documento **sigue certificado** y el motivo
    queda en `error_mensaje`.

### Panel
- Rutas: `POST facturas/{id}/anular`, `POST facturas/{id}/notas`, `GET facturas/{id}/xml/{generado|firmado|certificado|anulacion}`
  (`?descargar=1`). El XML certificado puede venir en base64 de INFILE; se decodifica al mostrarlo.
- `/admin/facturas/{id}`: menú «Ajustes SAT» (notas y anular, con modales), menú «XML», enlaces factura ↔ notas,
  tabla de notas con saldo acreditable. Insignia de estado compartida en `admin/facturas/partes/estado`.
- Se corrigió un error 500 previo: «Certificar» con la configuración desactivada lanzaba una excepción sin capturar.
- La página pública del QR decía «documento verificado» aunque estuviera anulado; ahora avisa «anulado».

---

## 2. Error SAT 2614 (frases)

**Síntoma:** `FEL-GUI-30 | 2.6 | 2.6.1 | No. 4 | El código de escenario [1] para el Tipo de Frase [2] no corresponde
a la afiliación del NIT del emisor. (2614)`.

**Causa (verificada en la BD y en el XML rechazado de FAC-2026-00002):** la configuración (afiliación GEN) tenía
las frases 1·1 y **2·1**. La 2·1 significa «Agente de retención del IVA» y el NIT no está calificado como tal.
Las descripciones del panel estaban cruzadas (la 1·1 decía «No genera derecho a crédito fiscal»), por eso parecía
correcto. Esos valores venían del código por defecto.

**Arreglo:**
- Datos: se dejó solo la frase 1·1 («Sujeto a pagos trimestrales ISR»). Después de eso la factura certificó.
- Código: `ConfiguracionDTE::CATALOGO_FRASES` y `textoFrase()`; valores por defecto correctos
  (PEQ → 3·1, GEN → 1·1); el panel muestra la tabla de frases y «Según la SAT: …» junto a cada fila.
- Si algún día aparece el mismo 2614 pero con **Tipo de Frase [1]**, el NIT está en retención definitiva:
  cambiar 1·1 por 1·2. La 2·1 solo va si la SAT calificó al NIT como agente de retención.
- Aviso: `sistema-inventario` tiene el mismo valor por defecto equivocado para PEQ (1·2 + 2·1). No se tocó.

---

## 3. Logs legibles (`/admin/facturas/{id}` y `/admin/facturacion?ver=logs`)

**Problema:** el JSON salía en una sola línea, con el XML en base64 (miles de caracteres) y el error real escondido.

**Cómo:**
- `LogDTE`: `erroresSat()` (lista de `descripcion_errores` con fuente, categoría, numeral y validación),
  `resumenError()` (primer error SAT para una línea), `respuestaLegible()` / `peticionLegible()` (JSON con sangría
  y los campos `archivo`, `xml_dte`, `xml_certificado` reemplazados por un aviso), `xmls()` (XML decodificados y
  con sangría), `nombreAccion()`.
- Vista compartida `admin/facturacion/partes/log-detalle` (+ `partes/codigo` con botón Copiar). Pestañas
  Respuesta / Petición / XML. Colores del tema (antes `bg-dark text-light`, ilegible en modo claro/oscuro).
- El aviso rojo de la factura separa `error_mensaje` («… — A | B») en una lista.

---

## 4. PDF y formato de factura / ticket

**Problema 1 — el PDF perdía el diseño:** la descarga usaba la misma vista de pantalla, hecha con flexbox, grid,
variables CSS, `gap` y degradados. **DomPDF no entiende nada de eso** (solo tablas y CSS 2.1 básico).

**Problema 2 — el usuario quería el formato profesional del inventario.**

**Cómo quedó:**
- Una sola plantilla `resources/views/facturas/partes/documento.blade.php` (A4 y ticket), copiando el estilo de
  `sistema-inventario/resources/views/ventas/pdf.blade.php` (A4) e `imprimir.blade.php` (ticket):
  encabezado con color de marca, título centrado con serie/número, recuadros Cliente/Detalles, tabla con
  encabezado de color y filas alternas, base imponible + IVA (solo GEN), recuadro verde FEL (rojo si está
  anulado), pie «Empresa — documento · Generado el…».
  **Todo con tablas y colores fijos** para que DomPDF lo dibuje; los estilos van bajo `.dc` para no chocar.
- `documento_pdf.blade.php` (PDF) y `documento_imprimir.blade.php` (pantalla, con barra de botones) solo envuelven
  esa plantilla → lo que se ve en pantalla es lo que se descarga.
- `FacturaLibreController::datosDocumento()` prepara los datos para ambos:
  - **QR**: si está certificado, lleva al **verificador oficial de la SAT** (misma URL que el inventario);
    si no, a la verificación pública propia.
  - **Logo** incrustado como data URI (`logoDataUri()`), porque DomPDF no descarga bien imágenes remotas
    (están en Contabo). WEBP se omite (DomPDF no lo dibuja bien).
  - Empresa: `configuracion_dte` (nombre comercial, razón social, NIT, dirección) + `configuracion_sitio`
    (`nombre`, `telefono`, `correo`, `color_primario`, `logo`). Antes la vista leía `nombre_comercial` y `slogan`
    de `configuracion_sitio`, campos que **no existen** (por eso salía siempre «SOLUTIONS GT»).
- Régimen e IVA salen de `afiliacion_iva`; antes decía «Pequeño Contribuyente» fijo con IVA 0.
- Ticket: papel de 80 mm (226.77 pt) con alto calculado según líneas, UUID y notas, para que no se corte.
- Detalle a recordar: en DomPDF un `position: fixed` necesita ancho en px (el pie usa `width: 728px`), y no
  reutilizar nombres de clase entre A4 y ticket (la tabla del ticket es `.titems` por eso).

---

## 5. Pruebas

`tests/Feature/FacturacionDteTest.php` (16 pruebas, INFILE simulado con `Http::fake`): certificar, NIT limpio y
XML escapado, NCRE con referencia, saldo, NDEB/NABN, IVA con GEN, notas desactivadas, anular (y fecha exacta,
`es_anulacion = S`, QR público «anulado»), rechazo de anulación, bloqueo con notas vigentes, XML ver/descargar,
pantallas, frases por defecto PEQ, explicación de frases en el panel, logs legibles sin base64, PDF carta y ticket,
certificar con configuración desactivada.

Para revisar visualmente un PDF sin navegador: generar la respuesta con `tinker` (usuario en el guard) y pasar
el PDF por `pdftoppm -png`. Chrome sin ventana no logró guardar capturas en esta máquina.

---

## 6. Pendiente

- Probar notas y anulación contra INFILE en ambiente de pruebas (la factura FAC-2026-00002 ya certificó).
- Confirmar el NIT del certificador impreso (INFILE, S.A. · 12521337).
- Hacer commit (formato del repo: `tipo(módulo): descripción` en español, cuerpo con el porqué).
- Pint marca estilo en archivos que **ya existían**; no se reformatearon para no mezclar cambios.
