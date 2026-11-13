<?php

declare(strict_types=1);

namespace Tests\Feature\Seguridad;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Una lectura pública no abre sesión ni pone cookies.
 *
 * El sitio y el panel comparten dominio, así que el navegador manda `Referer` del dominio «con
 * estado» de Sanctum en cada lectura. Sin esta barrera, cada visita anónima recibía `XSRF-TOKEN` y
 * la cookie de sesión antes de elegir nada en el banner: eso contradice «sin cookies no esenciales
 * hasta el consentimiento» (AC-26.2). Las cookies del panel (login) no cambian.
 */
final class LecturasPublicasSinCookiesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function lecturas(): array
    {
        return [
            'entidad' => ['/api/v1/entidad'],
            'políticas' => ['/api/v1/politicas'],
            'identidad: menú' => ['/api/v1/identidad/menu'],
            'noticias' => ['/api/v1/noticias'],
            'banners' => ['/api/v1/banners'],
            'trámites' => ['/api/v1/tramites'],
            'búsqueda' => ['/api/v1/buscar?q=licencia'],
            'sugerencias' => ['/api/v1/busqueda/sugerencias?q=lic'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // El camino con estado cifra las cookies: la prueba no depende de la clave del entorno.
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
    }

    private function dominioConEstado(): string
    {
        $dominios = (array) config('sanctum.stateful');
        $this->assertNotEmpty($dominios, 'La prueba necesita al menos un dominio «con estado» configurado.');

        return (string) $dominios[0];
    }

    #[DataProvider('lecturas')]
    public function test_una_lectura_publica_desde_el_dominio_de_la_sede_no_pone_cookies(string $ruta): void
    {
        $respuesta = $this->withHeaders(['Referer' => 'http://'.$this->dominioConEstado().'/', 'Accept' => 'application/json'])->get($ruta);

        $this->assertLessThan(500, $respuesta->getStatusCode());
        $this->assertSame([], $respuesta->headers->getCookies(), 'La lectura pública puso cookies: '.$ruta);
    }

    public function test_el_login_del_panel_sigue_abriendo_sesion(): void
    {
        $respuesta = $this->withHeaders(['Referer' => 'http://'.$this->dominioConEstado().'/', 'Accept' => 'application/json'])
            ->post('/api/v1/panel/login', ['email' => 'nadie@example.org', 'password' => 'x']);

        $nombres = array_map(static fn ($c): string => $c->getName(), $respuesta->headers->getCookies());
        $this->assertContains('XSRF-TOKEN', $nombres, 'El panel necesita su sesión con estado.');
    }
}
