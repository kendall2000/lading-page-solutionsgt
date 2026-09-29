# Solutions GT — Landing page + panel

Sitio público para presentar al usuario y sus sistemas, con un panel (`/admin`) para administrar todo.
Independiente de los demás sistemas; toma como modelo `C:\laragon\www\sistema-restaurante`.
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

- Fuente: `C:\laragon\www\sistema-restaurante\public\Plantilla\public\` (el sitio público sale de
  `pages/landing/alternate.html`; el panel, del layout del restaurante).
- Buscar la pantalla/componente parecido, copiar su HTML a Blade y traducir. Si hace falta un archivo nuevo
  de la plantilla, copiarlo a `public/assets/...` o `public/vendors/...` y enlazarlo con `asset()`.
- **Peligro**: `Plantilla/public/CONFIG` y `resto/js` no son de Phoenix (credenciales de otro sistema): no abrir ni copiar.
- **No** incluir `polyfill.io` (dominio comprometido). El mapa de Google de la plantilla (con API key ajena)
  se reemplazó por un `iframe` de Google Maps sin llave.

## Base de datos

- BD propia `solutionsgt` en `31.220.77.136` (mismo servidor que los otros sistemas; **no tocar** otras bases).
  El `.env` local apunta a esa BD real: `migrate` y `db:seed` escriben ahí.
- Tablas: `users`, `configuracion_sitio` (una fila), `configuracion_correo`, `plantillas_correo`, `bitacora_correos`, `sistemas`, `sistema_imagenes`, `servicios`, `clientes`
  (incluye logo y testimonio), `direcciones`, `mensajes_contacto` + las de Laravel (`sessions`, `cache`, `jobs`…).
- `DatabaseSeeder` solo crea lo que falta (no pisa lo editado en el panel). El usuario inicial sale de
  `ADMIN_EMAIL` / `ADMIN_PASSWORD` o se genera y se muestra una sola vez.

## Imágenes (Contabo)

- Disco `contabo` (S3 compatible, `use_path_style_endpoint`), mismas credenciales que el restaurante,
  mismo bucket, carpeta `CONTABO_CARPETA=solutionsgt`. Siempre con `App\Support\Imagenes` (subir, url, borrar).
- Rutas que empiezan con `assets/` son imágenes locales de la plantilla.

## Correos (nada en el .env)

- Igual que el módulo del restaurante: Panel → Correos guarda el servidor SMTP en `configuracion_correo`
  (contraseña cifrada con APP_KEY), las plantillas en `plantillas_correo` y cada intento en `bitacora_correos`.
  `CorreoServiceProvider` aplica el SMTP al arrancar; cambiarlo no requiere tocar archivos ni reconstruir.
- Enviar siempre con `App\Services\Correos` (`enviar`, `avisar` a «Avisos a»). Plantillas del sitio:
  `nuevo_mensaje`, `confirmacion_contacto`, `recuperar_contrasena` (no se borran ni cambia su código).
- Pedido del usuario (2026-09-28): **nada de configuración de correo quemada en el .env**.

## Estructura

- Público: `SitioController` (portada, `/sistemas/{slug}`, `POST /contacto` con campo trampa y límite 5/10 min).
  Vistas en `resources/views/publico` + `layouts/publico`.
- Panel: `app/Http/Controllers/Admin/*`, vistas en `resources/views/admin` + `layouts/admin`.
  Cualquier usuario activo puede entrar al panel (no hay roles).
- Configuración del sitio en caché: `App\Support\Sitio::config()`; llamar `Sitio::olvidar()` al guardarla.
- En Blade, las variables de la vista hija pasan al layout: no usar `$titulo`, `$cfg`, `$base` como variables de ciclo.

## Despliegue (Docker)

- `Dockerfile`, `docker-compose.yml`, `docker-entrypoint.sh` adaptados del restaurante: contenedor
  `solutionsgt-php`, puerto **8097**, red externa `red-external-network`, volumen solo para `storage`.
- `.env` de producción: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con el dominio,
  `SESSION_SECURE_COOKIE=true` con HTTPS. El correo **no** va en el .env (ver «Correos»).
  `APP_KEY` no se cambia una vez en uso.
