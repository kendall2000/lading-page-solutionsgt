<?php

use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\CorreoController;
use App\Http\Controllers\Admin\CuentaController;
use App\Http\Controllers\Admin\DireccionController;
use App\Http\Controllers\Admin\InicioController;
use App\Http\Controllers\Admin\MensajeController;
use App\Http\Controllers\Admin\ServicioController;
use App\Http\Controllers\Admin\SistemaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\SitioController;
use Illuminate\Support\Facades\Route;

// Sitio público.
Route::get('/', [SitioController::class, 'inicio'])->name('inicio');
Route::get('sistemas/{sistema:slug}', [SitioController::class, 'sistema'])->name('sistema');
Route::post('contacto', [SitioController::class, 'contacto'])->middleware('throttle:5,10')->name('contacto');

// Panel de administración.
Route::middleware(['auth', 'sin-cache'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', InicioController::class)->name('inicio');

    Route::get('sitio', [ConfiguracionController::class, 'edit'])->name('sitio.edit');
    Route::put('sitio', [ConfiguracionController::class, 'update'])->name('sitio.update');

    Route::resource('sistemas', SistemaController::class)->except('show')->parameters(['sistemas' => 'sistema']);
    Route::post('sistemas/{sistema}/imagenes', [SistemaController::class, 'subirImagenes'])->name('sistemas.imagenes.store');
    Route::put('sistemas/{sistema}/imagenes/{imagen}', [SistemaController::class, 'actualizarImagen'])->name('sistemas.imagenes.update');
    Route::delete('sistemas/{sistema}/imagenes/{imagen}', [SistemaController::class, 'borrarImagen'])->name('sistemas.imagenes.destroy');

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

    Route::get('cuenta', [CuentaController::class, 'show'])->name('cuenta');
    Route::delete('cuenta/sesiones', [CuentaController::class, 'cerrarSesiones'])->middleware('throttle:6,1')->name('cuenta.sesiones');
});
