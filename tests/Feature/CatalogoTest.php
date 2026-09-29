<?php

namespace Tests\Feature;

use App\Models\MensajeContacto;
use App\Models\Sistema;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_el_catalogo_muestra_todos_los_sistemas_y_categorias(): void
    {
        $this->assertSame(14, Sistema::query()->count());

        $this->get('/software')->assertOk()
            ->assertSee(['Nexus ERP', 'Chatea con tu Empresa (IA)', 'Sistema para Salones de Belleza', 'Sistema para Boutiques',
                'Sistema para Ferreterías', 'Sistema para Condominios', 'Sistema Escolar', 'Sistema de Protección Civil',
                'Sistema de Transporte (Buses)', 'Sitio Web para tu Empresa'])
            ->assertSee(['Gestión empresarial', 'Educación', 'Transporte', 'Próximamente']);

        $this->get('/servicios')->assertSee('Integración con SAP Business One')->assertSee('INFILE');
        $this->get('/sistemas/sistema-de-transporte-urban')->assertOk()->assertSee('FEL con INFILE y otros certificadores SAT');
    }

    public function test_el_seeder_no_duplica_ni_pisa_lo_editado(): void
    {
        Sistema::query()->where('slug', 'nexus-erp')->update(['resumen' => 'Editado a mano']);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(14, Sistema::query()->count());
        $this->assertSame('Editado a mano', Sistema::query()->where('slug', 'nexus-erp')->value('resumen'));
    }

    public function test_un_sistema_proximamente_no_ofrece_demo_ni_prueba_y_registra_el_aviso(): void
    {
        $escolar = Sistema::query()->where('slug', 'sistema-escolar')->firstOrFail();
        $escolar->update(['acepta_demo' => true, 'acepta_prueba' => true]);

        $this->get('/sistemas/sistema-escolar')->assertOk()
            ->assertSee('Estamos terminando este sistema')
            ->assertDontSee('Una demostración')
            ->assertDontSee('días gratis');

        // Aunque alguien fuerce «prueba», queda como aviso (contacto).
        $this->from('/sistemas/sistema-escolar')->post('/contacto', $this->antispam() + [
            'tipo' => 'prueba', 'nombre' => 'Colegio San José', 'correo' => 'direccion@colegio.edu.gt', 'sistema_id' => $escolar->id, 'ancla' => 'solicitar',
        ])->assertSessionHasNoErrors();

        $m = MensajeContacto::query()->firstOrFail();
        $this->assertSame('contacto', $m->tipo);
        $this->assertSame('Avísenme cuando Sistema Escolar esté disponible.', $m->mensaje);
    }

    public function test_marcar_proximamente_desde_el_panel(): void
    {
        $admin = User::query()->firstOrFail();
        $sistema = Sistema::query()->where('slug', 'nexus-erp')->firstOrFail();

        $this->actingAs($admin)->put("/admin/sistemas/{$sistema->id}", [
            'nombre' => $sistema->nombre, 'slug' => $sistema->slug, 'resumen' => $sistema->resumen, 'modalidad' => 'premium',
            'acepta_demo' => '1', 'proximamente' => '1', 'visible' => '1',
        ])->assertSessionHasNoErrors();

        $sistema->refresh();
        $this->assertTrue($sistema->proximamente);
        $this->assertFalse($sistema->ofreceDemo());
    }
}
