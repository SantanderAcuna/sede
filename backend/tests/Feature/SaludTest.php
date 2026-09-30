<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use stdClass;
use Tests\TestCase;

/**
 * Sondas de salud (SEG-018).
 *
 * Las dos sondas tienen propósitos distintos y estas pruebas fijan esa
 * diferencia: confundirlas produce reinicios en bucle cuando lo que falla es una
 * dependencia y no el proceso.
 *
 * Las dependencias se sustituyen por dobles para que la prueba verifique la
 * lógica de la sonda y no la disponibilidad de los servicios en la máquina donde
 * se ejecuta.
 */
final class SaludTest extends TestCase
{
    public function test_la_sonda_de_vida_responde_sin_consultar_dependencias(): void
    {
        // Aunque la base esté caída, la vida debe responder: si esto falla, el
        // orquestador tiene que reiniciar el proceso.
        DB::shouldReceive('connection')->andThrow(new RuntimeException('sin base'));

        $this->get('/health')
            ->assertOk()
            ->assertExactJson(['estado' => 'vivo']);
    }

    public function test_la_sonda_de_preparacion_declara_el_estado_de_cada_componente(): void
    {
        $this->simular();

        $respuesta = $this->get('/ready');

        $respuesta->assertOk();

        $this->assertSame('listo', $respuesta->json('estado'));
        $this->assertSame(
            ['base_de_datos', 'cache', 'almacenamiento'],
            array_keys($respuesta->json('componentes')),
        );
    }

    public function test_la_sonda_de_preparacion_responde_503_si_una_dependencia_falla(): void
    {
        $this->simular('cache');

        $respuesta = $this->get('/ready');

        $respuesta->assertStatus(503);
        $this->assertSame('no_listo', $respuesta->json('estado'));
        $this->assertFalse($respuesta->json('componentes.cache'));
        $this->assertTrue($respuesta->json('componentes.base_de_datos'));
    }

    public function test_la_sonda_de_preparacion_no_revela_el_motivo_del_fallo(): void
    {
        $this->simular('cache');

        $texto = (string) $this->get('/ready')->getContent();

        $this->assertStringNotContainsString('Connection refused', $texto);
        $this->assertStringNotContainsString('tcp://', $texto);
        $this->assertStringNotContainsString('6379', $texto);
        $this->assertStringNotContainsString('RuntimeException', $texto);
    }

    public function test_las_sondas_quedan_fuera_de_la_api_versionada(): void
    {
        $rutas = collect(app('router')->getRoutes()->getRoutes())
            ->map(fn ($ruta) => $ruta->uri())
            ->all();

        $this->assertContains('health', $rutas);
        $this->assertContains('ready', $rutas);
        $this->assertNotContains('api/v1/health', $rutas);
        $this->assertNotContains('api/v1/ready', $rutas);
    }

    /**
     * Sustituye base, caché y almacenamiento por dobles.
     *
     * El componente indicado falla; el resto responde bien. Se monta todo de una
     * vez y con una sola expectativa por método: declarar una segunda no
     * reemplaza a la primera, y el doble seguiría respondiendo bien.
     */
    private function simular(?string $componenteQueFalla = null): void
    {
        if ($componenteQueFalla === 'base_de_datos') {
            DB::shouldReceive('connection')->andThrow(new RuntimeException('no disponible'));
        } else {
            DB::shouldReceive('connection')->andReturn(new class
            {
                public function getPdo(): object
                {
                    return new stdClass;
                }
            });
        }

        if ($componenteQueFalla === 'cache') {
            Redis::shouldReceive('connection')->andThrow(new RuntimeException('no disponible'));
        } else {
            Redis::shouldReceive('connection')->andReturn(new class
            {
                public function ping(): string
                {
                    return 'PONG';
                }
            });
        }

        if ($componenteQueFalla === 'almacenamiento') {
            Storage::shouldReceive('disk')->andThrow(new RuntimeException('no disponible'));
        } else {
            Storage::shouldReceive('disk')->andReturn(new class
            {
                public function exists(string $ruta): bool
                {
                    return true;
                }
            });
        }
    }
}
