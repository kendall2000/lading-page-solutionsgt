<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends Model
{
    use RegistraCambios;

    protected $table = 'cotizaciones';

    public const ESTADOS = [
        'borrador' => ['Borrador', 'secondary'],
        'enviada' => ['Enviada', 'info'],
        'aceptada' => ['Aceptada', 'success'],
        'rechazada' => ['Rechazada', 'danger'],
        'vencida' => ['Vencida', 'warning'],
    ];

    protected $fillable = [
        'numero',
        'fecha',
        'vigencia_dias',
        'cliente_id',
        'cuenta_id',
        'sistema_id',
        'cliente_empresa',
        'cliente_atencion',
        'cliente_correo',
        'cliente_telefono',
        'proveedor_nombre',
        'proveedor_cargo',
        'proveedor_correo',
        'proveedor_telefono',
        'titulo',
        'subtitulo',
        'objeto_descripcion',
        'objeto_puntos',
        'objeto_nota',
        'tipo',
        'planes',
        'planes_nota',
        'moneda',
        'total',
        'mantenimiento_incluye',
        'desarrollo_nuevo_incluye',
        'seccion_modulos_nuevos',
        'condiciones',
        'opciones_aceptacion',
        'firma_cliente_nombre',
        'firma_proveedor_nombre',
        'estado',
        'enviada_en',
        'aceptada_en',
        'notas_internas',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'vigencia_dias' => 'integer',
            'objeto_puntos' => 'array',
            'planes' => 'array',
            'mantenimiento_incluye' => 'array',
            'desarrollo_nuevo_incluye' => 'array',
            'condiciones' => 'array',
            'opciones_aceptacion' => 'array',
            'total' => 'decimal:2',
            'enviada_en' => 'datetime',
            'aceptada_en' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CotizacionItem::class)->orderBy('orden')->orderBy('id');
    }

    /** Fecha límite calculada según fecha y vigencia en días. */
    public function getFechaVencimientoAttribute(): Carbon
    {
        return ($this->fecha ?? now())->copy()->addDays($this->vigencia_dias ?? 30);
    }

    /** Comprueba si ya venció la vigencia. */
    public function estaVencida(): bool
    {
        if (in_array($this->estado, ['aceptada', 'rechazada'])) {
            return false;
        }

        return now()->isAfter($this->fecha_vencimiento);
    }

    /** Texto descriptivo del estado y clase CSS para badges. */
    public function badgeEstado(): array
    {
        if ($this->estado === 'enviada' && $this->estaVencida()) {
            return self::ESTADOS['vencida'];
        }

        return self::ESTADOS[$this->estado] ?? ['Otro', 'secondary'];
    }

    /** Genera el siguiente correlativo para el año en curso (ej. COT-2026-001). */
    public static function generarSiguienteNumero(): string
    {
        $anio = date('Y');
        $prefijo = "COT-{$anio}-";

        $ultimo = static::where('numero', 'like', "{$prefijo}%")
            ->orderByDesc('id')
            ->value('numero');

        if ($ultimo && preg_match('/COT-\d{4}-(\d+)/', $ultimo, $coincidencias)) {
            $numeroSecuencia = (int) $coincidencias[1] + 1;
        } else {
            $numeroSecuencia = 1;
        }

        return sprintf('COT-%s-%03d', $anio, $numeroSecuencia);
    }

    /** Plantilla base predeterminada para soporte y mantenimiento como la de Lunea Boutique */
    public static function plantillaSoportePredeterminada(): array
    {
        return [
            'titulo' => 'Cotización de Soporte y Mantenimiento',
            'vigencia_dias' => 30,
            'moneda' => 'GTQ',
            'proveedor_nombre' => 'Kendall Moreno',
            'proveedor_cargo' => 'Desarrollo y soporte de software',
            'objeto_descripcion' => 'Esta propuesta cubre la operación, el soporte y el mantenimiento mensual del sistema web.',
            'objeto_puntos' => [
                'Tienda en línea: catálogo, carrito, cupones, registro de clientes, checkout con comprobante de pago y seguimiento de pedidos.',
                'Punto de venta y caja: ventas en tienda física, apertura y cierre de caja.',
                'Productos e inventario: productos con variantes (talla y color), precios, movimientos de inventario, compras y proveedores.',
                'Clientes y marketing: cartera de clientes, cupones, campañas de correo y plantillas.',
                'Administración: usuarios, roles y permisos, configuración, bitácoras de actividad y de correo, reportes y dashboard.',
            ],
            'objeto_nota' => 'Los planes cubren únicamente lo que ya está construido. Los módulos nuevos y las mejoras que agregan funcionalidades se cotizan aparte (ver sección 4).',
            'planes' => [
                'columnas' => [
                    ['id' => 'basico', 'nombre' => 'Básico', 'precio' => '300', 'frecuencia' => '/ mes'],
                    ['id' => 'mantenimiento', 'nombre' => 'Mantenimiento', 'precio' => '600', 'frecuencia' => '/ mes'],
                    ['id' => 'plus', 'nombre' => 'Mantenimiento Plus', 'precio' => '750', 'frecuencia' => '/ mes'],
                ],
                'filas' => [
                    ['caracteristica' => 'Sistema publicado y en línea (despliegue y configuración del servidor)', 'valores' => ['✔', '✔', '✔']],
                    ['caracteristica' => 'Monitoreo de disponibilidad para detectar caídas', 'valores' => ['✔', '✔', '✔']],
                    ['caracteristica' => 'Restablecimiento del servicio ante caídas', 'valores' => ['✔', '✔', '✔']],
                    ['caracteristica' => 'Certificado de seguridad (HTTPS) y su renovación', 'valores' => ['✔', '✔', '✔']],
                    ['caracteristica' => 'Respaldos de la base de datos', 'valores' => ['Semanal', 'Diario', 'Diario']],
                    ['caracteristica' => 'Actualizaciones de seguridad del servidor', 'valores' => ['✔', '✔', '✔']],
                    ['caracteristica' => 'Corrección de errores (bugs) del sistema existente', 'valores' => ['—', '✔', '✔']],
                    ['caracteristica' => 'Ajustes menores sobre lo ya construido (textos, campos, validaciones, formatos, reportes)', 'valores' => ['—', '✔', '✔']],
                    ['caracteristica' => 'Actualización de versiones del framework y librerías', 'valores' => ['—', '✔', '✔']],
                    ['caracteristica' => 'Soporte a usuarios (dudas de uso por WhatsApp o correo)', 'valores' => ['—', '✔', '✔']],
                    ['caracteristica' => 'Horas de mantenimiento incluidas al mes', 'valores' => ['—', 'Hasta 6 h', 'Hasta 10 h']],
                    ['caracteristica' => 'Tiempo de respuesta ante caída del sistema', 'valores' => ['24 h', '8 h', '4 h']],
                    ['caracteristica' => 'Tiempo de respuesta en solicitudes normales', 'valores' => ['—', '48 h hábiles', '24 h hábiles']],
                    ['caracteristica' => 'Revisión mensual de rendimiento y base de datos', 'valores' => ['—', '—', '✔']],
                    ['caracteristica' => 'Capacitación a personal nuevo (1 sesión al mes)', 'valores' => ['—', '—', '✔']],
                    ['caracteristica' => 'Informe mensual de servicio', 'valores' => ['Disponibilidad', 'Disponibilidad y trabajos realizados', 'Completo']],
                ],
            ],
            'planes_nota' => '¿Cuál elegir? El plan Básico mantiene el sistema en línea, pero no incluye cambios. Los planes de Mantenimiento además corrigen errores y hacen ajustes sobre lo ya construido. El plan Plus agrega más horas, una respuesta más rápida y una revisión preventiva cada mes.',
            'mantenimiento_incluye' => [
                'Algo que ya existía dejó de funcionar o da error.',
                'Cambiar textos, etiquetas, imágenes o mensajes.',
                'Agregar o quitar un campo en un formulario existente.',
                'Ajustar una validación, un cálculo o el formato de un reporte existente.',
                'Crear usuarios o ajustar roles y permisos existentes.',
                'Actualizar librerías por seguridad o compatibilidad.',
            ],
            'desarrollo_nuevo_incluye' => [
                'Un módulo nuevo (por ejemplo, facturación electrónica FEL, apartados o lealtad).',
                'Un reporte o dashboard nuevo.',
                'Integraciones con terceros (pasarelas de pago, envíos, redes sociales, ERP).',
                'Rediseño de pantallas o de la tienda en línea.',
                'Cambiar el flujo de un proceso (ventas, checkout o inventario).',
                'Una app móvil u otro canal de venta.',
            ],
            'seccion_modulos_nuevos' => 'Todo desarrollo nuevo se cotiza por módulo o por mejora. Para cada solicitud se entrega una cotización con el alcance, el precio y el tiempo de entrega, y el trabajo empieza solo cuando el cliente la aprueba. Cuando una solicitud de mantenimiento supera las horas del plan, se avisa antes y se cotiza el excedente.',
            'condiciones' => [
                'Precios en quetzales (GTQ), pago mensual anticipado dentro de los primeros 5 días de cada mes.',
                'Los precios no incluyen los costos de infraestructura: servidor o hosting, dominio, almacenamiento de imágenes (S3) y servicio de envío de correos. Estos los paga el cliente directamente al proveedor del servicio o se facturan aparte según el consumo.',
                'Las horas de mantenimiento que no se usen no se acumulan para el mes siguiente.',
                'Los tiempos de respuesta cuentan desde que se reporta el problema por los canales acordados.',
                'Las caídas causadas por el proveedor de hosting o por terceros se atienden en coordinación con ellos, y no cuentan como incumplimiento.',
                'El cliente puede cambiar de plan o cancelar con un aviso de 30 días.',
                'Los datos y la información del sistema son propiedad del cliente.',
            ],
            'opciones_aceptacion' => [
                'Básico (Q300)',
                'Mantenimiento (Q600)',
                'Mantenimiento Plus (Q750)',
            ],
        ];
    }
}
