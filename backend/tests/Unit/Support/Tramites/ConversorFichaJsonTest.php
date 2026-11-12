<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Tramites;

use App\Support\Tramites\ConversorFichaJson;
use PHPUnit\Framework\TestCase;

/**
 * La conversión de la ficha en bloques JSON a filas propias no puede perder contenido:
 * se prueba con las formas irregulares que el catálogo real trae.
 */
final class ConversorFichaJsonTest extends TestCase
{
    public function test_un_requisito_sin_descripcion_toma_el_nombre_del_documento_o_formulario(): void
    {
        $r = ConversorFichaJson::convertir(['requisitos' => [
            ['tipo' => 'documento', 'documento' => 'Cédula de ciudadanía'],
            ['tipo' => 'formulario', 'formulario' => 'Formulario único', 'formulario_url' => 'https://ejemplo.gov.co/f.pdf'],
            ['tipo' => 'documento'],
        ]]);

        $this->assertCount(2, $r['requisitos']);
        $this->assertSame('Cédula de ciudadanía', $r['requisitos'][0]['descripcion']);
        $this->assertSame('https://ejemplo.gov.co/f.pdf', $r['requisitos'][1]['url']);
        $this->assertTrue($r['requisitos'][0]['obligatorio']);
    }

    public function test_una_solicitud_sin_texto_se_conserva_con_una_descripcion_neutra(): void
    {
        $r = ConversorFichaJson::convertir(['requisitos' => [['tipo' => 'solicitud', 'descripcion' => '']]]);

        $this->assertSame('Presentar la solicitud', $r['requisitos'][0]['descripcion']);
    }

    public function test_los_requisitos_de_un_paso_quedan_en_ese_paso_y_no_se_repiten_los_generales(): void
    {
        $r = ConversorFichaJson::convertir([
            'momentos' => [
                ['orden' => 1, 'titulo' => 'Radique la solicitud', 'descripcion' => 'En ventanilla', 'requisitos' => [
                    ['tipo' => 'documento', 'descripcion' => 'Cédula'],
                ]],
                ['orden' => 2, 'titulo' => 'Reciba la respuesta'],
            ],
            'requisitos' => [
                ['tipo' => 'documento', 'descripcion' => ' cédula '],
                ['tipo' => 'solicitud', 'descripcion' => 'Carta de solicitud'],
            ],
        ]);

        $this->assertSame([1, 2], array_column($r['pasos'], 'orden'));
        $this->assertCount(2, $r['requisitos']);
        $this->assertSame(1, $r['requisitos'][0]['paso']);
        $this->assertNull($r['requisitos'][1]['paso']);
        $this->assertSame([1, 2], array_column($r['requisitos'], 'orden'));
    }

    public function test_lo_que_no_cabe_en_una_columna_pasa_a_la_nota_en_palabras(): void
    {
        $r = ConversorFichaJson::convertir(['requisitos' => [[
            'tipo' => 'pago', 'descripcion' => 'Pago de la tasa', 'cantidad' => 2, 'unidad_cantidad' => 'copias',
            'pago' => [['valor' => '45000', 'moneda' => 'COP']],
            'cuentas' => [['banco' => 'Banco X', 'numero' => '123']],
        ]]]);

        $nota = (string) $r['requisitos'][0]['nota'];
        $this->assertStringContainsString('Cantidad: 2 copias', $nota);
        $this->assertStringContainsString('Valor: 45000', $nota);
        $this->assertStringContainsString('Banco X', $nota);
    }

    public function test_el_tipo_de_solicitante_sale_de_los_grupos_de_la_audiencia_y_de_los_requisitos(): void
    {
        $r = ConversorFichaJson::convertir([
            'audiencias' => [
                ['grupo' => 'Ciudadano', 'nombre' => 'Infancia'], ['grupo' => 'Ciudadano', 'nombre' => 'Adulto'],
                ['grupo' => 'Organizaciones', 'nombre' => 'Grandes empresas'],
            ],
            'momentos' => [['titulo' => 'Paso', 'requisitos' => [['tipo' => 'solicitud', 'descripcion' => 'x', 'tipos_audiencia' => ['Instituciones o dependencias públicas']]]]],
        ]);

        $this->assertEqualsCanonicalizing(['ciudadano', 'organizaciones', 'instituciones'], $r['audiencias']);
        $this->assertSame([], ConversorFichaJson::convertir([])['audiencias']);
    }

    public function test_las_cuentas_con_dos_vocabularios_se_unifican_sin_repetirse(): void
    {
        $r = ConversorFichaJson::convertir([
            'costo_cuentas' => [['entidad' => 'Banco X', 'numero_cuenta' => '123', 'tipo_cuenta' => 'Ahorros']],
            'cuentas' => [['banco' => 'Banco X', 'numero' => '123'], ['banco' => 'Banco Y', 'numero' => '9']],
        ]);

        $this->assertCount(2, $r['cuentas']);
        $this->assertSame('Ahorros', $r['cuentas'][0]['tipo']);
    }

    public function test_el_municipio_como_objeto_se_convierte_en_texto_y_sin_puntos_toma_el_seguimiento_presencial(): void
    {
        $r = ConversorFichaJson::convertir(['seguimiento' => ['presencial' => [
            ['nombre' => 'Ventanilla', 'direccion' => 'Calle 1', 'municipio' => ['codigo' => '47001', 'nombre' => 'Santa Marta'], 'latitud' => '11.24'],
            ['nombre' => 'Sin dirección'],
        ]]]);

        $this->assertCount(1, $r['puntos_atencion']);
        $this->assertSame('Santa Marta', $r['puntos_atencion'][0]['municipio']);
        $this->assertSame(11.24, $r['puntos_atencion'][0]['latitud']);
    }

    public function test_el_resultado_cae_al_producto_final_y_las_entradas_vacias_o_raras_no_rompen(): void
    {
        $this->assertSame('Un certificado', ConversorFichaJson::convertir(['producto_final' => 'Un certificado'])['resultado']);

        $vacio = ConversorFichaJson::convertir(['requisitos' => 'texto', 'momentos' => [null, 5], 'normativa' => [['numero' => '1']]]);
        $this->assertSame([], $vacio['requisitos']);
        $this->assertSame([], $vacio['pasos']);
        $this->assertSame([], $vacio['normativa']);
    }

    public function test_una_url_invalida_se_descarta(): void
    {
        $r = ConversorFichaJson::convertir(['documentos' => [['nombre' => 'Guía', 'url' => 'no es url', 'formato' => 'pdf']]]);

        $this->assertNull($r['documentos'][0]['url']);
        $this->assertSame('pdf', $r['documentos'][0]['formato']);
    }
}
