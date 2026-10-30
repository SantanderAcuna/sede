<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Busqueda\TextoBuscable;
use PHPUnit\Framework\TestCase;

/**
 * La normalización del texto buscable.
 *
 * Fija la **salida**, no la implementación: lo que ya está guardado en la base
 * se produjo con esta función, y si su resultado cambia sin que se reindexe,
 * la búsqueda empieza a fallar justo en lo que se guardó antes del cambio.
 * Una prueba que dependa sólo de comparar la función consigo misma no lo vería.
 */
final class TextoBuscableTest extends TestCase
{
    public function test_quita_tildes_y_eñes_y_pasa_a_minusculas(): void
    {
        $this->assertSame('tramite de nandu', TextoBuscable::normalizar('  Trámite de Ñandú '));
    }

    public function test_quita_los_comodines_de_like(): void
    {
        $this->assertSame('100libre', TextoBuscable::normalizar('100%_libre'));
        $this->assertSame('', TextoBuscable::normalizar('%'));
    }

    public function test_conserva_los_espacios_y_los_parentesis_de_la_fuente(): void
    {
        // Los nombres de la fuente traen «(s)» y dobles espacios; no se tocan.
        $this->assertSame(
            'liquidacion impuesto(s)  predial unificado',
            TextoBuscable::normalizar('Liquidación impuesto(s)  predial unificado'),
        );
    }

    public function test_el_texto_de_un_tramite_une_nombre_y_resumen(): void
    {
        $this->assertSame(
            'certificado de residencia constancia de domicilio',
            TextoBuscable::deTramite('Certificado de residencia', 'Constancia de domicilio'),
        );
        $this->assertSame('certificado', TextoBuscable::deTramite('Certificado', null));
    }

    public function test_el_texto_de_una_politica_incluye_nombre_apartados_y_parrafos(): void
    {
        $texto = TextoBuscable::dePolitica('Uso de cookies', [
            ['id' => 'duracion', 'titulo' => 'Duración', 'parrafos' => ['Un año.', 'Renovable.']],
            ['id' => 'contacto', 'titulo' => 'Contacto'],
        ]);

        $this->assertSame('uso de cookies duracion un ano. renovable. contacto', $texto);
    }
}
