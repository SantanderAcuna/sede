<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use App\Services\Monitoreo\LectorRedisReal;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * `LectorRedisReal` contra un Redis REAL: un Redis falso no puede decir si `rawCommand`, el SCAN sin prefijo y la forma de
 * `ACL LOG` son como se supone. Se omite si no hay `REDIS_PRUEBA_HOST` (y `REDIS_PRUEBA_PASSWORD`, `REDIS_PRUEBA_PORT`).
 */
final class LectorRedisRealTest extends TestCase
{
    private \Redis $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $host = getenv('REDIS_PRUEBA_HOST');
        if ($host === false || $host === '') {
            $this->markTestSkipped('Sin REDIS_PRUEBA_HOST: se prueba contra un Redis real en CI y en local.');
        }

        config(['database.redis.default' => [
            'host' => $host,
            'port' => (string) (getenv('REDIS_PRUEBA_PORT') ?: '6379'),
            'password' => (string) getenv('REDIS_PRUEBA_PASSWORD'),
            'database' => '0',
        ], 'database.redis.options.prefix' => 'sede-database-']);
        Redis::purge('default');

        $cliente = Redis::connection('default')->client();
        $this->assertInstanceOf(\Redis::class, $cliente);
        $this->admin = $cliente;
        $this->admin->rawCommand('FLUSHALL');
        $this->admin->rawCommand('ACL', 'LOG', 'RESET');
        $this->admin->rawCommand('ACL', 'SETUSER', 'sitio', 'on', '>claveprueba', '~sitio:*', '-@all', '+get', '+set', '+del', '+ping', '+auth');
    }

    protected function tearDown(): void
    {
        if (isset($this->admin)) {
            $this->admin->rawCommand('ACL', 'DELUSER', 'sitio');
            $this->admin->rawCommand('CONFIG', 'SET', 'maxmemory', '0');
        }
        parent::tearDown();
    }

    public function test_suma_solo_las_claves_sitio_y_las_ve_aunque_laravel_use_prefijo(): void
    {
        $this->admin->rawCommand('SET', 'sitio:a', str_repeat('x', 1000));
        $this->admin->rawCommand('SET', 'sitio:b', str_repeat('y', 2000));
        $this->admin->rawCommand('SET', 'sede-database-otra', str_repeat('z', 5000));

        $esperado = (int) $this->admin->rawCommand('MEMORY', 'USAGE', 'sitio:a') + (int) $this->admin->rawCommand('MEMORY', 'USAGE', 'sitio:b');
        $bytes = (new LectorRedisReal)->bytesClavesSitio();

        $this->assertGreaterThan(3000, $bytes);
        $this->assertSame($esperado, $bytes);
    }

    public function test_recorre_mas_claves_que_un_lote_de_scan(): void
    {
        for ($i = 0; $i < 1300; $i++) {
            $this->admin->rawCommand('SET', "sitio:{$i}", 'v');
        }

        $uno = (int) $this->admin->rawCommand('MEMORY', 'USAGE', 'sitio:0');
        $this->assertGreaterThanOrEqual(1300 * $uno - 1300 * 8, (new LectorRedisReal)->bytesClavesSitio());
    }

    public function test_memoria_usada_maxima_y_expulsadas(): void
    {
        $this->admin->rawCommand('CONFIG', 'SET', 'maxmemory', (string) (64 * 1024 * 1024));
        $lector = new LectorRedisReal;

        $this->assertGreaterThan(0, $lector->memoriaUsada());
        $this->assertSame(64 * 1024 * 1024, $lector->memoriaMaxima());
        $this->assertGreaterThanOrEqual(0, $lector->expulsadas());
    }

    public function test_cuenta_los_rechazos_del_usuario_sitio_y_no_los_de_otros(): void
    {
        $lector = new LectorRedisReal;
        $this->assertSame(0, $lector->rechazosDelUsuario('sitio'));

        $sitio = new \Redis;
        $sitio->connect((string) getenv('REDIS_PRUEBA_HOST'), (int) (getenv('REDIS_PRUEBA_PORT') ?: 6379));
        $sitio->auth(['sitio', 'claveprueba']);
        try {
            $sitio->rawCommand('FLUSHALL');
            $this->fail('FLUSHALL debía rechazarse.');
        } catch (\RedisException $e) {
            $this->assertStringContainsString('NOPERM', $e->getMessage());
        }
        try {
            $sitio->rawCommand('GET', 'sede-database-otra');
        } catch (\RedisException) {
        }

        $this->assertGreaterThanOrEqual(2, $lector->rechazosDelUsuario('sitio'));
        $this->assertSame(0, $lector->rechazosDelUsuario('nadie'));
    }
}
