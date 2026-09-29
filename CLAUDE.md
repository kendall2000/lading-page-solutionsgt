# Solutions GT — Landing page + panel

Sitio público para presentar al usuario y sus sistemas, con un panel (`/admin`) para administrar todo.
**Sistema ajeno e independiente de `sistema-restaurante`** (lo recalcó el usuario): no comparte código, base,
sesiones ni despliegue con él, y al trabajar aquí no se toca el restaurante ni se asumen sus reglas. El
restaurante solo sirvió de modelo inicial (estructura, módulo de Correos, Docker); lo que se tomó ya vive aquí.
`C:\laragon\www\landing-page` **no es del usuario**: no usarla como referencia.

## Cómo trabajar con el usuario

- Responder **siempre en español**, claro y sin jerga; al final de cada tarea explicar qué cambió, por qué y qué falta.
- **No compilar ni levantar servidores en su computadora** (nada de `npm`, `php artisan serve`, Docker local).
  Verificar con `php artisan test` (antes, `php artisan config:clear`: con la config cacheada las pruebas
  borrarían la base real).
- Decisiones de negocio o de contenido (textos, precios, qué se publica): preguntar con opciones y una recomendada.

## Repositorio

- Privado en GitHub: `git@github.com:kendall2000/lading-page-solutionsgt.git` (rama `main`, por SSH; no hay `gh`).
- Commits en español con el formato `tipo(módulo): descripción` (p. ej. `feat(sistemas): …`, `fix(correos): …`),
  cuerpo explicando el porqué. El `.env` nunca se sube (credenciales de BD y Contabo).

## Stack

- Laravel 13 + Fortify (login, recuperar contraseña, verificación en dos pasos) · PHP 8.4 · MySQL.
- Vistas Blade con la plantilla **Phoenix** (Bootstrap 5), sin npm/Vite: CSS/JS ya compilados en
  `public/assets` y `public/vendors` (copiados solo los que se usan).
- Español (`lang/es`), zona `America/Guatemala`, MySQL de sesión en `-06:00`.

## Plantilla de diseño (obligatoria)

- Fuente: `public/Plantilla/public/` de este proyecto (copia local idéntica a la del restaurante; fuera de git
  y de Docker). El sitio público sale de
  `pages/landing/alternate.html`; el panel, del layout del restaurante).
- Buscar la pantalla/componente parecido, copiar su HTML a Blade y traducir. Si hace falta un archivo nuevo
  de la plantilla, copiarlo a `public/assets/...` o `public/vendors/...` y enlazarlo con `asset()`.
- **Peligro**: `Plantilla/public/CONFIG` y `resto/js` no son de Phoenix (credenciales de otro sistema): no abrir ni copiar.
- **No** incluir `polyfill.io` (dominio comprometido). El mapa de Google de la plantilla (con API key ajena)
  se reemplazó por un `iframe` de Google Maps sin llave.

## Base de datos

- BD propia `solutionsgt` en `31.220.77.136` (mismo servidor que los otros sistemas; **no tocar** otras bases).
  El `.env` local apunta a esa BD real: `migrate` y `db:seed` escriben ahí.
- Tablas: `users`, `configuracion_sitio` (una fila), `paginas`, `secciones`, `elementos`, `categorias_sistema`, `manuales`, `configuracion_correo`, `plantillas_correo`, `bitacora_correos`, `sistemas`, `sistema_imagenes`, `servicios` (planes), `clientes`
  (incluye logo y testimonio), `direcciones`, `mensajes_contacto` + las de Laravel (`sessions`, `cache`, `jobs`…).
- `DatabaseSeeder` solo crea lo que falta (no pisa lo editado en el panel). El usuario inicial sale de
  `ADMIN_EMAIL` / `ADMIN_PASSWORD` o se genera y se muestra una sola vez.

## Imágenes (Contabo)

- Disco `contabo` (S3 compatible, `use_path_style_endpoint`) con **bucket propio** `<id>:lading-page-solutionsgt`
  (`CONTABO_CARPETA` vacío). Siempre con `App\Support\Imagenes` (subir, url, borrar).
- Rutas que empiezan con `assets/` son imágenes locales de la plantilla.

## Correos (nada en el .env)

- Igual que el módulo del restaurante: Panel → Correos guarda el servidor SMTP en `configuracion_correo`
  (contraseña cifrada con APP_KEY), las plantillas en `plantillas_correo` y cada intento en `bitacora_correos`.
  `CorreoServiceProvider` aplica el SMTP al arrancar; cambiarlo no requiere tocar archivos ni reconstruir.
- Enviar siempre con `App\Services\Correos` (`enviar`, `avisar` a «Avisos a»). Plantillas del sitio:
  `nuevo_mensaje`, `confirmacion_contacto`, `recuperar_contrasena` (no se borran ni cambia su código).
- Pedido del usuario (2026-09-28): **nada de configuración de correo quemada en el .env**.

## Chat en vivo (visitante ↔ panel)

- Widget flotante de la plantilla (`support-chat`) en todo el sitio (`publico/partes/chat`), bandeja en
  `/admin/chat` (diseño `apps/chat.html`) y aviso (toast + contador) en todo el panel.
- Tablas `conversaciones` (visitante identificado por cookie cifrada `chat_visitante`; en la base solo el
  sha256 del token) y `mensajes_chat`. Guardar/enviar siempre con `App\Services\Chat`.
- Tiempo real con **Laravel Reverb** (contenedor `solutionsgt-reverb`, puerto público 8098): evento
  `MensajeEnviado` (ShouldBroadcastNow, sin cola) por canales privados `chat.conversacion.{id}` y `chat.panel`.
  El panel se autoriza en `/broadcasting/auth` (`routes/channels.php`); el visitante en `POST /chat/auth`
  comprobando su cookie. Si Reverb no responde, el mensaje queda guardado y ambos lados consultan cada pocos
  segundos (respaldo). Echo y pusher-js compilados en `public/vendors` (sin npm).
- `.env`: `REVERB_HOST/PORT` = cómo el sitio habla con Reverb (red interna); `REVERB_PUBLICO_*` = cómo se
  conecta el navegador. Aviso de chat nuevo por correo con la plantilla `nuevo_chat`.
- En pruebas el broadcaster es `null`: las JSON con cookie necesitan `withCredentials()`.

## Estructura

- **Sitio armado por páginas** (pedido del usuario): `paginas` (menú, con submenú de un nivel vía `padre_id`;
  una es `es_inicio`) → `secciones` (bloques; tipo, textos en Markdown seguro, imagen, botones, `opciones` JSON)
  → `elementos` (repetibles: fotos del carrusel, tarjetas, tecnologías, cifras, preguntas).
  Catálogo de tipos en `App\Support\Bloques` (qué campos/opciones/elementos pide cada uno); la vista pública de
  cada tipo es `resources/views/publico/bloques/{tipo}.blade.php`. Para un tipo nuevo: agregarlo en `Bloques`,
  crear su vista y, si usa datos de otra tabla, cargarlos en `SitioController::datos()`.
- Rutas públicas: `/` (página de inicio), `/sistemas/{slug}`, `/manuales/{slug}`, `POST /contacto` y al final
  `/{pagina:slug}`. Las direcciones de `Pagina::RESERVADAS` no se pueden usar como página.
- Software: `sistemas` con `categoria_id`, `modalidad` (gratis/premium/a_medida), `precio`, `acepta_demo`,
  `acepta_prueba` y `dias_prueba`. El formulario (`publico/partes/formulario`) guarda en `mensajes_contacto` con
  `tipo` contacto/demo/prueba; desde el panel se envían credenciales de prueba (clave cifrada) con la plantilla
  `credenciales_prueba`. El usuario de prueba se crea a mano en el sistema correspondiente.
- Manuales: `manuales` (Markdown, PDF en Contabo, video YouTube/Vimeo), por sistema.
- Enlaces escritos en el panel: solo `https://`, `/`, `#`, `mailto:`, `tel:` (`Bloques::REGLA_ENLACE`).
- Público: `SitioController`. Vistas en `resources/views/publico` + `layouts/publico` (menú desde `Sitio::menu()`).
- Panel: `app/Http/Controllers/Admin/*`, vistas en `resources/views/admin` + `layouts/admin`.
  Cualquier usuario activo puede entrar al panel (no hay roles).
- **Configuración del sistema** (`/admin/sistema/configuracion`, como el módulo del restaurante): pestañas
  Empresa, Logo e íconos (logo, logo oscuro, favicon, fondo del acceso), Fotos de inicio (sube/quita fotos del
  carrusel de la página de inicio e imagen de la portada), Colores (principal y secundario), Redes, Chat e
  Inicio de sesión. En caché: `App\Support\Sitio::config()` (solo el arreglo de columnas; Laravel 13 no
  deserializa objetos de la caché); llamar `Sitio::olvidar()` al guardarla.
- En Blade, las variables de la vista hija pasan al layout: no usar `$titulo`, `$cfg`, `$base` como variables de ciclo.

## Despliegue (Docker)

- `Dockerfile`, `docker-compose.yml`, `docker-entrypoint.sh` adaptados del restaurante: contenedor
  `solutionsgt-php`, puerto **8097**, red externa `red-external-network`, volumen solo para `storage`.
- `.env` de producción: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con el dominio,
  `SESSION_SECURE_COOKIE=true` con HTTPS. El correo **no** va en el .env (ver «Correos»).
  `APP_KEY` no se cambia una vez en uso.
