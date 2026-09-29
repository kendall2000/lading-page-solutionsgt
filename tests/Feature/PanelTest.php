<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Direccion;
use App\Models\MensajeContacto;
use App\Models\Servicio;
use App\Models\Sistema;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->firstOrFail();
    }

    public function test_el_panel_exige_iniciar_sesion(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/sistemas')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Panel de administración');
    }

    public function test_un_usuario_desactivado_no_puede_entrar(): void
    {
        $this->admin->update(['is_active' => false, 'password' => 'ClaveSegura123']);

        $this->post('/login', ['email' => $this->admin->email, 'password' => 'ClaveSegura123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_todas_las_pantallas_del_panel_abren(): void
    {
        $sistema = Sistema::query()->firstOrFail();
        $mensaje = MensajeContacto::query()->create(['nombre' => 'Ana', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Hola', 'telefono' => '55555555']);
        $cliente = Cliente::query()->create(['empresa' => 'Café Central']);
        $direccion = Direccion::query()->create(['nombre' => 'Oficina', 'direccion' => 'Zona 1', 'visible' => true]);

        $rutas = [
            '/admin', '/admin/mensajes', "/admin/mensajes/{$mensaje->id}", '/admin/sistemas', '/admin/sistemas/create',
            "/admin/sistemas/{$sistema->id}/edit", '/admin/servicios', '/admin/servicios/create',
            '/admin/servicios/'.Servicio::query()->first()->id.'/edit', '/admin/clientes', '/admin/clientes/create',
            "/admin/clientes/{$cliente->id}/edit", '/admin/direcciones', '/admin/direcciones/create',
            "/admin/direcciones/{$direccion->id}/edit", '/admin/sitio', '/admin/usuarios', '/admin/usuarios/create',
            "/admin/usuarios/{$this->admin->id}/edit", '/admin/cuenta',
        ];
        foreach ($rutas as $ruta) {
            $this->actingAs($this->admin)->get($ruta)->assertOk();
        }
    }

    public function test_crear_y_editar_un_sistema(): void
    {
        $this->actingAs($this->admin)->post('/admin/sistemas', [
            'nombre' => 'Salón de Uñas',
            'resumen' => 'Citas, clientas y caja para salones de belleza.',
            'caracteristicas' => "Agenda de citas\nCaja",
            'tecnologias' => 'Laravel, MySQL',
            'visible' => '1',
        ])->assertRedirect();

        $sistema = Sistema::query()->where('slug', 'salon-de-unas')->firstOrFail();
        $this->assertTrue($sistema->visible);
        $this->assertSame('fa-solid fa-laptop-code', $sistema->icono);
        $this->assertSame(['Agenda de citas', 'Caja'], $sistema->listaCaracteristicas());

        $this->actingAs($this->admin)->put("/admin/sistemas/{$sistema->id}", [
            'nombre' => 'Salón de Uñas', 'slug' => 'salon-unas', 'resumen' => 'Nuevo resumen', 'icono' => 'fa-solid fa-spa',
        ])->assertRedirect();

        $sistema->refresh();
        $this->assertSame('salon-unas', $sistema->slug);
        $this->assertFalse($sistema->visible);
        $this->assertSame('fa-solid fa-spa', $sistema->icono);
        $this->get('/sistemas/salon-unas')->assertNotFound();
    }

    public function test_el_slug_no_se_repite(): void
    {
        $this->actingAs($this->admin)->post('/admin/sistemas', [
            'nombre' => 'AutoDMV Pro', 'resumen' => 'Duplicado',
        ])->assertSessionHasErrors('slug');
    }

    public function test_solo_una_direccion_es_principal(): void
    {
        $this->actingAs($this->admin)->post('/admin/direcciones', ['nombre' => 'A', 'direccion' => 'Zona 1', 'principal' => '1', 'visible' => '1']);
        $this->actingAs($this->admin)->post('/admin/direcciones', ['nombre' => 'B', 'direccion' => 'Zona 2', 'principal' => '1', 'visible' => '1']);

        $this->assertSame(['B'], Direccion::query()->where('principal', true)->pluck('nombre')->all());
    }

    public function test_cambiar_estado_de_un_mensaje(): void
    {
        $mensaje = MensajeContacto::query()->create(['nombre' => 'Ana', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Hola']);

        $this->actingAs($this->admin)->put("/admin/mensajes/{$mensaje->id}", ['estado' => 'cliente', 'notas' => 'Cerró trato'])->assertRedirect();
        $this->assertSame('cliente', $mensaje->fresh()->estado);

        $this->actingAs($this->admin)->put("/admin/mensajes/{$mensaje->id}", ['estado' => 'inventado'])->assertSessionHasErrors('estado');
    }

    public function test_guardar_datos_del_sitio_se_ve_en_la_portada(): void
    {
        $this->actingAs($this->admin)->put('/admin/sitio', [
            'nombre' => 'Solutions GT',
            'propietario' => 'Carlos Ramírez',
            'whatsapp' => '5555 1234',
            'color_primario' => '#0a7cff',
            'anios_experiencia' => 8,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->get('/')->assertSee('Carlos Ramírez')->assertSee('https://wa.me/50255551234', false)->assertSee('--phoenix-primary: #0a7cff', false);
    }

    public function test_no_puede_desactivarse_a_si_mismo(): void
    {
        $this->actingAs($this->admin)->put("/admin/usuarios/{$this->admin->id}", [
            'name' => 'Admin', 'email' => $this->admin->email,
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_crear_usuario_con_contrasena_segura(): void
    {
        $this->actingAs($this->admin)->post('/admin/usuarios', [
            'name' => 'Ayudante', 'email' => 'AYUDANTE@ejemplo.com', 'password' => 'corta', 'password_confirmation' => 'corta', 'is_active' => '1',
        ])->assertSessionHasErrors('password');

        $this->actingAs($this->admin)->post('/admin/usuarios', [
            'name' => 'Ayudante', 'email' => 'AYUDANTE@ejemplo.com', 'password' => 'ClaveSegura123', 'password_confirmation' => 'ClaveSegura123', 'is_active' => '1',
        ])->assertRedirect('/admin/usuarios');

        $this->assertDatabaseHas('users', ['email' => 'ayudante@ejemplo.com', 'is_active' => true]);
    }
}
