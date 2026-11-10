<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Una ruta protegida del panel responde 401 con el sobre de la API aunque la petición
 * no declare `Accept: application/json` (un `curl` sin cabeceras, una sonda, un HEAD).
 */
final class NoAutenticadoSinCabeceraTest extends TestCase
{
    public function test_ruta_protegida_sin_accept_responde_401_y_no_500(): void
    {
        $respuesta = $this->call('GET', '/api/v1/panel/politicas');

        $respuesta->assertStatus(401);
        $respuesta->assertJsonPath('success', false);
    }

    public function test_ruta_protegida_con_head_responde_401(): void
    {
        $this->call('HEAD', '/api/v1/panel/politicas')->assertStatus(401);
    }
}
