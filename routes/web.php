<?php

use App\Http\Controllers\Admin\BitacoraController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\CorreoController;
use App\Http\Controllers\Admin\CuentaController;
use App\Http\Controllers\Admin\DireccionController;
use App\Http\Controllers\Admin\ElementoController;
use App\Http\Controllers\Admin\EstadisticaController;
use App\Http\Controllers\Admin\InicioController;
use App\Http\Controllers\Admin\ManualController;
use App\Http\Controllers\Admin\MensajeController;
use App\Http\Controllers\Admin\PaginaController;
use App\Http\Controllers\Admin\SeccionController;
use App\Http\Controllers\Admin\ServicioController;
use App\Http\Controllers\Admin\SistemaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SitioController;
use Illuminate\Support\Facades\Route;

// Sitio público.
// «visita»: contador de visitas propio (App\Services\Visitas).
Route::get('/', [SitioController::class, 'inicio'])->middleware('visita')->name('inicio');
Route::get('sistemas/{sistema:slug}', [SitioController::class, 'sistema'])->middleware('visita')->name('sistema');
Route::get('manuales/{manual:slug}', [SitioController::class, 'manual'])->middleware('visita')->name('manual');
Route::post('contacto', [SitioController::class, 'contacto'])->middleware('throttle:8,10')->name('contacto');

// Para buscadores (se arman solos con lo publicado).
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');

// Chat en vivo del visitante (widget). Se identifica con una cookie; ver ChatController.
Route::prefix('chat')->name('chat.')->group(function () {
    Route::get('estado', [ChatController::class, 'estado'])->middleware('throttle:60,1')->name('estado');
    Route::post('iniciar', [ChatController::class, 'iniciar'])->middleware('throttle:5,10')->name('iniciar');
    Route::get('mensajes', [ChatController::class, 'mensajes'])->middleware('throttle:120,1')->name('mensajes');
    Route::post('mensajes', [ChatController::class, 'enviar'])->middleware('throttle:30,1')->name('enviar');
    Route::post('auth', [ChatController::class, 'autorizar'])->middleware('throttle:60,1')->name('auth');
    Route::post('leer', [ChatController::class, 'leer'])->middleware('throttle:120,1')->name('leer');
    Route::post('copia', [ChatController::class, 'copia'])->middleware('throttle:3,10')->name('copia');
    Route::post('salir', [ChatController::class, 'salir'])->middleware('throttle:20,1')->name('salir');
});

// Panel de administración.
Route::middleware(['auth', 'sin-cache'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', InicioController::class)->name('inicio');
    Route::get('estadisticas', EstadisticaController::class)->name('estadisticas');
    Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
    Route::get('bitacora/exportar', [BitacoraController::class, 'exportar'])->name('bitacora.exportar');

    Route::get('sistema/configuracion', [ConfiguracionController::class, 'edit'])->name('sitio.edit');
    Route::put('sistema/configuracion', [ConfiguracionController::class, 'update'])->name('sitio.update');

    Route::resource('sistemas', SistemaController::class)->except('show')->parameters(['sistemas' => 'sistema']);
    Route::post('sistemas/{sistema}/imagenes', [SistemaController::class, 'subirImagenes'])->name('sistemas.imagenes.store');
    Route::put('sistemas/{sistema}/imagenes/{imagen}', [SistemaController::class, 'actualizarImagen'])->name('sistemas.imagenes.update');
    Route::delete('sistemas/{sistema}/imagenes/{imagen}', [SistemaController::class, 'borrarImagen'])->name('sistemas.imagenes.destroy');

    // Páginas del sitio y sus secciones (bloques) y elementos.
    Route::resource('paginas', PaginaController::class)->except('show')->parameters(['paginas' => 'pagina']);
    Route::post('paginas/{pagina}/mover/{direccion}', [PaginaController::class, 'mover'])->whereIn('direccion', ['arriba', 'abajo'])->name('paginas.mover');
    Route::post('paginas/{pagina}/secciones', [SeccionController::class, 'store'])->name('secciones.store');
    Route::get('secciones/{seccion}', [SeccionController::class, 'edit'])->name('secciones.edit');
    Route::put('secciones/{seccion}', [SeccionController::class, 'update'])->name('secciones.update');
    Route::delete('secciones/{seccion}', [SeccionController::class, 'destroy'])->name('secciones.destroy');
    Route::post('secciones/{seccion}/mover/{direccion}', [SeccionController::class, 'mover'])->whereIn('direccion', ['arriba', 'abajo'])->name('secciones.mover');
    Route::post('secciones/{seccion}/alternar', [SeccionController::class, 'alternar'])->name('secciones.alternar');
    Route::post('secciones/{seccion}/elementos', [ElementoController::class, 'store'])->name('elementos.store');
    Route::put('elementos/{elemento}', [ElementoController::class, 'update'])->name('elementos.update');
    Route::delete('elementos/{elemento}', [ElementoController::class, 'destroy'])->name('elementos.destroy');
    Route::post('elementos/{elemento}/mover/{direccion}', [ElementoController::class, 'mover'])->whereIn('direccion', ['arriba', 'abajo'])->name('elementos.mover');

    Route::resource('categorias', CategoriaController::class)->only(['store', 'update', 'destroy'])->parameters(['categorias' => 'categoria']);
    Route::resource('manuales', ManualController::class)->except('show')->parameters(['manuales' => 'manual']);
    Route::get('mensajes/exportar', [MensajeController::class, 'exportar'])->name('mensajes.exportar');
    Route::post('mensajes/{mensaje}/credenciales', [MensajeController::class, 'credenciales'])->middleware('throttle:20,1')->name('mensajes.credenciales');

    Route::resource('servicios', ServicioController::class)->except('show')->parameters(['servicios' => 'servicio']);
    Route::resource('clientes', ClienteController::class)->except('show')->parameters(['clientes' => 'cliente']);
    Route::resource('direcciones', DireccionController::class)->except('show')->parameters(['direcciones' => 'direccion']);
    Route::resource('mensajes', MensajeController::class)->only(['index', 'show', 'update', 'destroy'])->parameters(['mensajes' => 'mensaje']);
    Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy'])->parameters(['usuarios' => 'usuario']);

    // Correos: servidor SMTP, plantillas y bitácora (todo en la base, nada en el .env).
    Route::get('correos', [CorreoController::class, 'index'])->name('correos.index');
    Route::post('correos/servidor', [CorreoController::class, 'guardar'])->name('correos.guardar');
    Route::post('correos/probar', [CorreoController::class, 'probar'])->middleware('throttle:10,1')->name('correos.probar');
    Route::get('correos/plantillas/nueva', [CorreoController::class, 'create'])->name('correos.plantillas.create');
    Route::post('correos/plantillas', [CorreoController::class, 'store'])->name('correos.plantillas.store');
    Route::get('correos/plantillas/{plantilla}', [CorreoController::class, 'edit'])->name('correos.plantillas.edit');
    Route::put('correos/plantillas/{plantilla}', [CorreoController::class, 'update'])->name('correos.plantillas.update');
    Route::delete('correos/plantillas/{plantilla}', [CorreoController::class, 'destroy'])->name('correos.plantillas.destroy');
    Route::get('correos/plantillas/{plantilla}/vista-previa', [CorreoController::class, 'vistaPrevia'])->name('correos.plantillas.previa');
    Route::post('correos/plantillas/{plantilla}/probar', [CorreoController::class, 'probarPlantilla'])->middleware('throttle:10,1')->name('correos.plantillas.probar');

    // Chat en vivo con visitantes.
    Route::get('chat', [AdminChatController::class, 'index'])->name('chat.index');
    Route::get('chat/resumen', [AdminChatController::class, 'resumen'])->name('chat.resumen');
    Route::get('chat/{conversacion}/mensajes', [AdminChatController::class, 'mensajes'])->name('chat.mensajes');
    Route::post('chat/{conversacion}/mensajes', [AdminChatController::class, 'responder'])->middleware('throttle:60,1')->name('chat.responder');
    Route::post('chat/{conversacion}/cerrar', [AdminChatController::class, 'cerrar'])->name('chat.cerrar');
    Route::delete('chat/{conversacion}', [AdminChatController::class, 'destroy'])->name('chat.destroy');

    Route::get('cuenta', [CuentaController::class, 'show'])->name('cuenta');
    Route::delete('cuenta/sesiones', [CuentaController::class, 'cerrarSesiones'])->middleware('throttle:6,1')->name('cuenta.sesiones');
});

// Páginas creadas en el panel (Nosotros, Servicios, Software…). Va al final para no tapar otras rutas.
Route::get('{pagina:slug}', [SitioController::class, 'pagina'])->where('pagina', '[a-z0-9\-]+')->middleware('visita')->name('pagina');
