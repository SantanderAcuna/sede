<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use App\Models\Tramite;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 0D, D4: la base rechaza los valores fuera de las listas que el código ya define.
 * Cada caso corre en un punto de guardado: en PostgreSQL un error aborta la transacción entera.
 */
final class ChecksDeEnumeracionesTest extends TestCase
{
    use RefreshDatabase;

    /** @param \Closure(): mixed $escritura */
    private function rechaza(\Closure $escritura, string $que): void
    {
        DB::beginTransaction();

        try {
            $escritura();
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $this->assertSame('23514', $e->getCode(), $que.': se esperaba una violación de CHECK, no '.$e->getMessage());

            return;
        }

        $this->fail($que.': la base aceptó un valor inválido.');
    }

    /** @param \Closure(): mixed $escritura */
    private function acepta(\Closure $escritura): void
    {
        $escritura();
        $this->addToAssertionCount(1);
    }

    private function tramiteId(): int
    {
        return (int) Tramite::factory()->createOne()->getKey();
    }

    public function test_consentimientos_evento(): void
    {
        $fila = fn (string $e): array => ['evento' => $e, 'categorias' => '[]', 'version_politica' => 1];

        $this->rechaza(fn () => DB::table('consentimientos')->insert($fila('inventado')), 'consentimientos.evento');
        foreach (['aceptado', 'rechazado', 'revocado', 'caducado'] as $evento) {
            $this->acepta(fn () => DB::table('consentimientos')->insert($fila($evento)));
        }
    }

    public function test_tramite_canales_canal(): void
    {
        $id = $this->tramiteId();
        $fila = fn (string $c): array => ['tramite_id' => $id, 'orden' => 1, 'canal' => $c];

        $this->rechaza(fn () => DB::table('tramite_canales')->insert($fila('paloma')), 'tramite_canales.canal');
        foreach (['sede', 'presencial', 'telefonico', 'web', 'correo', 'otro'] as $canal) {
            $this->acepta(fn () => DB::table('tramite_canales')->insert($fila($canal)));
        }
    }

    public function test_tramite_requisitos_tipo(): void
    {
        $id = $this->tramiteId();
        $fila = fn (string $t): array => ['tramite_id' => $id, 'orden' => 1, 'tipo' => $t, 'descripcion' => 'd'];

        $this->rechaza(fn () => DB::table('tramite_requisitos')->insert($fila('otro_tipo')), 'tramite_requisitos.tipo');
        foreach (['documento', 'verificacion_institucional', 'solicitud', 'formulario', 'pago'] as $tipo) {
            $this->acepta(fn () => DB::table('tramite_requisitos')->insert($fila($tipo)));
        }
    }

    public function test_tramite_valoraciones_valor_estrellas_y_al_menos_una(): void
    {
        $id = $this->tramiteId();
        $fila = fn (?string $v, ?int $e): array => ['tramite_id' => $id, 'valor' => $v, 'estrellas' => $e];

        $this->rechaza(fn () => DB::table('tramite_valoraciones')->insert($fila('regular', null)), 'valor fuera de lista');
        $this->rechaza(fn () => DB::table('tramite_valoraciones')->insert($fila(null, 0)), 'estrellas = 0');
        $this->rechaza(fn () => DB::table('tramite_valoraciones')->insert($fila(null, 6)), 'estrellas = 6');
        $this->rechaza(fn () => DB::table('tramite_valoraciones')->insert($fila(null, null)), 'ninguna de las dos');

        $this->acepta(fn () => DB::table('tramite_valoraciones')->insert($fila('facil', null)));
        $this->acepta(fn () => DB::table('tramite_valoraciones')->insert($fila('dificil', 1)));
        $this->acepta(fn () => DB::table('tramite_valoraciones')->insert($fila(null, 5)));
    }

    public function test_tramites_type_modalidad_tiene_costo_y_canal_inicio(): void
    {
        $id = $this->tramiteId();

        foreach (['type', 'modalidad', 'tiene_costo', 'canal_inicio'] as $columna) {
            $this->rechaza(
                fn () => DB::table('tramites')->where('id', $id)->update([$columna => 'inventado']),
                "tramites.{$columna}",
            );
        }

        $this->acepta(fn () => DB::table('tramites')->where('id', $id)->update([
            'type' => 'opa', 'modalidad' => 'en_linea', 'tiene_costo' => 'con_costo', 'canal_inicio' => 'portal_nacional',
        ]));
        $this->acepta(fn () => DB::table('tramites')->where('id', $id)->update(['modalidad' => null, 'tiene_costo' => null]));
    }
}
