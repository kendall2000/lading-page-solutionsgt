<?php

namespace Tests\Feature;

use App\Models\Conversacion;
use App\Models\MensajeChat;
use App\Services\Respaldos;
use App\Support\Imagenes;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TareasProgramadasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function conversacion(string $estado, $ultimo): Conversacion
    {
        $c = Conversacion::query()->forceCreate([
            'token_hash' => hash('sha256', Str::random()), 'nombre' => 'Ana', 'correo' => 'ana@ejemplo.com',
            'estado' => $estado, 'ultimo_mensaje_en' => $ultimo,
        ]);
        MensajeChat::query()->forceCreate(['conversacion_id' => $c->id, 'autor' => 'visitante', 'cuerpo' => 'Hola']);

        return $c;
    }

    public function test_la_limpieza_borra_solo_lo_viejo(): void
    {
        DB::table('bitacora_correos')->insert([
            ['destinatario' => 'viejo@ejemplo.com', 'estado' => 'enviado', 'created_at' => now()->subDays(91)],
            ['destinatario' => 'nuevo@ejemplo.com', 'estado' => 'enviado', 'created_at' => now()->subDays(89)],
        ]);
        $cerradaVieja = $this->conversacion('cerrada', now()->subMonths(7));
        $cerradaReciente = $this->conversacion('cerrada', now()->subMonths(5));
        $abiertaVieja = $this->conversacion('abierta', now()->subYear());

        $this->artisan('sitio:limpiar')->assertSuccessful();

        $this->assertSame(['nuevo@ejemplo.com'], DB::table('bitacora_correos')->pluck('destinatario')->all());
        $this->assertModelMissing($cerradaVieja);
        $this->assertModelExists($cerradaReciente);
        $this->assertModelExists($abiertaVieja);
        $this->assertSame(0, MensajeChat::query()->where('conversacion_id', $cerradaVieja->id)->count());
        $this->assertSame(2, MensajeChat::query()->count());
    }

    public function test_el_respaldo_se_sube_cifrado_y_privado_y_se_conservan_30(): void
    {
        Storage::fake(Imagenes::DISCO);
        // Base MySQL ficticia: el volcado se simula, no se conecta a nada.
        config(['database.connections.respaldo_prueba' => [
            'driver' => 'mysql', 'host' => 'bd.invalid', 'port' => 3306, 'database' => 'solutionsgt', 'username' => 'u', 'password' => 'secreta',
        ]]);
        Process::fake(['*' => Process::result("/*M!999999\\- enable the sandbox mode */\nCREATE TABLE `users` (`id` int);\nINSERT INTO `users` VALUES (1);\n")]);
        // 30 respaldos anteriores: al crear uno nuevo, el más viejo sobra.
        foreach (range(1, 30) as $dia) {
            Storage::disk(Imagenes::DISCO)->put(sprintf('respaldos/solutionsgt-2026-08-%02d-030000-x.sql.gz.enc', $dia), 'viejo');
        }

        $this->artisan('respaldo:crear', ['--conexion' => 'respaldo_prueba'])->assertSuccessful();

        Process::assertRan(fn ($p) => $p->command[0] === 'mariadb-dump' && ! str_contains(implode(' ', $p->command), 'secreta')
            && $p->environment['MYSQL_PWD'] === 'secreta');
        $respaldos = app(Respaldos::class);
        $lista = $respaldos->lista();
        $this->assertCount(30, $lista);
        $this->assertStringNotContainsString('2026-08-01', $lista->implode(' '));

        $nuevo = $lista->first();
        $this->assertStringStartsWith('respaldos/solutionsgt-'.now()->format('Y-m-d'), $nuevo);
        $this->assertStringNotContainsString('CREATE TABLE', Storage::disk(Imagenes::DISCO)->get($nuevo));
        $this->assertSame("CREATE TABLE `users` (`id` int);\nINSERT INTO `users` VALUES (1);\n", $respaldos->leer($nuevo));
    }

    public function test_si_el_respaldo_falla_avisa_y_no_borra_los_anteriores(): void
    {
        Storage::fake(Imagenes::DISCO);
        Storage::disk(Imagenes::DISCO)->put('respaldos/solutionsgt-2026-08-01-030000-x.sql.gz.enc', 'viejo');
        config(['database.connections.respaldo_prueba' => [
            'driver' => 'mysql', 'host' => 'bd.invalid', 'port' => 3306, 'database' => 'solutionsgt', 'username' => 'u', 'password' => 'x',
        ]]);
        Process::fake(['*' => Process::result('', 'Access denied for user', 1)]);

        $this->artisan('respaldo:crear', ['--conexion' => 'respaldo_prueba'])->assertFailed();

        $this->assertCount(1, app(Respaldos::class)->lista());
        $this->assertDatabaseHas('plantillas_correo', ['codigo' => 'respaldo_fallido']);
    }

    public function test_estan_programados_de_madrugada(): void
    {
        $eventos = collect(app(Schedule::class)->events())->mapWithKeys(fn ($e) => [Str::afterLast($e->command, ' ') => $e->expression]);

        $this->assertSame('0 3 * * *', $eventos['respaldo:crear']);
        $this->assertSame('30 3 * * *', $eventos['sitio:limpiar']);
    }
}
