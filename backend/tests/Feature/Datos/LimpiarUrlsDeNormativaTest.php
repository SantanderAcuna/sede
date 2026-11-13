<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\Tramite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** La migración anula solo las URL de normativa que el panel rechazaría; las válidas no se tocan. */
final class LimpiarUrlsDeNormativaTest extends TestCase
{
    use RefreshDatabase;

    public function test_anula_la_ruta_local_y_respeta_las_urls_validas(): void
    {
        $t = Tramite::factory()->create();
        $t->normas()->create(['orden' => 1, 'tipo' => 'Acuerdo', 'url' => 'http://file:///C:/Users/User/Downloads/a.pdf']);
        $t->normas()->create(['orden' => 2, 'tipo' => 'Decreto', 'url' => 'https://www.gov.co/norma']);
        $t->normas()->create(['orden' => 3, 'tipo' => 'Ley', 'url' => null]);

        (require base_path('database/migrations/2026_11_16_000000_limpiar_urls_de_normativa_invalidas.php'))->up();

        $this->assertSame([null, 'https://www.gov.co/norma', null], $t->normas()->get()->pluck('url')->all());
    }
}
