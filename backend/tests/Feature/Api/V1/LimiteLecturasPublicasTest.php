<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RutaRegistrada;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * El límite de las lecturas públicas de la sede.
 *
 * Cada página que el sitio sirve pide dos o tres lecturas a la API (medido en
 * staging), y todas compartían el `throttle:api` de 60 por minuto y por IP: unas
 * 20 a 30 páginas por minuto para todos los que salen por la misma dirección —una
 * oficina, una red móvil—. Estas pruebas fijan que las lecturas públicas tienen su
 * propio cupo, configurable sin tocar código, y que eso no afloja ningún otro límite.
 */
final class LimiteLecturasPublicasTest extends TestCase
{
    use RefreshDatabase;

    /** Las lecturas que el sitio hace para pintar una página pública. */
    private const LECTURAS = [
        'v1.entidad.mostrar',
        'v1.politicas.index',
        'v1.politicas.show',
        'v1.identidad.top-bar',
        'v1.identidad.footer',
        'v1.identidad.menu',
        'v1.tramites.listar',
        'v1.tramites.mostrar',
    ];

    public function test_el_valor_por_defecto_es_300_por_minuto(): void
    {
        $this->assertSame(300, config('lecturas.limite_por_minuto'));
    }

    public function test_el_limite_es_el_configurado_y_no_el_general_de_la_api(): void
    {
        // Con `throttle:api` (60 por minuto) aplicado además del propio, la petición 61
        // daría 429 aunque la configuración dijera más.
        config(['lecturas.limite_por_minuto' => 70]);

        foreach (range(1, 70) as $numero) {
            $this->assertSame(
                200,
                $this->getJson('/api/v1/politicas')->status(),
                "La petición {$numero} de 70 no debía limitarse.",
            );
        }

        $this->getJson('/api/v1/politicas')->assertStatus(429);
    }

    public function test_al_agotarse_responde_429_con_retry_after_y_el_sobre_del_contrato(): void
    {
        config(['lecturas.limite_por_minuto' => 2]);

        $this->getJson('/api/v1/politicas')->assertOk();
        $this->getJson('/api/v1/tramites')->assertOk();

        $respuesta = $this->getJson('/api/v1/politicas');

        $respuesta->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null);
        $this->assertGreaterThan(0, (int) $respuesta->headers->get('Retry-After'));
    }

    /** @return iterable<string, array{string}> */
    public static function rutasPublicas(): iterable
    {
        yield 'entidad' => ['/api/v1/entidad'];
        yield 'políticas' => ['/api/v1/politicas'];
        yield 'una política' => ['/api/v1/politicas/privacidad'];
        yield 'barra superior' => ['/api/v1/identidad/top-bar'];
        yield 'pie' => ['/api/v1/identidad/footer'];
        yield 'menú' => ['/api/v1/identidad/menu'];
        yield 'catálogo' => ['/api/v1/tramites'];
        yield 'ficha' => ['/api/v1/tramites/no-existe'];
    }

    #[DataProvider('rutasPublicas')]
    public function test_todas_las_lecturas_publicas_gastan_el_mismo_cupo(string $ruta): void
    {
        config(['lecturas.limite_por_minuto' => 1]);

        $this->getJson('/api/v1/politicas')->assertOk();

        $this->getJson($ruta)->assertStatus(429);
    }

    public function test_cada_ip_tiene_su_propio_cupo(): void
    {
        config(['lecturas.limite_por_minuto' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->getJson('/api/v1/politicas')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->getJson('/api/v1/politicas')->assertStatus(429);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->getJson('/api/v1/politicas')->assertOk();
    }

    public function test_agotar_las_lecturas_no_gasta_el_cupo_de_la_busqueda(): void
    {
        config(['lecturas.limite_por_minuto' => 1]);

        $this->getJson('/api/v1/politicas')->assertOk();
        $this->getJson('/api/v1/politicas')->assertStatus(429);

        $this->getJson('/api/v1/busqueda/sugerencias?q=licencia')->assertOk();
    }

    public function test_solo_las_lecturas_publicas_cambian_de_limite(): void
    {
        $conLecturas = [];

        foreach (Route::getRoutes()->getRoutes() as $ruta) {
            $nombre = (string) $ruta->getName();
            if (! str_starts_with($nombre, 'v1.')) {
                continue;
            }

            $limites = $this->limitesDe($ruta);

            if (in_array('lecturas-publicas', $limites, true)) {
                $conLecturas[] = $nombre;
                // Con dos límites mandaría el más estricto: tiene que ser el único.
                $this->assertSame(['lecturas-publicas'], $limites, "{$nombre} lleva más de un límite.");
                $this->assertSame(['GET', 'HEAD'], $ruta->methods(), "{$nombre} no es una lectura.");
            } elseif (str_starts_with($nombre, 'v1.panel.')) {
                // El panel conserva el límite general: el cupo holgado es sólo de lo público.
                $this->assertContains('api', $limites, "{$nombre} perdió el límite general.");
            }
        }

        sort($conLecturas);
        $esperadas = self::LECTURAS;
        sort($esperadas);

        $this->assertSame($esperadas, $conLecturas);
    }

    /**
     * Los limitadores que la ruta lleva de verdad, ya resuelto el grupo `api` y
     * quitados los que la ruta excluye: `throttle:api` → `api`.
     *
     * @return list<string>
     */
    private function limitesDe(RutaRegistrada $ruta): array
    {
        $limites = [];

        foreach (Route::gatherRouteMiddleware($ruta) as $intermedio) {
            if (is_string($intermedio)
                && preg_match('/(?:^throttle|ThrottleRequests(?:WithRedis)?):([^,]+)/', $intermedio, $partes) === 1) {
                $limites[] = $partes[1];
            }
        }

        return $limites;
    }
}
