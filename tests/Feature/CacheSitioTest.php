<?php

namespace Tests\Feature;

use App\Models\ConfiguracionSitio;
use App\Support\Sitio;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Con la caché en la base (como en el servidor), la configuración del sitio debe
 * leerse bien desde la caché: Laravel 13 no deserializa objetos guardados ahí.
 */
class CacheSitioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'database']);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_configuracion_se_lee_desde_la_cache_de_la_base(): void
    {
        ConfiguracionSitio::query()->first()->update(['nombre' => 'Solutions GT Cache', 'color_primario' => '#0a7cff']);
        Sitio::olvidar();

        Sitio::config(); // se guarda en caché
        $this->resetearMemoria();
        $cfg = Sitio::config(); // se lee de la caché

        $this->assertInstanceOf(ConfiguracionSitio::class, $cfg);
        $this->assertSame('Solutions GT Cache', $cfg->nombre);
        $this->assertTrue($cfg->exists);

        // Dos visitas seguidas a la portada (la segunda usa la caché).
        $this->resetearMemoria();
        $this->get('/')->assertOk();
        $this->resetearMemoria();
        $this->get('/')->assertOk()->assertSee('Solutions GT Cache')->assertSee('--phoenix-primary: #0a7cff', false);
    }

    /** Borra lo que Sitio guarda en memoria para simular una petición nueva (la caché de la base se conserva). */
    private function resetearMemoria(): void
    {
        $ref = new \ReflectionClass(Sitio::class);
        foreach (['actual' => null, 'menu' => null, 'enlaces' => []] as $prop => $valor) {
            $ref->getProperty($prop)->setValue(null, $valor);
        }
    }
}
