<?php

namespace Tests\Feature;

use App\Models\ConfiguracionSitio;
use App\Models\Pagina;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ConfiguracionSistemaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->firstOrFail();
        Storage::fake('contabo');
    }

    private function guardar(array $datos): TestResponse
    {
        return $this->actingAs($this->admin)->put('/admin/sistema/configuracion', ['nombre' => 'Solutions GT'] + $datos);
    }

    public function test_la_pantalla_abre_con_todas_las_pestanas(): void
    {
        $this->actingAs($this->admin)->get('/admin/sistema/configuracion')->assertOk()
            ->assertSee(['Empresa y contacto', 'Logo e íconos', 'Fotos de inicio', 'Colores', 'Redes sociales', 'Chat en vivo', 'Inicio de sesión']);
        $this->actingAs($this->admin)->get('/admin/sistema/configuracion?pestana=inicio')->assertOk();
    }

    public function test_subir_y_quitar_fotos_de_inicio(): void
    {
        $this->guardar([
            'fotos_inicio' => [UploadedFile::fake()->image('uno.jpg', 1920, 800), UploadedFile::fake()->image('dos.jpg', 1920, 800)],
            'pestana' => 'inicio',
        ])->assertRedirect('/admin/sistema/configuracion?pestana=inicio')->assertSessionHasNoErrors();

        $carrusel = Pagina::query()->where('es_inicio', true)->firstOrFail()->secciones()->where('tipo', 'carrusel')->firstOrFail();
        $fotos = $carrusel->elementos()->get();
        $this->assertCount(2, $fotos);
        Storage::disk('contabo')->assertExists($fotos[0]->imagen);
        $this->get('/')->assertSee('carousel-item', false);

        $this->guardar(['quitar_fotos' => [$fotos[0]->id]])->assertSessionHasNoErrors();
        $this->assertSame(1, $carrusel->elementos()->count());
        Storage::disk('contabo')->assertMissing($fotos[0]->imagen);
    }

    public function test_las_fotos_de_inicio_deben_ser_imagenes(): void
    {
        $this->guardar(['fotos_inicio' => [UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('fotos_inicio.0');
    }

    public function test_iconos_imagen_de_portada_y_fondo_de_acceso(): void
    {
        $this->guardar([
            'favicon' => UploadedFile::fake()->image('icono.png', 128, 128),
            'logo' => UploadedFile::fake()->image('logo.png', 400, 100),
            'fondo_login' => UploadedFile::fake()->image('fondo.jpg', 1400, 900),
            'portada_imagen' => UploadedFile::fake()->image('captura.png', 1600, 900),
            'login_titulo' => 'Bienvenido a Solutions GT',
        ])->assertSessionHasNoErrors();

        $cfg = ConfiguracionSitio::query()->firstOrFail();
        Storage::disk('contabo')->assertExists([$cfg->favicon, $cfg->logo, $cfg->fondo_login]);
        $portada = Pagina::query()->where('es_inicio', true)->firstOrFail()->secciones()->where('tipo', 'portada')->firstOrFail();
        $this->assertNotNull($portada->imagen);

        $this->get('/')->assertSee($cfg->favicon, false)->assertSee($portada->imagen, false);
        auth()->logout();
        $this->get('/login')->assertSee('Bienvenido a Solutions GT')->assertSee($cfg->fondo_login, false);
    }

    public function test_colores_principal_y_secundario(): void
    {
        $this->guardar(['color_primario' => '#0f4c81', 'color_secundario' => '#16a34a', 'pie_texto' => 'Solutions GT · Guatemala'])->assertSessionHasNoErrors();

        $this->get('/')->assertSee('--phoenix-primary: #0f4c81', false)->assertSee('linear-gradient(90deg, #16a34a, #0f4c81)', false);
        $this->actingAs($this->admin)->get('/admin')->assertSee('Solutions GT · Guatemala');

        $this->guardar(['color_secundario' => 'verde'])->assertSessionHasErrors('color_secundario');
    }
}
