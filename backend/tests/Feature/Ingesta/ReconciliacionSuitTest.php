<?php

declare(strict_types=1);

namespace Tests\Feature\Ingesta;

use App\Models\Tramite;
use App\Services\ReconciliacionSuit;
use App\Support\Tramites\FuenteSuit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JsonException;
use Tests\TestCase;

/**
 * La conciliación entre el catálogo de SUIT y el listado del sitio del Distrito.
 *
 * Se prueba contra las **dos copias congeladas reales** y no contra una fuente de
 * mentira. Es deliberado: lo que estas pruebas protegen son las cinco cifras que
 * la Entidad va a usar para decidir qué hacer con su catálogo —16, 2, 8, 2 y 23—,
 * y una fuente inventada sólo comprobaría que la aritmética del servicio funciona.
 * Si alguien cambia una copia sin querer, la prueba lo dice.
 *
 * La conciliación **informa y no decide**: por eso la última prueba comprueba que
 * comparar no escribe nada en el catálogo.
 */
final class ReconciliacionSuitTest extends TestCase
{
    // La conciliación no escribe nada, y para poder afirmarlo hace falta una base
    // sobre la que comprobar que sigue vacía después de comparar.
    use RefreshDatabase;

    /** Cuántos códigos tiene el catálogo ratificado. */
    private const CODIGOS_SUIT = 124;

    /** Cuántas entradas publica el sitio del Distrito, con sus duplicados dentro. */
    private const ENTRADAS_DISTRITO = 118;

    /** Cuántos códigos únicos salen de esas entradas. */
    private const CODIGOS_DISTRITO = 110;

    public function test_la_conciliacion_da_las_cinco_cifras_del_expediente(): void
    {
        $comparacion = $this->comparar();

        // 16 trámites vigentes que el ciudadano no encuentra en el sitio del
        // Distrito: urbanismo, catastro y movilidad, entre otros.
        $this->assertCount(16, $comparacion['solo_suit']);

        // 2 que el sitio publica y SUIT no registra, los dos retiros de SISBEN.
        $this->assertCount(2, $comparacion['solo_distrito']);
        $this->assertSame(
            ['T13787', 'T6126'],
            array_column($comparacion['solo_distrito'], 'codigo'),
        );

        // 8 códigos repetidos en el listado del Distrito.
        $this->assertCount(8, $comparacion['duplicados']);

        // 2 de esos repetidos aparecen además con dos nombres distintos.
        $this->assertCount(2, $comparacion['dos_nombres']);
        $this->assertSame(
            ['T65978', 'T65981'],
            array_column($comparacion['dos_nombres'], 'codigo'),
        );

        // Y 23 veces el mismo código se llama de una manera en SUIT y de otra en
        // el sitio. Son 23 y no 24 porque T65978 y T65981 publican los dos nombres
        // —el de SUIT y el otro—, así que uno de ellos sí coincide: contarlos como
        // discrepancia mandaría a arreglar algo que ya está bien en una entrada.
        $this->assertCount(23, $comparacion['nombres_diferentes']);

        $this->assertSame(self::CODIGOS_SUIT, $comparacion['codigos_suit']);
        $this->assertSame(self::ENTRADAS_DISTRITO, $comparacion['entradas_distrito']);
        $this->assertSame(self::CODIGOS_DISTRITO, $comparacion['codigos_distrito']);
    }

    /**
     * La conciliación informa; no decide.
     *
     * Qué trámites tiene la Alcaldía es una decisión suya, y un programa que
     * añadiera los 16 que faltan o borrara los 2 huérfanos estaría publicando y
     * despublicando actos administrativos sin competencia para ello. La prueba
     * comprueba que comparar no escribe ni una fila.
     */
    public function test_conciliar_no_escribe_nada_en_el_catalogo(): void
    {
        $this->assertSame(0, Tramite::query()->count());

        $this->comparar();

        // Se vuelve a contar con `assertCount` sobre una consulta nueva y no con
        // otro `assertSame(0, …)`: el análisis estático memoriza el resultado de la
        // primera comprobación y da por hecho que el segundo cero también lo es,
        // con lo que la segunda aserción dejaría de comprobar nada. Con
        // `assertCount` la tabla se vuelve a leer de verdad.
        $this->assertCount(0, Tramite::query()->get());
    }

    /**
     * @return array{
     *     solo_suit: list<array{codigo: string, nombre: string}>,
     *     solo_distrito: list<array{codigo: string, nombre: string}>,
     *     duplicados: list<array{codigo: string, nombres: list<string>}>,
     *     dos_nombres: list<array{codigo: string, nombres: list<string>}>,
     *     nombres_diferentes: list<array{codigo: string, suit: string, distrito: list<string>}>,
     *     entradas_distrito: int,
     *     codigos_distrito: int,
     *     codigos_suit: int,
     * }
     */
    private function comparar(): array
    {
        return (new ReconciliacionSuit)->comparar($this->titulosSuit());
    }

    /**
     * Los títulos de SUIT, de la copia congelada del catálogo.
     *
     * @return array<string, string>
     */
    private function titulosSuit(): array
    {
        $ruta = database_path(FuenteSuit::COPIA_CATALOGO);

        try {
            $contenido = json_decode((string) file_get_contents($ruta), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            $this->fail(sprintf('La copia de SUIT en %s no es un JSON legible.', $ruta));
        }

        if (! is_array($contenido) || ! isset($contenido['tramites']) || ! is_array($contenido['tramites'])) {
            $this->fail(sprintf('La copia de SUIT en %s no tiene la forma esperada.', $ruta));
        }

        $titulos = [];

        foreach ($contenido['tramites'] as $fila) {
            if (! is_array($fila)) {
                continue;
            }

            $codigo = $fila['id'] ?? null;
            $titulo = $fila['titulo'] ?? null;

            if (is_string($codigo) && is_string($titulo)) {
                $titulos[trim($codigo)] = trim($titulo);
            }
        }

        return $titulos;
    }
}
