<?php

namespace Database\Seeders;

use App\Models\CategoriaSistema;
use App\Models\Pagina;
use App\Models\Sistema;
use Illuminate\Database\Seeder;

/**
 * Más sistemas del catálogo (pedido del usuario, 2026-09-28), descritos a partir de
 * los módulos reales de cada proyecto en C:\laragon\www. Solo crea lo que falta:
 * no pisa lo que ya se editó en el panel.
 */
class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = $this->categorias();
        $orden = (int) Sistema::query()->max('orden');

        foreach ($this->sistemas() as $slug => $datos) {
            $categoria = $datos['categoria'];
            unset($datos['categoria']);
            Sistema::query()->firstOrCreate(['slug' => $slug], $datos + [
                'categoria_id' => $categorias[$categoria] ?? null,
                'modalidad' => 'premium', 'acepta_demo' => true, 'acepta_prueba' => false, 'dias_prueba' => 15,
                'visible' => true, 'orden' => ++$orden,
            ]);
        }

        $this->serviciosDeIntegracion();
    }

    /** @return array<string, int> slug => id */
    private function categorias(): array
    {
        $lista = [
            'gestion-empresarial' => ['Gestión empresarial', 'fa-solid fa-building'],
            'belleza-y-bienestar' => ['Belleza y bienestar', 'fa-solid fa-spa'],
            'educacion' => ['Educación', 'fa-solid fa-graduation-cap'],
            'condominios' => ['Condominios', 'fa-solid fa-city'],
            'transporte' => ['Transporte', 'fa-solid fa-bus'],
            'sitios-web' => ['Sitios web', 'fa-solid fa-globe'],
        ];
        $orden = (int) CategoriaSistema::query()->max('orden');
        foreach ($lista as $slug => [$nombre, $icono]) {
            CategoriaSistema::query()->firstOrCreate(['slug' => $slug], ['nombre' => $nombre, 'icono' => $icono, 'orden' => ++$orden]);
        }

        return CategoriaSistema::query()->pluck('id', 'slug')->all();
    }

    private function sistemas(): array
    {
        return [
            'nexus-erp' => [
                'nombre' => 'Nexus ERP', 'categoria' => 'gestion-empresarial', 'icono' => 'fa-solid fa-diagram-project', 'destacado' => true,
                'resumen' => 'ERP completo para empresas en crecimiento: CRM y ventas, finanzas y facturación, inventario en bodegas, recursos humanos y nómina, para varias empresas y sucursales.',
                'descripcion' => "Toda la operación de tu empresa en un solo sistema: desde el primer contacto con un cliente hasta la factura, el inventario y el pago de la planilla.\nTrabaja con varias empresas, sucursales y líneas de negocio, con permisos por rol y auditoría de cada cambio.",
                'caracteristicas' => "CRM: prospectos, embudo de ventas, oportunidades y propuestas\nTickets de soporte con SLA y encuestas de satisfacción\nCampañas y metas de venta\nFacturación, pagos y series de facturación\nCuentas contables, centros de costo y presupuesto anual\nInventario por bodegas, órdenes de compra y recepción de mercadería\nRecursos humanos: empleados, contratos y documentos\nNómina, prestaciones, préstamos, asistencia y ausencias\nVarias empresas y sucursales\nAuditoría de accesos y cambios",
                'tecnologias' => 'Laravel 13, Vue 3, Tailwind CSS, MySQL',
            ],
            'chatea-con-tu-empresa' => [
                'nombre' => 'Chatea con tu Empresa (IA)', 'categoria' => 'gestion-empresarial', 'icono' => 'fa-solid fa-robot', 'destacado' => true,
                'resumen' => 'Asistente con inteligencia artificial que conversa contigo sobre tu negocio: te dice cómo van las ventas, envía correos y te informa lo que necesitas saber.',
                'descripcion' => "Pregúntale a tu empresa como le preguntarías a una persona: «¿cuánto vendimos hoy?», «¿qué cliente me debe?», «envíale el estado de cuenta a…».\nEl asistente consulta tus datos y te responde en segundos, sin reportes complicados.",
                'caracteristicas' => "Conversación en lenguaje natural\nConsulta de ventas y resultados del negocio\nEnvío de correos desde el chat\nResúmenes e información de tu empresa al instante\nConectado a los datos de tus sistemas",
                'tecnologias' => 'Laravel, Inteligencia artificial, MySQL',
            ],
            'sistema-para-salones-de-belleza' => [
                'nombre' => 'Sistema para Salones de Belleza', 'categoria' => 'belleza-y-bienestar', 'icono' => 'fa-solid fa-spa',
                'resumen' => 'Citas y reservas con horarios, servicios, empleadas y comisiones, caja, venta de productos, programa de fidelidad y recordatorios automáticos. Ideal para salones de uñas y belleza.',
                'descripcion' => "Organiza la agenda de tu salón y deja de perder citas: tus clientas reservan, el sistema asigna horarios según cada especialista y les recuerda su cita por correo.\nAl cobrar, calcula las comisiones de cada empleada y lleva el control de caja, productos e inventario.",
                'caracteristicas' => "Reservas y citas con horarios por especialista\nRecordatorios automáticos por correo\nServicios con precios, modalidades y catálogo de diseños\nEmpleadas, especialidades, horarios y comisiones\nCaja, ventas y métodos de pago\nProductos, compras, proveedores e inventario\nProgramas de fidelidad, niveles y logros de clientas\nCupones de descuento\nAsistente con inteligencia artificial\nRoles, permisos y respaldos",
                'tecnologias' => 'Laravel 13, Vue 3, Inertia, Tailwind CSS, MySQL',
            ],
            'sistema-para-boutiques' => [
                'nombre' => 'Sistema para Boutiques', 'categoria' => 'comercio-e-inventario', 'icono' => 'fa-solid fa-shirt',
                'resumen' => 'Punto de venta y tienda en línea en un solo sistema: inventario por talla y color, ventas en tienda con escáner, pedidos en línea, cupones y campañas por correo.',
                'descripcion' => "Vende en tu tienda física y en internet con el mismo inventario: lo que se vende en un lado se descuenta en el otro.\nEl punto de venta trabaja con escáner de código QR o SKU, y la tienda en línea tiene catálogo, carrito y cuenta para tus clientas.",
                'caracteristicas' => "Tienda en línea: catálogo, carrito y pedidos\nPunto de venta con escáner QR / SKU\nProductos con variantes de talla y color\nInventario, compras y proveedores\nCaja con sesiones y métodos de pago\nCupones de descuento\nCampañas de correo a clientas\nReportes de ventas",
                'tecnologias' => 'Laravel 13, Vue 3, Inertia, Tailwind CSS, MySQL',
            ],
            'sistema-para-ferreterias' => [
                'nombre' => 'Sistema para Ferreterías', 'categoria' => 'comercio-e-inventario', 'icono' => 'fa-solid fa-screwdriver-wrench',
                'resumen' => 'Punto de venta e inventario para ferreterías y comercios con muchos productos: varias bodegas, compras, cotizaciones, devoluciones, gastos y planilla.',
                'descripcion' => "Pensado para negocios con miles de productos: control por bodega, traslados, conteos de inventario y ventas rápidas en caja.\nIncluye cotizaciones, crédito a clientes, gastos y el control del personal.",
                'caracteristicas' => "Punto de venta (POS)\nInventario en varias bodegas y traslados\nConteo y ajustes de inventario\nProductos con variantes, marcas y unidades\nCompras, proveedores y devoluciones\nCotizaciones y entregas\nClientes, grupos de clientes y tarjetas de regalo\nGastos, cuentas y transferencias\nEmpleados, asistencia y planilla",
                'tecnologias' => 'Laravel, MySQL, Bootstrap',
            ],
            'sistema-para-condominios' => [
                'nombre' => 'Sistema para Condominios', 'categoria' => 'condominios', 'icono' => 'fa-solid fa-city',
                'resumen' => 'Administración de condominios: unidades y residentes, cuotas, pagos y multas, áreas comunes y reservas, correspondencia, personal y finanzas del condominio.',
                'descripcion' => "La administración del condominio ordenada y transparente: cuotas generadas automáticamente, pagos al día, multas y un presupuesto claro.\nLos residentes reservan áreas comunes, reciben avisos y reportan incidencias.",
                'caracteristicas' => "Condominios, unidades y residentes\nVehículos y mascotas registrados\nGeneración de cuotas y control de pagos\nMultas e infracciones\nEgresos, proveedores y presupuesto\nÁreas comunes y reservas con autorización\nCorrespondencia y documentos\nEventos, avisos y tareas\nPersonal del condominio, activos e inventario\nVideollamada y reportes de residentes",
                'tecnologias' => 'PHP, MySQL, JavaScript',
            ],
            'sistema-escolar' => [
                'nombre' => 'Sistema Escolar', 'categoria' => 'educacion', 'icono' => 'fa-solid fa-graduation-cap', 'proximamente' => true, 'acepta_demo' => false,
                'resumen' => 'Gestión de colegios y centros educativos: periodos, grupos, asignaturas y salones, avisos a padres y alumnos, clases por videollamada y reportes.',
                'descripcion' => "Un sistema para ordenar la vida académica del colegio: periodos, grupos, cursos y salones en un solo lugar, con comunicación directa con padres y alumnos.\nEstamos terminando su desarrollo: déjanos tus datos y te avisamos en cuanto esté disponible.",
                'caracteristicas' => "Periodos académicos\nGrupos, asignaturas y salones\nAvisos y notificaciones\nMensajes por SMS\nClases por videollamada\nReportes\nUsuarios y accesos por perfil",
                'tecnologias' => 'PHP, MySQL, JavaScript',
            ],
            'sistema-proteccion-civil' => [
                'nombre' => 'Sistema de Protección Civil', 'categoria' => 'organizaciones', 'icono' => 'fa-solid fa-truck-medical',
                'resumen' => 'Registro de eventos y emergencias con informes detallados: vehículos, voluntarios, embarcaciones, generadores, radios y equipo, con reportes en PDF.',
                'descripcion' => "Pensado para cuerpos de socorro y protección civil: cada evento queda documentado con quién participó, qué recursos se usaron y dónde.\nLos informes se generan en PDF listos para presentar, y los voluntarios pueden inscribirse en línea.",
                'caracteristicas' => "Registro de eventos y emergencias\nInformes preliminares y finales\nVehículos, embarcaciones, generadores y radios por informe\nVoluntarios e inscripciones\nEquipamiento e insumos\nLugares y departamentos\nNoticias\nReportes en PDF\nUsuarios con roles y permisos",
                'tecnologias' => 'PHP, MySQL, TCPDF',
            ],
            'sistema-de-transporte-urban' => [
                'nombre' => 'Sistema de Transporte (Buses)', 'categoria' => 'transporte', 'icono' => 'fa-solid fa-bus',
                'resumen' => 'Venta de boletos y reservaciones, horarios y turnos, unidades y socios, paquetería, caja y cortes, con facturación electrónica FEL (INFILE y otros certificadores de la SAT).',
                'descripcion' => "Para empresas de buses y transporte de pasajeros: vende boletos, controla las reservaciones y asigna unidades y turnos a cada horario.\nNueva versión mejorada, con facturación electrónica FEL: trabajamos con INFILE y podemos integrar otros certificadores autorizados por la SAT.",
                'caracteristicas' => "Venta de boletos y reservaciones\nDestinos, horarios y asignación de turnos\nUnidades (buses) y socios\nPaquetería y encomiendas\nCaja, cortes de caja y arqueos\nDescuentos y transferencias\nFacturación electrónica FEL con INFILE y otros certificadores SAT\nBitácora y roles de usuario",
                'tecnologias' => 'PHP, MySQL, FEL (SAT)',
            ],
            'sitio-web-para-tu-empresa' => [
                'nombre' => 'Sitio Web para tu Empresa', 'categoria' => 'sitios-web', 'icono' => 'fa-solid fa-globe', 'modalidad' => 'a_medida',
                'resumen' => 'Tu página web profesional y administrable: páginas y menú que tú editas, catálogo de productos o servicios, formularios, chat en vivo con tus clientes y avisos por correo.',
                'descripcion' => "El mismo sistema con el que está hecho este sitio, con tu marca: cambias textos, fotos y páginas desde un panel, sin depender de nadie.\nIncluye chat en vivo para atender a tus clientes en tiempo real y formularios que te avisan por correo.",
                'caracteristicas' => "Páginas y menú que tú administras\nSecciones: carrusel de fotos, servicios, tecnologías, galería, preguntas y más\nCatálogo de productos o servicios\nFormularios de contacto, demostración y prueba\nChat en vivo con tus visitantes\nCorreos con plantillas y servidor configurable\nManuales y descargas\nDiseño adaptable a celular y modo oscuro\nPanel seguro con verificación en dos pasos",
                'tecnologias' => 'Laravel 13, Bootstrap 5, MySQL, Laravel Reverb',
            ],
        ];
    }

    /** Tarjetas de SAP y FEL en la página Servicios (si no están). */
    private function serviciosDeIntegracion(): void
    {
        $seccion = Pagina::query()->where('slug', 'servicios')->first()?->secciones()->where('tipo', 'tarjetas')->first();
        if (! $seccion) {
            return;
        }
        $tarjetas = [
            [
                'icono' => 'fa-solid fa-plug', 'titulo' => 'Integración con SAP Business One',
                'texto' => 'Conectamos tus sistemas con SAP Business One mediante DI API o Service Layer: pedidos, facturas, clientes e inventario sincronizados sin digitar dos veces.',
            ],
            [
                'icono' => 'fa-solid fa-file-invoice', 'titulo' => 'Facturación electrónica FEL',
                'texto' => 'Integramos la factura electrónica de la SAT en tus sistemas: trabajamos con INFILE y podemos conectar otros certificadores autorizados.',
            ],
        ];
        $orden = (int) $seccion->elementos()->max('orden');
        foreach ($tarjetas as $t) {
            $seccion->elementos()->firstOrCreate(['titulo' => $t['titulo']], $t + ['orden' => ++$orden]);
        }
    }
}
