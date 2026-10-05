<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Ingesta;

use App\Support\Ingesta\MapeoFicha;
use PHPUnit\Framework\TestCase;

/**
 * Tests para métodos públicos de MapeoFicha.
 *
 * @covers \App\Support\Ingesta\MapeoFicha
 */
final class MapeoFichaMetodosPublicosTest extends TestCase
{
    private MapeoFicha $mapeo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapeo = new MapeoFicha;
    }

    public function test_atributos_que_faltan_devuelve_lista_vacia_cuando_todo_presente(): void
    {
        $atributos = [
            'modalidad' => 'SemiPresencial',
            'tiene_costo' => 'SI',
            'tiempo_solucion_dias' => 5,
            'canal_inicio' => 'Presencial',
            'consulta_estado' => '/seguimiento',
            'requisitos' => [['descripcion' => 'Documento']],
            'url_ficha_gov_co' => 'https://gov.co/ficha',
        ];

        $faltan = $this->mapeo->atributosQueFaltan($atributos);

        $this->assertSame([], $faltan);
    }

    public function test_atributos_que_faltan_detecta_nulos(): void
    {
        $atributos = [
            'modalidad' => null,
            'tiene_costo' => 'SI',
            'tiempo_solucion_dias' => 5,
            'canal_inicio' => 'Presencial',
            'consulta_estado' => '/seguimiento',
            'requisitos' => [['descripcion' => 'Documento']],
            'url_ficha_gov_co' => 'https://gov.co/ficha',
        ];

        $faltan = $this->mapeo->atributosQueFaltan($atributos);

        $this->assertSame(['modalidad'], $faltan);
    }

    public function test_atributos_que_faltan_detecta_strings_vacios(): void
    {
        $atributos = [
            'modalidad' => '',
            'tiene_costo' => 'SI',
            'tiempo_solucion_dias' => 5,
            'canal_inicio' => 'Presencial',
            'consulta_estado' => '/seguimiento',
            'requisitos' => [['descripcion' => 'Documento']],
            'url_ficha_gov_co' => 'https://gov.co/ficha',
        ];

        $faltan = $this->mapeo->atributosQueFaltan($atributos);

        $this->assertSame(['modalidad'], $faltan);
    }

    public function test_atributos_que_faltan_detecta_arrays_vacios(): void
    {
        $atributos = [
            'modalidad' => 'SemiPresencial',
            'tiene_costo' => 'SI',
            'tiempo_solucion_dias' => 5,
            'canal_inicio' => 'Presencial',
            'consulta_estado' => '/seguimiento',
            'requisitos' => [],
            'url_ficha_gov_co' => 'https://gov.co/ficha',
        ];

        $faltan = $this->mapeo->atributosQueFaltan($atributos);

        $this->assertSame(['requisitos'], $faltan);
    }

    public function test_atributos_que_faltan_devuelve_multiples_faltantes(): void
    {
        $atributos = [
            'modalidad' => null,
            'tiene_costo' => null,
            'tiempo_solucion_dias' => null,
            'canal_inicio' => null,
            'consulta_estado' => null,
            'requisitos' => null,
            'url_ficha_gov_co' => null,
        ];

        $faltan = $this->mapeo->atributosQueFaltan($atributos);

        $this->assertCount(7, $faltan);
    }

    public function test_atributo_que_falta_devuelve_primero(): void
    {
        $atributos = [
            'modalidad' => null,
            'tiene_costo' => 'SI',
            'tiempo_solucion_dias' => 5,
            'canal_inicio' => 'Presencial',
            'consulta_estado' => '/seguimiento',
            'requisitos' => [],
            'url_ficha_gov_co' => 'https://gov.co/ficha',
        ];

        $falta = $this->mapeo->atributoQueFalta($atributos);

        $this->assertSame('modalidad', $falta);
    }

    public function test_atributo_que_falta_devuelve_null_cuando_nada_falta(): void
    {
        $atributos = [
            'modalidad' => 'SemiPresencial',
            'tiene_costo' => 'SI',
            'tiempo_solucion_dias' => 5,
            'canal_inicio' => 'Presencial',
            'consulta_estado' => '/seguimiento',
            'requisitos' => [['descripcion' => 'Documento']],
            'url_ficha_gov_co' => 'https://gov.co/ficha',
        ];

        $falta = $this->mapeo->atributoQueFalta($atributos);

        $this->assertNull($falta);
    }

    public function test_atributos_que_faltan_ignora_campos_no_enumeraods(): void
    {
        $atributos = [
            'modalidad' => 'SemiPresencial',
            'tiene_costo' => 'SI',
            'tiempo_solucion_dias' => 5,
            'canal_inicio' => 'Presencial',
            'consulta_estado' => '/seguimiento',
            'requisitos' => [['descripcion' => 'Documento']],
            'url_ficha_gov_co' => 'https://gov.co/ficha',
            'nombre' => 'Test',
            'otro_campo_extra' => null,
        ];

        $faltan = $this->mapeo->atributosQueFaltan($atributos);

        $this->assertSame([], $faltan);
    }
}
