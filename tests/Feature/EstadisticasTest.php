<?php

namespace Tests\Feature;

use App\Models\BitacoraCambio;
use App\Models\MensajeContacto;
use App\Models\Sistema;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstadisticasTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->firstOrFail();
    }

    // ---------- Contador de visitas ----------

    public function test_cuenta_las_visitas_de_personas_con_su_origen_y_equipo(): void
    {
        $sistema = Sistema::query()->where('visible', true)->firstOrFail();

        $this->withHeaders(['User-Agent' => self::CHROME, 'Referer' => 'https://www.google.com/'])->get('/')->assertOk();
        $this->withHeaders(['User-Agent' => self::CHROME, 'Referer' => url('/')])->get('/nosotros')->assertOk();
        $this->withHeaders(['User-Agent' => self::IPHONE, 'Referer' => 'https://l.facebook.com/'])->get("/sistemas/{$sistema->slug}")->assertOk();
        $this->withHeaders(['User-Agent' => self::CHROME])->get('/software?utm_source=boletin')->assertOk();

        $visitas = DB::table('visitas')->orderBy('id')->get();
        $this->assertSame(['google', 'interno', 'facebook', 'campana'], $visitas->pluck('origen')->all());
        $this->assertSame(['/', '/nosotros', "/sistemas/{$sistema->slug}", '/software'], $visitas->pluck('ruta')->all());
        $this->assertSame(['pagina', 'pagina', 'sistema', 'pagina'], $visitas->pluck('tipo')->all());
        $this->assertSame($sistema->nombre, $visitas[2]->titulo);
        $this->assertSame(['escritorio', 'escritorio', 'movil', 'escritorio'], $visitas->pluck('dispositivo')->all());
        $this->assertSame('Safari', $visitas[2]->navegador);
        // El mismo navegador el mismo día es el mismo visitante; no se guarda la IP.
        $this->assertSame($visitas[0]->visitante, $visitas[1]->visitante);
        $this->assertNotSame($visitas[0]->visitante, $visitas[2]->visitante);
        $this->assertStringNotContainsString('127.0.0.1', json_encode($visitas));
    }

    public function test_no_cuenta_robots_errores_precargas_ni_al_usuario_del_panel(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])->get('/')->assertOk();
        $this->withHeaders(['User-Agent' => 'WhatsApp/2.23.20.0'])->get('/')->assertOk();
        $this->withHeaders(['User-Agent' => ''])->get('/')->assertOk();
        $this->withHeaders(['User-Agent' => self::CHROME, 'Sec-Purpose' => 'prefetch'])->get('/')->assertOk();
        $this->withHeaders(['User-Agent' => self::CHROME])->get('/no-existe')->assertNotFound();
        $this->withHeaders(['User-Agent' => self::CHROME])->get('/sitemap.xml')->assertOk();
        $this->actingAs($this->admin)->withHeaders(['User-Agent' => self::CHROME])->get('/')->assertOk();

        $this->assertSame(0, DB::table('visitas')->count());
    }

    // ---------- Pantalla de estadísticas ----------

    public function test_la_pantalla_de_estadisticas_muestra_todo_y_acepta_cada_rango(): void
    {
        $this->withHeaders(['User-Agent' => self::CHROME, 'Referer' => 'https://www.google.com/'])->get('/nosotros');
        $this->withHeaders(['User-Agent' => self::IPHONE])->get('/nosotros');
        MensajeContacto::query()->create(['nombre' => 'Ana', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Hola', 'tipo' => 'demo', 'sistema_id' => Sistema::query()->value('id')]);
        DB::table('bitacora_correos')->insert(['plantilla' => 'nuevo_mensaje', 'destinatario' => 'a@b.com', 'estado' => 'fallido', 'created_at' => now()]);

        $this->actingAs($this->admin)->get('/admin/estadisticas')->assertOk()
            ->assertSee(['Visitantes', 'Páginas vistas', 'Solicitudes', 'Chats', 'Usuarios y bitácora'])
            ->assertSee(\App\Models\Pagina::query()->where('slug', 'nosotros')->value('titulo'))
            ->assertSee('Google')
            ->assertSee('nuevo_mensaje')
            ->assertSee($this->admin->email)
            ->assertSee('"visitantes":[', false);

        foreach (['hoy', '7', '90', 'mes', 'mes_anterior', '365', 'inventado'] as $rango) {
            $this->get('/admin/estadisticas?rango='.$rango)->assertOk();
        }
        $this->get('/admin/estadisticas?rango=personalizado&desde=2026-01-01&hasta='.now()->toDateString())->assertOk()->assertSee('1 de enero 2026');
        $this->get('/admin/estadisticas?rango=personalizado&desde=2026-05-01&hasta=2026-04-01')->assertSessionHasErrors('hasta');
    }

    public function test_el_inicio_del_panel_muestra_visitantes_y_actividad(): void
    {
        $this->withHeaders(['User-Agent' => self::CHROME])->get('/');

        $this->actingAs($this->admin)->get('/admin')->assertOk()
            ->assertSee('Visitantes hoy')
            ->assertSee('Estadísticas completas')
            ->assertSee('Actividad en el panel');
    }

    // ---------- Bitácora de cambios ----------

    public function test_la_bitacora_anota_entradas_cambios_y_accesos_fallidos_sin_guardar_claves(): void
    {
        $this->admin->update(['password' => 'ClaveSegura123']);
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'mala-clave-123'])->assertSessionHasErrors();
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'ClaveSegura123']);

        $sistema = Sistema::query()->firstOrFail();
        $this->put("/admin/sistemas/{$sistema->id}", ['nombre' => 'Nombre nuevo', 'slug' => $sistema->slug, 'resumen' => $sistema->resumen, 'icono' => $sistema->icono]);
        $this->put("/admin/usuarios/{$this->admin->id}", ['name' => $this->admin->name, 'email' => $this->admin->email, 'password' => 'OtraClave12345', 'password_confirmation' => 'OtraClave12345', 'is_active' => '1']);

        $this->assertDatabaseHas('bitacora_cambios', ['accion' => 'fallido', 'usuario' => $this->admin->email, 'user_id' => $this->admin->id]);
        $this->assertDatabaseHas('bitacora_cambios', ['accion' => 'entrar', 'user_id' => $this->admin->id]);
        $editado = BitacoraCambio::query()->where('modelo_tipo', Sistema::class)->where('accion', 'editar')->firstOrFail();
        $this->assertSame('Software', $editado->modulo);
        $this->assertSame([$sistema->nombre, 'Nombre nuevo'], $editado->cambios['nombre']);
        $this->assertArrayNotHasKey('updated_at', $editado->cambios);

        $clave = BitacoraCambio::query()->where('modelo_tipo', User::class)->where('accion', 'editar')->firstOrFail();
        $this->assertSame(['•••', '(cambió)'], $clave->cambios['password']);
        $this->assertStringNotContainsString('OtraClave12345', BitacoraCambio::query()->get()->toJson());
        $this->assertStringNotContainsString('$2y$', BitacoraCambio::query()->get()->toJson());
    }

    public function test_lo_que_hacen_visitantes_y_procesos_no_va_a_la_bitacora(): void
    {
        $this->from('/')->post('/contacto', $this->antispam() + ['nombre' => 'Ana', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Hola']);
        $this->withHeaders(['User-Agent' => self::CHROME])->get('/sistemas/'.Sistema::query()->where('visible', true)->value('slug'));

        $this->assertSame(0, BitacoraCambio::query()->count());
    }

    public function test_la_bitacora_se_filtra_y_se_descarga(): void
    {
        $this->actingAs($this->admin)->put('/admin/mensajes/'.MensajeContacto::query()->create(['nombre' => 'Ana', 'correo' => 'a@b.com', 'mensaje' => 'x'])->id, ['estado' => 'cliente']);
        $this->actingAs($this->admin)->delete('/admin/clientes/'.\App\Models\Cliente::query()->create(['empresa' => '=HYPERLINK("http://x")'])->id);

        $this->get('/admin/bitacora')->assertOk()->assertSee('Solicitud «Ana»')->assertSee('Borró');
        $this->get('/admin/bitacora?accion=borrar')->assertOk()->assertDontSee('Solicitud «Ana»')->assertSee('Cliente');
        $this->get('/admin/bitacora?modulo=Solicitudes&usuario='.$this->admin->id)->assertOk()->assertSee('Solicitud «Ana»');
        $this->get('/admin/bitacora?accion=inventada')->assertSessionHasErrors('accion');

        $csv = $this->get('/admin/bitacora/exportar')->assertOk()->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF".'Fecha,Usuario,Acción', $csv);
        $this->assertStringContainsString('estado: ""nuevo"" → ""cliente""', $csv);
        // El «=» no va al principio de la celda: Excel no lo ejecuta y se deja tal cual.
        $this->assertStringContainsString('Cliente «=HYPERLINK', $csv);
    }

    // ---------- Exportar solicitudes ----------

    public function test_exportar_solicitudes_para_excel_con_filtros_y_sin_formulas(): void
    {
        MensajeContacto::query()->create(['nombre' => '=cmd|calc', 'correo' => 'x@y.com', 'mensaje' => 'Ataque', 'estado' => 'nuevo']);
        MensajeContacto::query()->create(['nombre' => 'Ana López', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Quiero una demo', 'tipo' => 'demo', 'estado' => 'cliente', 'clave_prueba' => 'secreta']);

        $csv = $this->actingAs($this->admin)->get('/admin/mensajes/exportar')->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF".'Fecha,Tipo,Estado,Nombre', $csv);
        $this->assertStringContainsString('Ana López', $csv);
        $this->assertStringContainsString("'=cmd|calc", $csv);
        $this->assertStringNotContainsString('secreta', $csv);

        $soloClientes = $this->get('/admin/mensajes/exportar?estado=cliente')->streamedContent();
        $this->assertStringContainsString('Ana López', $soloClientes);
        $this->assertStringNotContainsString('cmd|calc', $soloClientes);
        $this->get('/admin/mensajes')->assertSee('Descargar para Excel');
    }

    public function test_las_pantallas_nuevas_piden_sesion(): void
    {
        foreach (['/admin/estadisticas', '/admin/bitacora', '/admin/bitacora/exportar', '/admin/mensajes/exportar'] as $ruta) {
            $this->get($ruta)->assertRedirect(route('login'));
        }
    }

    public function test_la_limpieza_borra_visitas_de_mas_de_13_meses(): void
    {
        DB::table('visitas')->insert([
            ['visitante' => str_repeat('a', 64), 'ruta' => '/', 'tipo' => 'pagina', 'origen' => 'directo', 'dispositivo' => 'escritorio', 'navegador' => 'Chrome', 'created_at' => now()->subMonths(14)],
            ['visitante' => str_repeat('b', 64), 'ruta' => '/', 'tipo' => 'pagina', 'origen' => 'directo', 'dispositivo' => 'escritorio', 'navegador' => 'Chrome', 'created_at' => now()->subMonths(12)],
        ]);

        $this->artisan('sitio:limpiar')->assertSuccessful();

        $this->assertSame([str_repeat('b', 64)], DB::table('visitas')->pluck('visitante')->all());
    }
}
