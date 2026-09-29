<?php

namespace Database\Seeders;

use App\Models\ConfiguracionSitio;
use App\Models\Servicio;
use App\Models\Sistema;
use App\Models\User;
use App\Support\Sitio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Contenido inicial del sitio. Se puede correr varias veces: no duplica ni pisa
 * lo que ya se editó en el panel (solo crea lo que falta).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->administrador();
        $this->configuracion();
        $this->sistemas();
        $this->servicios();
        $this->call(PaginasSeeder::class);
        $this->call(CatalogoSeeder::class);
        Sitio::olvidar();
    }

    private function administrador(): void
    {
        if (User::query()->exists()) {
            return;
        }
        $correo = Str::lower((string) env('ADMIN_EMAIL', 'admin@solutionsgt.com'));
        $clave = (string) (env('ADMIN_PASSWORD') ?: Str::password(14, symbols: false));
        User::query()->create(['name' => 'Administrador', 'email' => $correo, 'password' => $clave, 'is_active' => true]);
        $this->command?->warn("Usuario del panel: {$correo} / contraseña: {$clave}  (cámbiala al entrar)");
    }

    private function configuracion(): void
    {
        if (ConfiguracionSitio::query()->exists()) {
            return;
        }
        ConfiguracionSitio::query()->create([
            'nombre' => 'Solutions GT',
            'eslogan' => 'Desarrollo de sistemas empresariales',
            'horario' => 'Lunes a viernes, 8:00 a 17:00',
            'meta_descripcion' => 'Solutions GT: sistemas web para restaurantes, inventario, ONG y agencias de trámites. Ventas, caja, inventario, facturación electrónica FEL y reportes en la nube.',
        ]);
    }

    private function sistemas(): void
    {
        $sistemas = [
            [
                'nombre' => 'Sistema para Restaurantes',
                'icono' => 'fa-solid fa-utensils',
                'destacado' => true,
                'resumen' => 'Punto de venta completo para restaurantes: mesas, pedidos, cocina, caja, inventario con recetas y factura electrónica FEL, para una o varias sucursales.',
                'descripcion' => "Controla todo tu restaurante desde una sola pantalla: los meseros toman pedidos en el salón, la cocina los recibe al instante por estación y la caja cobra con cualquier método de pago.\nEl inventario se descuenta solo según las recetas de cada platillo, y al final del día tienes cortes de caja, ventas por producto y reportes por sucursal.",
                'caracteristicas' => "Salón con áreas y mesas, unir y transferir cuentas\nPedidos para comer aquí, llevar, domicilio y apps de delivery\nPantalla de cocina por estación y comandas impresas\nCaja con turnos, cortes, ingresos y egresos\nMenú con modificadores, combos y precios por sucursal\nInventario con recetas, compras, mermas y kardex\nFactura electrónica FEL (Guatemala)\nOfertas automáticas: 2x1, descuentos por horario y por tarjeta\nCarta digital con código QR\nVarias sucursales con reportes consolidados\nRoles y permisos, verificación en dos pasos\nRespaldos automáticos diarios",
                'tecnologias' => 'Laravel, PHP 8.4, MySQL, Bootstrap, Docker',
            ],
            [
                'nombre' => 'ERP de Inventario y Ventas',
                'icono' => 'fa-solid fa-boxes-stacked',
                'resumen' => 'ERP para comercios y distribuidoras: inventario en varios almacenes, punto de venta, compras, cotizaciones, cuentas por cobrar y por pagar.',
                'descripcion' => "Pensado para negocios que venden productos y necesitan saber exactamente qué tienen, dónde está y cuánto les deben.\nDesde el punto de venta hasta las cuentas por cobrar, toda la información queda conectada y al día.",
                'caracteristicas' => "Inventario en varios almacenes y traslados\nPunto de venta (POS)\nCompras a proveedores\nCotizaciones que se convierten en ventas\nCuentas por cobrar y por pagar\nCaja y movimientos\nClientes y proveedores\nUsuarios con roles y permisos\nReportes de ventas e inventario",
                'tecnologias' => 'Laravel, React, TypeScript, MySQL, Tailwind',
            ],
            [
                'nombre' => 'Sistema para ONG',
                'icono' => 'fa-solid fa-hand-holding-heart',
                'resumen' => 'Gestión integral para organizaciones sin fines de lucro: proyectos, donantes y donaciones, beneficiarios, entregas de ayuda, voluntarios e inventario.',
                'descripcion' => "Transparencia de principio a fin: cada donación en especie entra automáticamente al inventario y cada entrega a un beneficiario lo descuenta, con su constancia de entrega.\nIdeal para rendir cuentas a donantes y llevar el control de cada proyecto o programa.",
                'caracteristicas' => "Proyectos y programas\nDonantes y donaciones monetarias y en especie\nBeneficiarios y entregas de ayuda con constancia\nVoluntarios\nInventario en varios almacenes\nCompras y proveedores\nCaja y punto de venta\nUsuarios con roles y permisos",
                'tecnologias' => 'Laravel, Vue 3, TypeScript, MySQL, Tailwind',
            ],
            [
                'nombre' => 'AutoDMV Pro',
                'icono' => 'fa-solid fa-car',
                'resumen' => 'Sistema multi-empresa y bilingüe para agencias de trámites vehiculares del DMV: títulos, registros, placas, poderes notariales y rentas.',
                'descripcion' => "Hecho para agencias que ayudan a sus clientes con trámites de vehículos ante el DMV de Carolina del Sur.\nVarias agencias trabajan en una misma instalación, cada una con sus datos, y el sistema genera los formularios oficiales en PDF listos para imprimir.",
                'caracteristicas' => "Varias agencias en una sola instalación\nBilingüe: inglés y español\nTítulos, registros y placas\nCambios de dirección y poderes notariales\nFormularios oficiales en PDF\nMódulo de rentas\nClientes y vehículos\nEn producción con Docker",
                'tecnologias' => 'Laravel, Vue, MySQL, Docker',
            ],
        ];

        foreach ($sistemas as $i => $datos) {
            Sistema::query()->firstOrCreate(
                ['slug' => Str::slug($datos['nombre'])],
                $datos + ['orden' => $i + 1, 'visible' => true],
            );
        }
    }

    private function servicios(): void
    {
        if (Servicio::query()->exists()) {
            return;
        }
        $servicios = [
            [
                'nombre' => 'Implementación',
                'descripcion' => 'Uno de mis sistemas funcionando en tu negocio.',
                'incluye' => "Instalación en la nube con tu dominio\nConfiguración a la medida de tu negocio\nCarga inicial de productos, clientes e inventario\nCapacitación a tu equipo\nAcompañamiento en el arranque",
            ],
            [
                'nombre' => 'Desarrollo a la medida',
                'descripcion' => 'Un sistema nuevo, hecho para cómo trabaja tu empresa.',
                'incluye' => "Análisis de tus procesos\nDiseño de pantallas y reportes\nIntegraciones: FEL, pagos, apps de delivery\nEntregas por etapas para ver avances\nCódigo y datos respaldados",
                'destacado' => true,
            ],
            [
                'nombre' => 'Soporte y mantenimiento',
                'descripcion' => 'Tu sistema siempre al día y funcionando.',
                'incluye' => "Soporte directo por WhatsApp\nRespaldos automáticos\nActualizaciones de seguridad\nMejoras y ajustes pequeños\nMonitoreo del servidor",
            ],
        ];
        foreach ($servicios as $i => $datos) {
            Servicio::query()->create($datos + ['orden' => $i + 1, 'visible' => true]);
        }
    }
}
