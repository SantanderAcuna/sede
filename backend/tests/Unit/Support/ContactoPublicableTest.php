<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Tramites\ContactoPublicable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Qué datos de contacto de la fuente se publican y cuáles no.
 *
 * Es la regla de la Ley 1581 de 2012 aplicada a la ingesta, y por eso se prueba
 * sola y no sólo a través de la ingesta entera: aquí se ve **por qué** se
 * descarta cada cosa. Lo que la prueba defiende es la asimetría —se publica lo
 * que se puede demostrar institucional y se descarta lo que puede ser de una
 * persona—, y esa asimetría tiene que sobrevivir a que alguien «arregle» la clase
 * para que devuelva el teléfono tal cual.
 *
 * Que el dato venga de una API pública no cambia nada, y es la razón de que estos
 * casos estén escritos con números reales de la fuente: `3007659666` es el móvil
 * que la ficha del certificado de residencia trae en el punto de atención
 * «ALCALDIA LOCAL 3».
 */
final class ContactoPublicableTest extends TestCase
{
    /**
     * Un teléfono institucional se publica tal cual.
     *
     * El fijo del Distrito es conmutador o dependencia, y ya lo publica el pie del
     * sitio: descartarlo perdería un dato de contacto que la Entidad sí sostiene.
     */
    public function test_un_fijo_institucional_se_publica(): void
    {
        $this->assertSame('(5) 4209600', ContactoPublicable::telefono('(5) 4209600'));
        $this->assertSame('(57) (605) 4209600', ContactoPublicable::telefono('(57) (605) 4209600'));
    }

    /**
     * Un móvil de diez dígitos no se publica, esté donde esté el número.
     *
     * Es el caso que la regla existe para atrapar: un punto de atención puede
     * estar a nombre de una oficina y su teléfono a nombre de quien la atiende.
     */
    #[DataProvider('moviles')]
    public function test_un_movil_no_se_publica(string $declarado): void
    {
        $this->assertNull(
            ContactoPublicable::telefono($declarado),
            sprintf('«%s» es un móvil y no debe publicarse.', $declarado),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function moviles(): array
    {
        return [
            'diez dígitos que empiezan por 3' => ['3007659666'],
            'con separadores' => ['300 765 9666'],
            'con el indicativo del país' => ['+57 300 7659666'],
            'sin el más' => ['573007659666'],
        ];
    }

    /**
     * Un campo con varios números se filtra **número a número**.
     *
     * Es el caso real de la Dirección de Impuestos, que publica dos extensiones
     * del conmutador. Descartar la cadena entera por llevar un móvil al lado
     * perdería también el fijo, y publicarla entera por llevar un fijo publicaría
     * el móvil: las dos salidas son malas, así que se filtra uno a uno.
     */
    public function test_de_una_lista_se_queda_solo_lo_institucional(): void
    {
        $this->assertSame(
            '(5) 4209600 - 1231, (5) 4209600 - 1232',
            ContactoPublicable::telefono('(5) 4209600 - 1231, (5) 4209600 - 1232'),
        );

        $this->assertSame(
            '(5) 4209600',
            ContactoPublicable::telefono('(5) 4209600, 3007659666'),
            'El fijo se queda y el móvil se va.',
        );

        $this->assertNull(
            ContactoPublicable::telefono('3007659666, 3101111111'),
            'Si no hay ningún número institucional, no hay teléfono que publicar.',
        );
    }

    /** El vacío y el blanco no son un teléfono. */
    public function test_el_vacio_no_es_un_telefono(): void
    {
        $this->assertNull(ContactoPublicable::telefono(null));
        $this->assertNull(ContactoPublicable::telefono(''));
        $this->assertNull(ContactoPublicable::telefono('   '));
    }

    /**
     * Las coordenadas que no son de Colombia se descartan.
     *
     * La fuente trae `Latitud: "20"` y `Longitud: "30"` para el punto «ALCALDIA
     * LOCAL 3», que caen en el golfo de Guinea: publicarlas pondría el marcador en
     * el Atlántico, y un mapa que miente es peor que un mapa sin marcador.
     */
    public function test_las_coordenadas_de_ningun_sitio_se_descartan(): void
    {
        $fuera = ContactoPublicable::coordenadas(20.0, 30.0);

        $this->assertNull($fuera['latitud']);
        $this->assertNull($fuera['longitud']);
    }

    /** Y las que sí son de Santa Marta se publican. */
    public function test_las_coordenadas_reales_se_publican(): void
    {
        $dentro = ContactoPublicable::coordenadas(11.24516286, -74.21307865);

        $this->assertSame(11.24516286, $dentro['latitud']);
        $this->assertSame(-74.21307865, $dentro['longitud']);
    }

    /**
     * Una coordenada suelta no basta: hacen falta las dos.
     *
     * Media coordenada no sitúa nada, y publicarla dejaría un punto con latitud y
     * sin longitud que ningún mapa sabe dibujar.
     */
    public function test_media_coordenada_no_se_publica(): void
    {
        $mitad = ContactoPublicable::coordenadas(11.24516286, null);

        $this->assertNull($mitad['latitud']);
        $this->assertNull($mitad['longitud']);
    }

    /**
     * El correo se publica sólo si tiene forma de correo.
     *
     * La fuente reutiliza el campo para describir el canal —«Ver tutoriales»
     * aparece dentro de `Correo` en algunas fichas—, y publicar eso como buzón
     * daría una dirección que no existe.
     */
    public function test_solo_se_publica_lo_que_tiene_forma_de_correo(): void
    {
        $this->assertSame('impuesto-ica@santamarta.gov.co', ContactoPublicable::correo('impuesto-ica@santamarta.gov.co'));
        $this->assertNull(ContactoPublicable::correo('Ver tutoriales'));
        $this->assertNull(ContactoPublicable::correo('Página web'));
    }

    /**
     * Los buzones de área de la Entidad son institucionales y se publican.
     *
     * No son datos de una persona: `gestiondecadaveres@santamarta.gov.co` es el
     * buzón del área que atiende el trámite, y es el canal por el que la Entidad
     * pide que se radique.
     */
    public function test_los_buzones_de_area_de_la_entidad_se_publican(): void
    {
        $this->assertSame(
            'gestiondecadaveres@santamarta.gov.co',
            ContactoPublicable::correo('gestiondecadaveres@santamarta.gov.co'),
        );
    }
}
