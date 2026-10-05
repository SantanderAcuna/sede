<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Tramites;

use App\Support\Tramites\ContactoPublicable;
use Tests\TestCase;

/**
 * Unidad de ContactoPublicable.
 */
final class ContactoPublicableTest extends TestCase
{
    public function test_telefono_fijo_es_institucional(): void
    {
        $this->assertTrue(ContactoPublicable::esInstitucional('(5) 4209600'));
        $this->assertTrue(ContactoPublicable::esInstitucional('4209600'));
        $this->assertTrue(ContactoPublicable::esInstitucional('+57 5 4209600'));
    }

    public function test_telefono_movil_10_digitos_no_es_institucional(): void
    {
        // Línea 75: 10 dígitos que empiezan con 3 → móvil, no se publica
        $this->assertFalse(ContactoPublicable::esInstitucional('3007659666'));
        $this->assertFalse(ContactoPublicable::esInstitucional('3101234567'));
    }

    public function test_telefono_movil_12_digitos_con_57_no_es_institucional(): void
    {
        // Línea 79: 12 dígitos que empiezan con 573 → móvil con indicativo
        $this->assertFalse(ContactoPublicable::esInstitucional('573007659666'));
        $this->assertFalse(ContactoPublicable::esInstitucional('573101234567'));
    }

    public function test_telefono_solo_simbolos_devuelve_false(): void
    {
        // Línea 71: después de quitar no-dígitos queda vacío → false
        $this->assertFalse(ContactoPublicable::esInstitucional('abc'));
        $this->assertFalse(ContactoPublicable::esInstitucional('---'));
    }

    public function test_telefono_null_devuelve_null(): void
    {
        $this->assertNull(ContactoPublicable::telefono(null));
        $this->assertNull(ContactoPublicable::telefono(''));
    }

    public function test_telefono_solo_moviles_devuelve_null(): void
    {
        $this->assertNull(ContactoPublicable::telefono('3007659666, 3101234567'));
    }

    public function test_telefono_mixto_filtra_solo_fijos(): void
    {
        $this->assertSame('(5) 4209600, 4209601', ContactoPublicable::telefono('(5) 4209600, 3007659666, 4209601'));
    }

    public function test_correo_invalido_devuelve_null(): void
    {
        $this->assertNull(ContactoPublicable::correo(null));
        $this->assertNull(ContactoPublicable::correo('Ver tutoriales'));
        $this->assertNull(ContactoPublicable::correo('no-es-correo'));
    }

    public function test_correo_valido_devuelve_correo(): void
    {
        $this->assertSame('impuesto-ica@santamarta.gov.co', ContactoPublicable::correo('impuesto-ica@santamarta.gov.co'));
    }

    public function test_coordenadas_dentro_de_colombia(): void
    {
        $resultado = ContactoPublicable::coordenadas(11.0, -74.0);

        $this->assertSame(11.0, $resultado['latitud']);
        $this->assertSame(-74.0, $resultado['longitud']);
    }

    public function test_coordenadas_fuera_de_colombia_devuelve_null(): void
    {
        // Punto en el golfo de Guinea (latitud 20, longitud 30) — fuera de la caja
        $resultado = ContactoPublicable::coordenadas(20.0, 30.0);

        $this->assertNull($resultado['latitud']);
        $this->assertNull($resultado['longitud']);
    }

    public function test_coordenadas_null_devuelven_null(): void
    {
        $resultado = ContactoPublicable::coordenadas(null, null);

        $this->assertNull($resultado['latitud']);
        $this->assertNull($resultado['longitud']);
    }
}
