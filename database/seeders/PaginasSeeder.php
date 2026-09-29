<?php

namespace Database\Seeders;

use App\Models\CategoriaSistema;
use App\Models\Pagina;
use App\Models\Sistema;
use Illuminate\Database\Seeder;

/**
 * Páginas iniciales del sitio (Inicio, Nosotros, Servicios, Software, Manuales,
 * Contáctenos) y categorías del catálogo. Solo se crean si todavía no hay páginas:
 * después todo se edita desde el panel.
 */
class PaginasSeeder extends Seeder
{
    public function run(): void
    {
        $this->categorias();
        // Las páginas iniciales solo en un sitio vacío; la de privacidad, siempre que falte.
        if (! Pagina::query()->exists()) {
            $this->paginasIniciales();
        }
        $this->privacidad();
    }

    private function paginasIniciales(): void
    {
        $orden = 0;
        foreach ($this->paginas() as $datos) {
            $secciones = $datos['secciones'];
            unset($datos['secciones']);
            $pagina = Pagina::query()->create($datos + ['orden' => $orden++, 'visible' => true, 'en_menu' => true]);
            foreach ($secciones as $i => $sec) {
                $elementos = $sec['elementos'] ?? [];
                unset($sec['elementos']);
                $seccion = $pagina->secciones()->create($sec + ['orden' => $i + 1]);
                foreach ($elementos as $j => $el) {
                    $seccion->elementos()->create($el + ['orden' => $j + 1]);
                }
            }
        }
    }

    /** Política de privacidad (fuera del menú). Se crea si falta, también en sitios ya armados. */
    private function privacidad(): void
    {
        if (Pagina::query()->where('slug', Pagina::PRIVACIDAD)->exists()) {
            return;
        }
        $pagina = Pagina::query()->create([
            'titulo' => 'Política de privacidad', 'slug' => Pagina::PRIVACIDAD,
            'subtitulo' => 'Qué datos guardamos, para qué y cómo puedes pedir que los borremos.',
            'meta_descripcion' => 'Cómo Solutions GT usa y protege los datos que envías por el formulario de contacto y el chat del sitio.',
            'en_menu' => false, 'visible' => true, 'orden' => 99,
        ]);
        $pagina->secciones()->create([
            'tipo' => 'texto', 'orden' => 1, 'opciones' => ['alineacion' => 'izquierda'],
            'etiqueta' => 'Última actualización: 29 de septiembre de 2026',
            'contenido' => <<<'MD'
En Solutions GT respetamos tu privacidad. Aquí explicamos, en palabras sencillas, qué información recibimos cuando usas este sitio y qué hacemos con ella.

### Qué datos recibimos

- **Formulario de contacto, demostraciones y pruebas:** tu nombre, empresa, correo, teléfono, el sistema que te interesa y tu mensaje.
- **Chat:** tu nombre, correo y los mensajes que escribes, además de la página desde donde empezaste la conversación.
- **Datos técnicos:** la dirección IP y el tipo de navegador, que usamos solo para proteger el sitio contra abusos y envíos automáticos (spam).

No pedimos datos bancarios ni información sensible. Por favor, no los envíes por el formulario ni por el chat.

### Para qué los usamos

- Responder tus consultas y darte seguimiento.
- Enviarte la demostración o los accesos de prueba que solicites.
- Enviarte una copia de la conversación del chat, si la pides.

No vendemos ni alquilamos tus datos, y no te enviaremos publicidad sin tu permiso.

### Cookies

Este sitio usa solo **cookies necesarias** para funcionar: la de la sesión y la de seguridad de los formularios, y la del chat, que permite continuar tu conversación si cambias de página. No usamos cookies de publicidad ni de seguimiento de terceros.

### Con quién se comparten

Solo con los servicios que necesitamos para operar el sitio: el servidor donde está alojado, el almacenamiento de archivos y el servicio de correo con el que te respondemos. Ellos no pueden usar tus datos para otros fines.

### Cuánto tiempo los guardamos

- Las conversaciones del chat, hasta 6 meses después de cerradas.
- Los mensajes de contacto, mientras sean necesarios para atenderte o hasta que nos pidas borrarlos.

### Tus derechos

Puedes pedirnos en cualquier momento que te digamos qué datos tuyos tenemos, que los corrijamos o que los borremos. Escríbenos por el formulario de contacto o al correo que aparece en el sitio, y te responderemos lo antes posible.

### Cambios a esta política

Si cambiamos esta política, publicaremos la nueva versión en esta misma página con su fecha de actualización.
MD,
        ]);
    }

    private function categorias(): void
    {
        $categorias = [
            ['Restaurantes', 'restaurantes', 'fa-solid fa-utensils', ['sistema-para-restaurantes']],
            ['Comercio e inventario', 'comercio-e-inventario', 'fa-solid fa-store', ['erp-de-inventario-y-ventas']],
            ['Organizaciones', 'organizaciones', 'fa-solid fa-hand-holding-heart', ['sistema-para-ong']],
            ['Trámites y servicios', 'tramites-y-servicios', 'fa-solid fa-file-signature', ['autodmv-pro']],
        ];
        foreach ($categorias as $i => [$nombre, $slug, $icono, $sistemas]) {
            $cat = CategoriaSistema::query()->firstOrCreate(['slug' => $slug], ['nombre' => $nombre, 'icono' => $icono, 'orden' => $i + 1]);
            Sistema::query()->whereIn('slug', $sistemas)->whereNull('categoria_id')->update(['categoria_id' => $cat->id]);
        }
    }

    private function paginas(): array
    {
        $llamado = fn (string $titulo, string $texto) => [
            'tipo' => 'llamado', 'etiqueta' => 'Demostración sin compromiso', 'titulo' => $titulo, 'contenido' => $texto,
            'boton_texto' => 'Enviar un mensaje', 'boton_enlace' => '/contactenos', 'opciones' => ['whatsapp' => true],
        ];

        return [
            [
                'titulo' => 'Inicio', 'slug' => 'inicio', 'es_inicio' => true,
                'secciones' => [
                    ['tipo' => 'carrusel', 'opciones' => ['altura' => 'alta']],
                    [
                        'tipo' => 'portada', 'etiqueta' => 'Desarrollo de sistemas empresariales',
                        'titulo' => 'que ordenan tu negocio', 'opciones' => ['resaltado' => 'Sistemas'],
                        'contenido' => 'Desarrollamos sistemas web para restaurantes, comercios, organizaciones y empresas de servicios: ventas, inventario, caja, facturación electrónica y reportes, en la nube y listos para usar.',
                        'boton_texto' => 'Ver software', 'boton_enlace' => '/software',
                        'boton2_texto' => 'Hablemos', 'boton2_enlace' => '/contactenos',
                    ],
                    ['tipo' => 'clientes', 'titulo' => 'Empresas que confían en nuestro trabajo'],
                    [
                        'tipo' => 'sistemas', 'etiqueta' => 'Software', 'titulo' => 'Soluciones que ya funcionan en negocios reales',
                        'contenido' => 'Cada sistema se instala en la nube, se adapta a tu empresa y viene con capacitación y soporte.',
                        'opciones' => ['estilo' => 'filas', 'limite' => 4],
                    ],
                    ['tipo' => 'testimonios', 'etiqueta' => 'Clientes', 'titulo' => 'Lo que dicen quienes ya trabajan con nosotros'],
                    $llamado('¿Listo para ordenar tu negocio?', 'Te mostramos el sistema funcionando y vemos juntos cómo se adapta a tu empresa.'),
                ],
            ],
            [
                'titulo' => 'Nosotros', 'slug' => 'nosotros',
                'subtitulo' => 'Tecnología hecha en Guatemala para que tu negocio trabaje mejor.',
                'secciones' => [
                    [
                        'tipo' => 'texto', 'etiqueta' => 'Quiénes somos', 'titulo' => 'Sistemas pensados para el día a día',
                        'contenido' => "Somos **Solutions GT**, un equipo de desarrollo de software en Guatemala. Diseñamos y construimos sistemas web que resuelven problemas reales: controlar ventas e inventario, cobrar más rápido, facturar en línea y saber en todo momento cómo va el negocio.\n\nCada sistema está pensado para quien lo usa todos los días: pantallas claras, accesos por rol, respaldos automáticos y soporte directo.",
                        'boton_texto' => 'Conoce nuestro software', 'boton_enlace' => '/software',
                    ],
                    [
                        'tipo' => 'tarjetas', 'etiqueta' => 'Lo que nos mueve', 'titulo' => 'Misión, visión y valores', 'opciones' => ['columnas' => '3'],
                        'elementos' => [
                            ['icono' => 'fa-solid fa-bullseye', 'titulo' => 'Misión', 'texto' => 'Ayudar a los negocios a trabajar con orden y control, con sistemas sencillos de usar, seguros y a su medida.'],
                            ['icono' => 'fa-solid fa-eye', 'titulo' => 'Visión', 'texto' => 'Ser el aliado tecnológico de referencia para pequeñas y medianas empresas de la región.'],
                            ['icono' => 'fa-solid fa-handshake', 'titulo' => 'Valores', 'texto' => "Compromiso con cada cliente.\nSeguridad de la información.\nMejora continua."],
                        ],
                    ],
                    [
                        'tipo' => 'tecnologias', 'etiqueta' => 'Tecnologías', 'titulo' => 'Con qué trabajamos', 'fondo' => 'suave',
                        'contenido' => 'Herramientas modernas, probadas y con soporte a largo plazo.',
                        'elementos' => [
                            ['grupo' => 'Lenguajes', 'titulo' => 'PHP', 'icono' => 'fa-brands fa-php'],
                            ['grupo' => 'Lenguajes', 'titulo' => 'JavaScript', 'icono' => 'fa-brands fa-js'],
                            ['grupo' => 'Lenguajes', 'titulo' => 'TypeScript', 'icono' => 'fa-solid fa-code'],
                            ['grupo' => 'Lenguajes', 'titulo' => 'HTML y CSS', 'icono' => 'fa-brands fa-html5'],
                            ['grupo' => 'Frameworks', 'titulo' => 'Laravel', 'icono' => 'fa-brands fa-laravel'],
                            ['grupo' => 'Frameworks', 'titulo' => 'Vue.js', 'icono' => 'fa-brands fa-vuejs'],
                            ['grupo' => 'Frameworks', 'titulo' => 'React', 'icono' => 'fa-brands fa-react'],
                            ['grupo' => 'Frameworks', 'titulo' => 'Bootstrap', 'icono' => 'fa-brands fa-bootstrap'],
                            ['grupo' => 'Frameworks', 'titulo' => 'Tailwind CSS', 'icono' => 'fa-solid fa-wind'],
                            ['grupo' => 'Bases de datos', 'titulo' => 'MySQL', 'icono' => 'fa-solid fa-database'],
                            ['grupo' => 'Servidores y nube', 'titulo' => 'Docker', 'icono' => 'fa-brands fa-docker'],
                            ['grupo' => 'Servidores y nube', 'titulo' => 'Linux', 'icono' => 'fa-brands fa-linux'],
                            ['grupo' => 'Servidores y nube', 'titulo' => 'Almacenamiento en la nube', 'icono' => 'fa-solid fa-cloud'],
                            ['grupo' => 'Servidores y nube', 'titulo' => 'Git y GitHub', 'icono' => 'fa-brands fa-github'],
                        ],
                    ],
                    [
                        // Oculta hasta que se pongan los números reales.
                        'tipo' => 'cifras', 'visible' => false,
                        'elementos' => [
                            ['valor' => '0', 'subtitulo' => '+', 'titulo' => 'Años de experiencia'],
                            ['valor' => '4', 'subtitulo' => '', 'titulo' => 'Sistemas propios'],
                            ['valor' => '0', 'subtitulo' => '+', 'titulo' => 'Clientes'],
                            ['valor' => '0', 'subtitulo' => '+', 'titulo' => 'Proyectos entregados'],
                        ],
                    ],
                    $llamado('¿Trabajamos juntos?', 'Cuéntanos de tu negocio y te proponemos la mejor solución.'),
                ],
            ],
            [
                'titulo' => 'Servicios', 'slug' => 'servicios',
                'subtitulo' => 'Desde tu página web hasta el sistema que controla tu empresa.',
                'secciones' => [
                    [
                        'tipo' => 'tarjetas', 'etiqueta' => 'Lo que hacemos', 'titulo' => 'Servicios', 'opciones' => ['columnas' => '3'],
                        'elementos' => [
                            ['icono' => 'fa-solid fa-palette', 'titulo' => 'Diseño Web', 'texto' => 'Sitios modernos, rápidos y fáciles de administrar, con tu marca y pensados para atraer clientes.'],
                            ['icono' => 'fa-solid fa-laptop-code', 'titulo' => 'Desarrollo Web', 'texto' => 'Sistemas y aplicaciones web a la medida: inventario, ventas, caja, reportes y lo que tu empresa necesite.'],
                            ['icono' => 'fa-solid fa-mobile-screen-button', 'titulo' => 'E-commerce Responsive', 'texto' => 'Tiendas en línea que se ven y funcionan perfecto en celular, tableta y computadora.'],
                            ['icono' => 'fa-solid fa-cart-shopping', 'titulo' => 'Tiendas online personalizadas', 'texto' => 'Desarrollo de tiendas en línea a tu medida: catálogo, carrito, pagos, envíos e inventario conectado.'],
                            ['icono' => 'fa-solid fa-screwdriver-wrench', 'titulo' => 'Mantenimiento y personalización', 'texto' => 'Actualizaciones, respaldos, mejoras y ajustes para que tu sitio o sistema siempre funcione bien.'],
                            ['icono' => 'fa-solid fa-puzzle-piece', 'titulo' => 'Servicios adicionales', 'texto' => 'Hosting y dominio, correos corporativos, facturación electrónica FEL, integraciones y capacitación.'],
                        ],
                    ],
                    ['tipo' => 'planes', 'etiqueta' => 'Planes', 'titulo' => '¿Cómo podemos ayudarte?', 'fondo' => 'suave'],
                    [
                        'tipo' => 'preguntas', 'etiqueta' => 'Preguntas frecuentes', 'titulo' => 'Resolvemos tus dudas',
                        'elementos' => [
                            ['titulo' => '¿Funciona en celular y tableta?', 'texto' => 'Sí. Todos nuestros sitios y sistemas son responsive: se adaptan a cualquier pantalla.'],
                            ['titulo' => '¿Dónde quedan guardados mis datos?', 'texto' => 'En servidores en la nube, con respaldos automáticos y accesos protegidos con usuario, contraseña y verificación en dos pasos.'],
                            ['titulo' => '¿Incluyen capacitación?', 'texto' => 'Sí. Capacitamos a tu equipo y dejamos manuales de uso de cada sistema.'],
                            ['titulo' => '¿Puedo probar un sistema antes de contratarlo?', 'texto' => 'Sí. Pide una demostración o, en los sistemas que lo permiten, un acceso de prueba por varios días desde la sección Software.'],
                        ],
                    ],
                    $llamado('¿Tienes un proyecto en mente?', 'Cuéntanos qué necesitas y te enviamos una propuesta sin compromiso.'),
                ],
            ],
            [
                'titulo' => 'Software', 'slug' => 'software',
                'subtitulo' => 'Sistemas listos para usar. Pide una demostración o pruébalos gratis.',
                'secciones' => [
                    ['tipo' => 'sistemas', 'opciones' => ['estilo' => 'tarjetas', 'filtros' => true]],
                    $llamado('¿Buscas algo a la medida?', 'Si ninguno se ajusta a lo que necesitas, desarrollamos el sistema para tu empresa.'),
                ],
            ],
            [
                'titulo' => 'Manuales', 'slug' => 'manuales',
                'subtitulo' => 'Guías paso a paso para sacarle el máximo provecho a cada sistema.',
                'secciones' => [
                    ['tipo' => 'manuales'],
                ],
            ],
            [
                'titulo' => 'Contáctenos', 'slug' => 'contactenos',
                'subtitulo' => 'Escríbenos, llámanos o visítanos. Te respondemos pronto.',
                'secciones' => [
                    ['tipo' => 'contacto', 'opciones' => ['mapa' => true]],
                ],
            ],
        ];
    }
}
