<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use App\Models\Tramite;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** 0D, D4: la migración se puede retroceder, repetir y se detiene ante datos viejos inválidos. */
final class ChecksDeEnumeracionesMigracionTest extends TestCase
{
    use RefreshDatabase;

    private function cuantos(): int
    {
        return (int) DB::scalar(<<<'SQL'
            select count(*) from pg_constraint
            where contype = 'c' and conname in (
                'consentimientos_evento_check', 'tramite_canales_canal_check', 'tramite_requisitos_tipo_check',
                'tramite_valoraciones_valor_check', 'tramite_valoraciones_estrellas_check',
                'tramite_valoraciones_alguna_check', 'tramites_type_check', 'tramites_modalidad_check',
                'tramites_tiene_costo_check', 'tramites_canal_inicio_check'
            )
        SQL);
    }

    public function test_down_los_quita_y_up_los_vuelve_a_poner_y_es_repetible(): void
    {
        $migracion = require database_path('migrations/2026_11_19_400000_checks_de_enumeraciones.php');
        $this->assertSame(10, $this->cuantos());

        $migracion->down();
        $this->assertSame(0, $this->cuantos());

        $migracion->up();
        $migracion->up();
        $this->assertSame(10, $this->cuantos());
    }

    public function test_un_dato_viejo_invalido_detiene_la_migracion_y_no_deja_nada_a_medias(): void
    {
        $migracion = require database_path('migrations/2026_11_19_400000_checks_de_enumeraciones.php');
        $id = (int) Tramite::factory()->createOne()->getKey();

        $migracion->down();
        DB::table('tramites')->where('id', $id)->update(['modalidad' => 'inventada']);

        try {
            $migracion->up();
            $this->fail('La migración debió detenerse ante la fila inválida.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('tramites_modalidad_check', $e->getMessage());
        }

        $this->assertSame(0, $this->cuantos(), 'La migración dejó restricciones a medias.');
    }
}
