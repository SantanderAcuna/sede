<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Tramites;

use App\Support\Tramites\SlugCatalogo;
use Tests\TestCase;

/**
 * Unidad de SlugCatalogo.
 */
final class SlugCatalogoTest extends TestCase
{
    public function test_nombre_normal_genera_slug(): void
    {
        $usados = [];

        $slug = SlugCatalogo::para('Trámite de prueba', 'T00001', $usados);

        $this->assertSame('tramite-de-prueba', $slug);
    }

    public function test_nombre_vacio_usa_el_codigo(): void
    {
        // Un nombre que al pasarlo por Str::slug produce cadena vacía
        $usados = [];

        $slug = SlugCatalogo::para('!!!', 'T00001', $usados);

        // Línea 33: slug '' → usa código en minúsculas
        $this->assertSame('t00001', $slug);
    }

    public function test_slug_duplicado_agrega_codigo(): void
    {
        $usados = ['tramite-de-prueba' => 'T00001'];

        $slug = SlugCatalogo::para('Trámite de prueba', 'T00002', $usados);

        // Línea 37: slug ya usado → se trunca a 140 y se agrega código
        $this->assertStringStartsWith('tramite-de-prueba-', $slug);
        $this->assertStringContainsString('t00002', $slug);
    }

    public function test_el_usados_se_actualiza(): void
    {
        $usados = [];

        SlugCatalogo::para('Trámite uno', 'T00001', $usados);

        $this->assertArrayHasKey('tramite-uno', $usados);
        $this->assertSame('T00001', $usados['tramite-uno']);
    }
}
