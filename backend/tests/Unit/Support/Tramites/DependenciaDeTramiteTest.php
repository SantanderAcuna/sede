<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Tramites;

use App\Support\Tramites\DependenciaDeTramite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DependenciaDeTramiteTest extends TestCase
{
    /** @return array<string, array{list<string>, ?string}> */
    public static function casos(): array
    {
        return [
            'un solo punto, con nombre propio de la dependencia' => [['Secretaría de Educación'], 'Educación'],
            'planeación' => [['Secretaría de Planeación'], 'Planeación'],
            'gobierno' => [['Secretaría de Gobierno'], 'Gobierno'],
            'hacienda' => [['Secretaría de Hacienda'], 'Hacienda'],
            'salud, con sus dos escrituras' => [['Secretaría de Salud', 'Secretaría de Salud Distrital'], 'Salud'],
            'movilidad' => [['Secretaría de Movilidad Multimodal y Sostenible'], 'Movilidad'],
            'catastro, tal como lo escribe el SUIT' => [['Unidad Especial de Catastro Multiproposito'], 'Catastro Multipropósito'],
            'DADSA, con sus dos escrituras' => [['Dadsa. (Departamento distrital de sotenibilidad ambiental)', 'Dadsa (Departamento distrital de sostenibilidad ambiental)'], 'DADSA'],
            'oficina jurídica' => [['Oficina Juridica'], 'Dirección Jurídica'],
            'mayúsculas y tildes no cambian el resultado' => [['SECRETARIA DE EDUCACION'], 'Educación'],
            'un punto sin dependencia propia se ignora si otro sí la tiene' => [['Dirección de Impuestos', 'Secretaría de Gobierno'], 'Gobierno'],
            'una curaduría no es una dependencia y no cuenta' => [['Curaduría Urbana Número 2', 'Secretaría de Planeación'], 'Planeación'],
            'el mismo punto repetido no es una discrepancia' => [['Secretaría de Educación', 'Secretaría de Educación'], 'Educación'],
            'dos dependencias distintas: no se elige, lo decide la Entidad' => [['Secretaría de Planeación', 'Secretaría de Salud'], null],
            'sin ninguna dependencia reconocible: no se inventa' => [['Oficina del SISBÉN', 'Cultura y Deporte'], null],
            'sin puntos' => [[], null],
            'ESITTS no se asume parte de Movilidad' => [['ESITTS'], null],
            'Impuestos no se asume parte de Hacienda' => [['Dirección de Impuestos Distrital'], null],
        ];
    }

    /** @param list<string> $puntos */
    #[DataProvider('casos')]
    public function test_asigna_solo_con_evidencia_directa(array $puntos, ?string $esperada): void
    {
        $this->assertSame($esperada, DependenciaDeTramite::para($puntos));
    }

    public function test_un_volcado_de_todos_los_puntos_no_es_evidencia(): void
    {
        $volcado = array_map(static fn (int $i): string => 'Punto '.$i, range(1, 40));
        $volcado[] = 'Secretaría de Educación';

        $this->assertNull(DependenciaDeTramite::para($volcado));
    }

    public function test_los_nombres_devueltos_existen_en_el_catalogo_de_dependencias(): void
    {
        foreach (DependenciaDeTramite::nombres() as $nombre) {
            $this->assertContains($nombre, ['DADSA', 'Dirección Jurídica', 'Movilidad', 'Gobierno', 'Planeación', 'Educación', 'Hacienda', 'Salud', 'Catastro Multipropósito', 'INRED', 'Alcaldía Local 1', 'Alcaldía Local 2', 'Alcaldía Local 3']);
        }
    }
}
