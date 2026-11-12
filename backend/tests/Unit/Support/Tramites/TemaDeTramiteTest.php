<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Tramites;

use App\Support\Tramites\TemaDeTramite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TemaDeTramiteTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function casos(): array
    {
        return [
            'educación' => ['Acreditación nivel en el escalafón docente', 'educacion'],
            'urbanismo' => ['Licencia de construcción en la modalidad de obra nueva', 'urbanismo-y-construccion'],
            'hacienda' => ['Impuesto predial unificado: corrección de la liquidación', 'hacienda-e-impuestos'],
            'tránsito' => ['Traspaso de vehículo automotor', 'transito-y-movilidad'],
            'salud' => ['Apertura de establecimientos farmacéuticos', 'salud'],
            'ambiente' => ['Permiso de vertimientos y aprovechamiento forestal', 'ambiente'],
            'tildes y mayúsculas' => ['EDUCACIÓN PARA EL TRABAJO', 'educacion'],
            'pensiones' => ['Aprobación pensión(es) de jubilación', 'pensiones-y-prestaciones'],
            'rifas' => ['Autorización operación de juegos de suerte y azar en la modalidad de rifas', 'juegos-rifas-y-espectaculos'],
            'funerarios' => ['Expedición licencia(s) de inhumación de cadáveres', 'salud'],
            'establecimiento educativo' => ['Cambio propiedad de establecimientos educativos', 'educacion'],
            'catastro' => ['Expedición certificado(s) catastral', 'urbanismo-y-construccion'],
            'organizaciones comunales' => ['Registro libro de las organizaciones comunales de primero y segundo grado', 'gobierno-y-convivencia'],
            'sin coincidencia' => ['Constancia de algo distinto', 'otros-tramites'],
        ];
    }

    #[DataProvider('casos')]
    public function test_asigna_el_tema_por_palabras_del_nombre(string $nombre, string $slug): void
    {
        $this->assertSame($slug, TemaDeTramite::slugPara($nombre));
    }

    public function test_cada_slug_asignable_tiene_nombre_y_el_catalogo_trae_el_comodin(): void
    {
        $temas = TemaDeTramite::catalogo();
        $slugs = array_column($temas, 'slug');

        $this->assertContains('otros-tramites', $slugs);
        $this->assertSame(array_unique($slugs), $slugs);
        foreach ($temas as $tema) {
            $this->assertNotSame('', $tema['nombre']);
        }
    }
}
