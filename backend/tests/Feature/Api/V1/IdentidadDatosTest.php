<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Http\Requests\ActualizarEntidadRequest;
use App\Models\Entidad;
use Database\Seeders\EntidadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Los datos de identidad que sirve la API tienen que ser ciertos y coincidir con
 * el sitio (T-21, H-22).
 *
 * Antes el pie enlazaba tres políticas con slugs que el sitio no atendía, el
 * escudo apuntaba a un archivo que daba 404 y la URL de YouTube llevaba un
 * espacio, así que el pie mostraba tres redes en vez de cuatro. Los tres defectos
 * nacían en los datos sembrados, que no pasan por la validación de la API.
 */
final class IdentidadDatosTest extends TestCase
{
    use RefreshDatabase;

    private function entidad(): Entidad
    {
        $this->seed(EntidadSeeder::class);

        return Entidad::query()->findOrFail(1);
    }

    private function rutaDelSitio(string $relativa): string
    {
        return base_path('../sitio/'.$relativa);
    }

    public function test_las_cuatro_redes_tienen_una_url_valida(): void
    {
        $redes = $this->entidad()->redes;

        $this->assertCount(4, $redes);
        foreach ($redes as $red) {
            $this->assertNotFalse(
                filter_var($red['url'], FILTER_VALIDATE_URL),
                'URL de red social no válida: '.$red['url'],
            );
        }
    }

    public function test_la_validacion_rechaza_una_url_de_red_con_espacio(): void
    {
        $validador = Validator::make(
            ['redes' => [['red' => 'youtube', 'url' => 'https://www.youtube.com/@Alcaldia de Santa Marta']]],
            (new ActualizarEntidadRequest)->rules(),
        );

        $this->assertTrue($validador->fails());
        $this->assertArrayHasKey('redes.0.url', $validador->errors()->toArray());
    }

    public function test_todo_slug_de_politica_existe_como_pagina_del_sitio(): void
    {
        $config = (string) file_get_contents($this->rutaDelSitio('app/config/sitemap.ts'));
        $this->assertSame(1, preg_match('/export const POLITICAS[^=]*=\s*\[(.*?)\n\]/s', $config, $bloque));
        preg_match_all("/slug:\s*'([^']+)'/", $bloque[1], $coincidencias);
        $delSitio = $coincidencias[1];
        $this->assertCount(5, $delSitio);

        $slugsApi = array_column($this->entidad()->politicas, 'slug');

        $this->assertEqualsCanonicalizing($delSitio, $slugsApi);
    }

    public function test_el_logo_apunta_a_un_archivo_que_sirve_el_sitio(): void
    {
        $logo = (string) $this->entidad()->logo;

        $this->assertStringStartsWith('/', $logo);
        $this->assertFileExists($this->rutaDelSitio('public'.$logo));
    }
}
