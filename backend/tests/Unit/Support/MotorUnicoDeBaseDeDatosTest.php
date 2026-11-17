<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

/**
 * PostgreSQL es el único motor del proyecto (Fase 0B, T10).
 *
 * Las pruebas, el CI y el despliegue ya corren en PostgreSQL. Lo que quedaba eran restos
 * de SQLite que no se usan pero engañan: un entorno sin `DB_CONNECTION` caía en `sqlite`
 * y fallaba lejos de la causa («Database file … does not exist»). Estas pruebas leen el
 * código fuente a propósito: el valor por defecto solo se ve cuando la variable falta, y
 * en las pruebas siempre está definida.
 */
final class MotorUnicoDeBaseDeDatosTest extends TestCase
{
    private function leer(string $ruta): string
    {
        $contenido = file_get_contents(dirname(__DIR__, 3).'/'.$ruta);
        $this->assertIsString($contenido, "No se pudo leer {$ruta}");

        return $contenido;
    }

    public function test_la_conexion_por_defecto_es_pgsql(): void
    {
        $this->assertStringContainsString("'default' => env('DB_CONNECTION', 'pgsql')", $this->leer('config/database.php'));
    }

    public function test_la_cola_usa_pgsql_por_defecto(): void
    {
        $cola = $this->leer('config/queue.php');

        $this->assertSame(2, substr_count($cola, "env('DB_CONNECTION', 'pgsql')"));
    }

    public function test_no_hay_conexion_sqlite_en_la_configuracion(): void
    {
        foreach (['config/database.php', 'config/queue.php'] as $archivo) {
            $this->assertStringNotContainsStringIgnoringCase('sqlite', $this->leer($archivo), $archivo);
        }
    }

    public function test_las_migraciones_no_mencionan_sqlite_ni_la_marca_de_pruebas_de_permisos(): void
    {
        foreach (glob(dirname(__DIR__, 3).'/database/migrations/*.php') ?: [] as $ruta) {
            $texto = (string) file_get_contents($ruta);
            $this->assertStringNotContainsStringIgnoringCase('sqlite', $texto, basename($ruta));
            $this->assertStringNotContainsString('permission.testing', $texto, basename($ruta));
        }
    }

    public function test_la_instalacion_inicial_de_composer_no_crea_un_archivo_sqlite(): void
    {
        $this->assertStringNotContainsStringIgnoringCase('sqlite', $this->leer('composer.json'));
    }
}
