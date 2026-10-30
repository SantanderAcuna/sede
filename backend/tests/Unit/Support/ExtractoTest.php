<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Busqueda\Extracto;
use PHPUnit\Framework\TestCase;

/**
 * El extracto de cada resultado de la búsqueda.
 */
final class ExtractoTest extends TestCase
{
    public function test_un_texto_corto_se_devuelve_limpio_y_sin_puntos(): void
    {
        $this->assertSame('Constancia de domicilio.', Extracto::recortar("  Constancia   de\ndomicilio. ", 200));
    }

    public function test_quita_las_etiquetas_html(): void
    {
        $this->assertSame('Texto en negrita', Extracto::recortar('<p>Texto en <b>negrita</b></p>', 200));
    }

    public function test_nulo_o_vacio_es_nulo(): void
    {
        $this->assertNull(Extracto::recortar(null, 200));
        $this->assertNull(Extracto::recortar("  \n ", 200));
        $this->assertNull(Extracto::recortar('<p></p>', 200));
    }

    public function test_un_texto_largo_se_corta_en_el_ultimo_espacio_y_nunca_pasa_del_maximo(): void
    {
        $largo = str_repeat('constancia ', 40);

        $extracto = Extracto::recortar($largo, 50);

        $this->assertNotNull($extracto);
        $this->assertLessThanOrEqual(50, mb_strlen($extracto));
        $this->assertStringEndsWith('constancia…', $extracto);
    }

    public function test_quita_la_puntuacion_antes_de_los_puntos_suspensivos(): void
    {
        $this->assertSame('uno dos…', Extracto::recortar('uno dos, tres cuatro cinco', 12));
    }

    public function test_una_sola_palabra_mas_larga_que_el_maximo_se_corta_dura(): void
    {
        $extracto = Extracto::recortar(str_repeat('a', 100), 10);

        $this->assertNotNull($extracto);
        $this->assertLessThanOrEqual(10, mb_strlen($extracto));
        $this->assertStringEndsWith('…', $extracto);
    }

    public function test_cuenta_caracteres_y_no_bytes(): void
    {
        $extracto = Extracto::recortar(str_repeat('ñandú ', 20), 20);

        $this->assertNotNull($extracto);
        $this->assertLessThanOrEqual(20, mb_strlen($extracto));
        $this->assertTrue(mb_check_encoding($extracto, 'UTF-8'));
    }
}
