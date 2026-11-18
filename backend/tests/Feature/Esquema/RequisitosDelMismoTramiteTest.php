<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use App\Models\Tramite;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 0D, D5 (R6, FNBC): un requisito solo puede apuntar a un paso de SU trámite.
 * `paso_id` determinaba `tramite_id` sin ser clave: la base ahora lo hace cumplir con una clave
 * foránea compuesta `(paso_id, tramite_id) → tramite_pasos (id, tramite_id)`.
 */
final class RequisitosDelMismoTramiteTest extends TestCase
{
    use RefreshDatabase;

    private function tramite(): int
    {
        return (int) Tramite::factory()->createOne()->getKey();
    }

    private function paso(int $tramite): int
    {
        return (int) DB::table('tramite_pasos')->insertGetId(['tramite_id' => $tramite, 'orden' => 1, 'titulo' => 'Paso']);
    }

    /** @return array<string, mixed> */
    private function requisito(int $tramite, ?int $paso): array
    {
        return ['tramite_id' => $tramite, 'paso_id' => $paso, 'orden' => 1, 'tipo' => 'documento', 'descripcion' => 'd'];
    }

    public function test_un_requisito_con_el_paso_de_otro_tramite_es_rechazado(): void
    {
        $uno = $this->tramite();
        $otro = $this->tramite();
        $pasoDelOtro = $this->paso($otro);

        try {
            DB::beginTransaction();
            DB::table('tramite_requisitos')->insert($this->requisito($uno, $pasoDelOtro));
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $this->assertSame('23503', $e->getCode(), 'Se esperaba una violación de clave foránea.');

            return;
        }

        $this->fail('La base aceptó un requisito cuyo paso es de otro trámite.');
    }

    public function test_un_requisito_con_el_paso_de_su_tramite_o_sin_paso_es_aceptado(): void
    {
        $id = $this->tramite();
        $paso = $this->paso($id);

        DB::table('tramite_requisitos')->insert($this->requisito($id, $paso));
        DB::table('tramite_requisitos')->insert($this->requisito($id, null));

        $this->assertSame(2, DB::table('tramite_requisitos')->where('tramite_id', $id)->count());
    }

    public function test_borrar_el_paso_deja_el_requisito_sin_paso_y_en_su_tramite(): void
    {
        $id = $this->tramite();
        $paso = $this->paso($id);
        DB::table('tramite_requisitos')->insert($this->requisito($id, $paso));

        DB::table('tramite_pasos')->where('id', $paso)->delete();

        $fila = DB::table('tramite_requisitos')->where('tramite_id', $id)->first();
        $this->assertNotNull($fila, 'El requisito no debió borrarse con su paso.');
        $this->assertNull($fila->paso_id);
        $this->assertSame($id, (int) $fila->tramite_id);
    }

    public function test_borrar_el_tramite_borra_sus_pasos_y_requisitos(): void
    {
        $id = $this->tramite();
        DB::table('tramite_requisitos')->insert($this->requisito($id, $this->paso($id)));

        DB::table('tramites')->where('id', $id)->delete();

        $this->assertSame(0, DB::table('tramite_pasos')->where('tramite_id', $id)->count());
        $this->assertSame(0, DB::table('tramite_requisitos')->where('tramite_id', $id)->count());
    }
}
