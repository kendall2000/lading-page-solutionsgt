<?php

namespace Tests\Feature;

use App\Mail\CorreoPlantilla;
use App\Models\CategoriaSistema;
use App\Models\ConfiguracionCorreo;
use App\Models\Elemento;
use App\Models\Manual;
use App\Models\MensajeContacto;
use App\Models\Pagina;
use App\Models\Seccion;
use App\Models\Sistema;
use App\Models\User;
use App\Support\Bloques;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaginasPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->firstOrFail();
    }

    public function test_las_pantallas_nuevas_del_panel_abren(): void
    {
        $pagina = Pagina::query()->where('slug', 'nosotros')->firstOrFail();
        $rutas = ['/admin/paginas', '/admin/paginas/create', "/admin/paginas/{$pagina->id}/edit", '/admin/manuales', '/admin/manuales/create'];
        foreach (Seccion::query()->pluck('id') as $id) {
            $rutas[] = "/admin/secciones/{$id}";
        }
        foreach ($rutas as $ruta) {
            $this->actingAs($this->admin)->get($ruta)->assertOk();
        }
    }

    public function test_crear_pagina_con_submenu_y_secciones(): void
    {
        $servicios = Pagina::query()->where('slug', 'servicios')->firstOrFail();

        $this->actingAs($this->admin)->post('/admin/paginas', [
            'titulo' => 'Diseño web', 'padre_id' => $servicios->id, 'visible' => '1', 'en_menu' => '1',
        ])->assertRedirect();
        $pagina = Pagina::query()->where('slug', 'diseno-web')->firstOrFail();
        $this->assertSame($servicios->id, $pagina->padre_id);

        $this->actingAs($this->admin)->post("/admin/paginas/{$pagina->id}/secciones", ['tipo' => 'texto'])->assertRedirect();
        $seccion = $pagina->secciones()->firstOrFail();
        $this->actingAs($this->admin)->put("/admin/secciones/{$seccion->id}", [
            'titulo' => 'Sitios que venden', 'contenido' => 'Hacemos **páginas web** modernas.', 'visible' => '1',
            'boton_texto' => 'Cotizar', 'boton_enlace' => '/contactenos', 'opciones' => ['lado' => 'izquierda'],
        ])->assertSessionHasNoErrors();

        $this->get('/diseno-web')->assertOk()->assertSee('Sitios que venden')->assertSee('<strong>páginas web</strong>', false);
        // Aparece como submenú de Servicios.
        $this->get('/')->assertSee('dropdown-menu', false)->assertSee(url('/diseno-web'), false);
    }

    public function test_no_se_puede_usar_una_direccion_del_sistema(): void
    {
        $this->actingAs($this->admin)->post('/admin/paginas', ['titulo' => 'Admin'])->assertSessionHasErrors('slug');
        $this->actingAs($this->admin)->post('/admin/paginas', ['titulo' => 'Nosotros'])->assertSessionHasErrors('slug');
    }

    public function test_cada_tipo_de_seccion_se_muestra_en_el_sitio(): void
    {
        $pagina = Pagina::query()->create(['titulo' => 'Prueba', 'slug' => 'prueba', 'visible' => true]);
        foreach (array_keys(Bloques::TIPOS) as $tipo) {
            $this->actingAs($this->admin)->post("/admin/paginas/{$pagina->id}/secciones", ['tipo' => $tipo])->assertRedirect();
        }
        foreach ($pagina->secciones as $sec) {
            if (Bloques::tieneElementos($sec->tipo)) {
                $sec->elementos()->create(['titulo' => "Elemento de {$sec->tipo}", 'texto' => 'Texto', 'imagen' => 'assets/img/bg/bg-34.png', 'valor' => '12', 'grupo' => 'Grupo A']);
            }
        }

        $this->get('/prueba')->assertOk()
            ->assertSee('Elemento de tarjetas')->assertSee('Elemento de tecnologias')->assertSee('Elemento de preguntas')
            ->assertSee('Elemento de carrusel')->assertSee('AutoDMV Pro')->assertSee('Envíanos un mensaje');
    }

    public function test_elementos_se_agregan_editan_ordenan_y_borran(): void
    {
        $seccion = Seccion::query()->where('tipo', 'tecnologias')->firstOrFail();
        $total = $seccion->elementos()->count();

        $this->actingAs($this->admin)->post("/admin/secciones/{$seccion->id}/elementos", [
            'grupo' => 'Lenguajes', 'titulo' => 'Python', 'icono' => 'fa-brands fa-python',
        ])->assertRedirect();
        $nuevo = Elemento::query()->where('titulo', 'Python')->firstOrFail();
        $this->assertSame($total + 1, $nuevo->orden);

        $this->actingAs($this->admin)->post("/admin/elementos/{$nuevo->id}/mover/arriba");
        $this->assertSame($total, $nuevo->fresh()->orden);

        $this->actingAs($this->admin)->put("/admin/elementos/{$nuevo->id}", ['titulo' => 'Python 3', 'icono' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('icono');
        $this->actingAs($this->admin)->put("/admin/elementos/{$nuevo->id}", ['titulo' => 'Python 3', 'enlace' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('enlace');
        $this->actingAs($this->admin)->put("/admin/elementos/{$nuevo->id}", ['titulo' => 'Python 3', 'grupo' => 'Lenguajes', 'visible' => '1'])
            ->assertSessionHasNoErrors();
        $this->get('/nosotros')->assertSee('Python 3');

        $this->actingAs($this->admin)->delete("/admin/elementos/{$nuevo->id}");
        $this->assertModelMissing($nuevo);
    }

    public function test_la_foto_del_carrusel_es_obligatoria(): void
    {
        $carrusel = Seccion::query()->where('tipo', 'carrusel')->firstOrFail();

        $this->actingAs($this->admin)->post("/admin/secciones/{$carrusel->id}/elementos", ['titulo' => 'Sin foto'])
            ->assertSessionHasErrors('imagen');
    }

    public function test_ocultar_y_ordenar_secciones(): void
    {
        $pagina = Pagina::query()->where('slug', 'servicios')->firstOrFail();
        [$primera, $segunda] = $pagina->secciones()->take(2)->get();

        $this->actingAs($this->admin)->post("/admin/secciones/{$segunda->id}/mover/arriba");
        $this->assertSame($segunda->id, $pagina->secciones()->first()->id);

        $this->actingAs($this->admin)->post("/admin/secciones/{$primera->id}/alternar");
        $this->assertFalse($primera->fresh()->visible);
        $this->get('/servicios')->assertDontSee('Diseño Web');
    }

    public function test_la_pagina_de_inicio_no_se_borra(): void
    {
        $inicio = Pagina::query()->where('es_inicio', true)->firstOrFail();

        $this->actingAs($this->admin)->delete("/admin/paginas/{$inicio->id}")->assertSessionHasErrors('pagina');
        $this->assertModelExists($inicio);
    }

    public function test_categorias_y_catalogo(): void
    {
        $this->actingAs($this->admin)->post('/admin/categorias', ['nombre' => 'Salud', 'icono' => 'fa-solid fa-heart-pulse'])->assertRedirect();
        $categoria = CategoriaSistema::query()->where('slug', 'salud')->firstOrFail();

        $this->actingAs($this->admin)->post('/admin/sistemas', [
            'nombre' => 'Clínica', 'resumen' => 'Citas y expedientes.', 'categoria_id' => $categoria->id,
            'modalidad' => 'gratis', 'acepta_prueba' => '1', 'dias_prueba' => 7, 'visible' => '1',
        ])->assertRedirect();
        $sistema = Sistema::query()->where('slug', 'clinica')->firstOrFail();
        $this->assertTrue($sistema->acepta_prueba);
        $this->assertFalse($sistema->acepta_demo);

        $this->get('/software')->assertSee('Clínica')->assertSee('Salud')->assertSee('Gratis')->assertSee('Probar 7 días');

        $this->actingAs($this->admin)->delete("/admin/categorias/{$categoria->id}");
        $this->assertNull($sistema->fresh()->categoria_id);
    }

    public function test_crear_manual(): void
    {
        $sistema = Sistema::query()->firstOrFail();

        $this->actingAs($this->admin)->post('/admin/manuales', [
            'titulo' => 'Primeros pasos', 'sistema_id' => $sistema->id, 'contenido' => '1. Entra', 'visible' => '1',
        ])->assertRedirect();

        $manual = Manual::query()->where('slug', 'primeros-pasos')->firstOrFail();
        $this->assertSame(1, $manual->orden);
        $this->get('/manuales/primeros-pasos')->assertOk()->assertSee('Primeros pasos');
    }

    public function test_enviar_credenciales_de_prueba(): void
    {
        Mail::fake();
        ConfiguracionCorreo::actual()->update(['host' => 'smtp.ejemplo.com', 'puerto' => 587, 'remitente_correo' => 'no@ejemplo.com', 'is_active' => true]);
        $sistema = Sistema::query()->firstOrFail();
        $mensaje = MensajeContacto::query()->create([
            'tipo' => 'prueba', 'nombre' => 'Ana', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Quiero probar', 'sistema_id' => $sistema->id,
        ]);

        $this->actingAs($this->admin)->get("/admin/mensajes/{$mensaje->id}")->assertOk()->assertSee('Acceso de prueba');
        $this->actingAs($this->admin)->post("/admin/mensajes/{$mensaje->id}/credenciales", [
            'url_acceso' => 'https://demo.ejemplo.com', 'usuario_prueba' => 'ana', 'clave_prueba' => 'Prueba-123', 'dias' => 15, 'enviar' => '1',
        ])->assertSessionHasNoErrors();

        $mensaje->refresh();
        $this->assertSame('Prueba-123', $mensaje->clave_prueba);
        $this->assertNotSame('Prueba-123', $mensaje->getRawOriginal('clave_prueba'));
        $this->assertSame(now()->addDays(15)->toDateString(), $mensaje->vence_el->toDateString());
        $this->assertNotNull($mensaje->credenciales_enviadas_en);
        $this->assertSame('atendido', $mensaje->estado);
        Mail::assertSent(CorreoPlantilla::class, fn ($m) => $m->hasTo('ana@ejemplo.com') && str_contains($m->cuerpo, 'Prueba-123') && str_contains($m->cuerpo, $sistema->nombre));
    }
}
