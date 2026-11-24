<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Cuando la base corta una consulta por tiempo o por un bloqueo (0D, D7), la API responde
 * 503 con `Retry-After` y el sobre de siempre, no un 500 genérico.
 *
 * Un 500 le dice al cliente «algo se rompió» y llena el registro de falsos errores de código;
 * un 503 le dice «la base está saturada, vuelve a intentar», que es lo que pasó. Cualquier OTRO
 * error de base (una tabla que no existe, SQL mal formado) sigue siendo 500: no se enmascaran
 * los defectos reales.
 *
 * Sin `RefreshDatabase`: una consulta cortada aborta la transacción de la prueba.
 */
final class LimitesDeBaseRespuestaTest extends TestCase
{
    private const TABLA = 'zz_prueba_503';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Los límites de la base sólo existen en PostgreSQL.');
        }

        DB::unprepared('DROP TABLE IF EXISTS '.self::TABLA);
        DB::unprepared('CREATE TABLE '.self::TABLA.' (id serial primary key, dato text)');
        DB::unprepared('INSERT INTO '.self::TABLA." (dato) VALUES ('semilla')");

        Route::get('/api/v1/zz-lenta', static fn () => DB::select('select pg_sleep(3)'));
        Route::get('/api/v1/zz-bloqueada', static fn () => DB::update('update '.self::TABLA." set dato = 'x' where id = 1"));
        Route::get('/api/v1/zz-rota', static fn () => DB::select('select * from tabla_que_no_existe'));
        Route::get('/zz-lenta-fuera-de-la-api', static fn () => DB::select('select pg_sleep(3)'));
    }

    protected function tearDown(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('reset statement_timeout');
            DB::statement('reset lock_timeout');
            DB::unprepared('DROP TABLE IF EXISTS '.self::TABLA);
        }

        parent::tearDown();
    }

    public function test_una_consulta_cortada_por_tiempo_responde_503_con_retry_after(): void
    {
        DB::statement("set statement_timeout = '200ms'");

        $respuesta = $this->getJson('/api/v1/zz-lenta');

        $respuesta->assertStatus(503)
            ->assertHeader('Retry-After')
            ->assertJson(['success' => false, 'data' => null, 'errors' => null]);
        $this->assertGreaterThan(0, (int) $respuesta->headers->get('Retry-After'));
        $this->assertNotSame('', (string) $respuesta->json('message'));
    }

    public function test_un_bloqueo_que_no_se_suelta_responde_503_con_retry_after(): void
    {
        DB::statement("set lock_timeout = '200ms'");
        $otra = DB::connectUsing('zz_otra', config('database.connections.pgsql'));
        $otra->beginTransaction();
        $otra->select('select * from '.self::TABLA.' where id = 1 for update');

        try {
            $this->getJson('/api/v1/zz-bloqueada')
                ->assertStatus(503)
                ->assertHeader('Retry-After')
                ->assertJson(['success' => false]);
        } finally {
            $otra->rollBack();
        }
    }

    public function test_el_mensaje_no_revela_la_consulta_ni_la_base(): void
    {
        DB::statement("set statement_timeout = '200ms'");

        $cuerpo = (string) $this->getJson('/api/v1/zz-lenta')->getContent();

        $this->assertStringNotContainsString('pg_sleep', $cuerpo);
        $this->assertStringNotContainsString('SQLSTATE', $cuerpo);
        $this->assertStringNotContainsString(self::TABLA, $cuerpo);
    }

    public function test_otro_error_de_base_sigue_siendo_500(): void
    {
        $this->getJson('/api/v1/zz-rota')->assertStatus(500);
    }

    public function test_fuera_de_la_api_no_se_responde_con_el_sobre_de_la_api(): void
    {
        DB::statement("set statement_timeout = '200ms'");

        // La guarda `api/*` es la misma de los demás `render`: fuera de la API el error sigue su
        // camino normal (500) y no se convierte en un sobre que su cliente no sabe leer.
        $this->get('/zz-lenta-fuera-de-la-api')->assertStatus(500);
    }
}
