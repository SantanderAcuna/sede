<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use App\Models\Tramite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/** 0D, D5: la migración se retrocede, se repite y se detiene ante una contradicción sin cambiar nada. */
final class RequisitosDelMismoTramiteMigracionTest extends TestCase
{
    use RefreshDatabase;

    private function existe(string $restriccion): bool
    {
        return (bool) DB::scalar('select exists (select 1 from pg_constraint where conname = ?)', [$restriccion]);
    }

    public function test_down_devuelve_la_clave_simple_y_up_la_vuelve_a_hacer_compuesta(): void
    {
        $migracion = require database_path('migrations/2026_11_19_500000_requisito_con_paso_de_su_tramite.php');
        $this->assertTrue($this->existe('tramite_requisitos_paso_tramite_foreign'));

        $migracion->down();
        $this->assertFalse($this->existe('tramite_requisitos_paso_tramite_foreign'));
        $this->assertTrue($this->existe('tramite_requisitos_paso_id_foreign'));
        $this->assertFalse($this->existe('tramite_pasos_id_tramite_id_unique'));

        $migracion->up();
        $this->assertTrue($this->existe('tramite_requisitos_paso_tramite_foreign'));
        $this->assertFalse($this->existe('tramite_requisitos_paso_id_foreign'));
        $this->assertTrue($this->existe('tramite_pasos_id_tramite_id_unique'));
    }

    public function test_ante_un_requisito_con_paso_ajeno_no_cambia_nada_y_lista_las_filas(): void
    {
        $migracion = require database_path('migrations/2026_11_19_500000_requisito_con_paso_de_su_tramite.php');
        $uno = (int) Tramite::factory()->createOne()->getKey();
        $otro = (int) Tramite::factory()->createOne()->getKey();
        $pasoDelOtro = (int) DB::table('tramite_pasos')->insertGetId(['tramite_id' => $otro, 'orden' => 1, 'titulo' => 'P']);

        $migracion->down();
        $requisito = (int) DB::table('tramite_requisitos')->insertGetId([
            'tramite_id' => $uno, 'paso_id' => $pasoDelOtro, 'orden' => 1, 'tipo' => 'documento', 'descripcion' => 'd',
        ]);

        try {
            $migracion->up();
            $this->fail('La migración debió detenerse.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("requisito {$requisito} (trámite {$uno}) → paso {$pasoDelOtro} (trámite {$otro})", $e->getMessage());
        }

        $this->assertFalse($this->existe('tramite_requisitos_paso_tramite_foreign'));
        $this->assertTrue($this->existe('tramite_requisitos_paso_id_foreign'), 'La migración cambió el esquema antes de detenerse.');
        $this->assertFalse($this->existe('tramite_pasos_id_tramite_id_unique'));
    }
}
