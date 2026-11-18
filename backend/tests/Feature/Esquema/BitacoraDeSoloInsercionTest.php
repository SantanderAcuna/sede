<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use App\Models\AuditLog;
use App\Support\RolesBaseDeDatos;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 0D, D6 (R7): `audit_log` es de solo inserción. Dos barreras:
 *  1. permisos: `sede_app` no puede modificar ni borrar (y `aplicar()`, que corre en cada
 *     despliegue, no lo vuelve a conceder);
 *  2. trigger: `UPDATE`, `DELETE` y `TRUNCATE` se rechazan para cualquiera, también el dueño.
 */
final class BitacoraDeSoloInsercionTest extends TestCase
{
    use RefreshDatabase;

    private function fila(): int
    {
        return (int) DB::table('audit_log')->insertGetId([
            'accion' => 'prueba', 'recurso' => 'x', 'ip_origen' => '127.0.0.1',
        ]);
    }

    /**
     * @param  literal-string  $sql  con `?` para los valores: nada se interpola en el SQL.
     * @param  list<int|string>  $valores
     */
    private function rechazada(string $sql, array $valores = []): void
    {
        DB::beginTransaction();

        try {
            DB::statement($sql, $valores);
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $this->assertStringContainsString('audit_log es de solo inserción', $e->getMessage());

            return;
        }

        $this->fail("La base aceptó: {$sql}");
    }

    public function test_insertar_y_leer_siguen_funcionando(): void
    {
        $id = $this->fila();

        $this->assertSame('prueba', DB::table('audit_log')->where('id', $id)->value('accion'));
    }

    public function test_update_delete_y_truncate_se_rechazan_incluso_para_el_dueno(): void
    {
        $id = $this->fila();

        $this->rechazada("update audit_log set accion = 'otra' where id = ?", [$id]);
        $this->rechazada('delete from audit_log where id = ?', [$id]);
        $this->rechazada('truncate audit_log');

        $this->assertSame('prueba', DB::table('audit_log')->where('id', $id)->value('accion'));
    }

    public function test_sede_app_solo_puede_insertar_y_leer(): void
    {
        $tiene = fn (string $privilegio): bool => (bool) DB::scalar(
            "select has_table_privilege('sede_app', 'audit_log', ?)",
            [$privilegio],
        );

        $this->assertTrue($tiene('INSERT'));
        $this->assertTrue($tiene('SELECT'));
        $this->assertFalse($tiene('UPDATE'), 'sede_app puede modificar la bitácora.');
        $this->assertFalse($tiene('DELETE'), 'sede_app puede borrar la bitácora.');
        $this->assertFalse($tiene('TRUNCATE'));
    }

    public function test_sincronizar_los_roles_de_nuevo_no_devuelve_los_permisos(): void
    {
        RolesBaseDeDatos::aplicar();
        RolesBaseDeDatos::aplicar();

        foreach (['UPDATE', 'DELETE'] as $privilegio) {
            $this->assertFalse((bool) DB::scalar("select has_table_privilege('sede_app', 'audit_log', ?)", [$privilegio]), $privilegio);
        }
        $this->assertTrue((bool) DB::scalar("select has_table_privilege('sede_app', 'audit_log', 'INSERT')"));
        $this->assertTrue((bool) DB::scalar("select has_table_privilege('sede_app', 'users', 'UPDATE')"), 'Las demás tablas no deben cambiar.');
    }

    public function test_cambios_es_jsonb_y_el_modelo_lo_sigue_leyendo_como_arreglo(): void
    {
        $tipo = DB::scalar(<<<'SQL'
            select data_type from information_schema.columns
            where table_schema = 'public' and table_name = 'audit_log' and column_name = 'cambios'
        SQL);
        $this->assertSame('jsonb', $tipo);

        $registro = AuditLog::query()->create(['accion' => 'prueba', 'recurso' => 'x', 'ip_origen' => '127.0.0.1', 'cambios' => ['antes' => 1, 'despues' => 2]]);

        $this->assertSame(['antes' => 1, 'despues' => 2], $registro->fresh()?->cambios);
    }

    public function test_la_migracion_se_retrocede_y_se_repite(): void
    {
        $migracion = require database_path('migrations/2026_11_19_600000_bitacora_de_solo_insercion.php');
        $id = $this->fila();

        $migracion->down();
        $this->assertSame(0, (int) DB::scalar("select count(*) from pg_trigger where tgrelid = 'audit_log'::regclass and not tgisinternal"));
        DB::table('audit_log')->where('id', $id)->update(['accion' => 'editada']); // sin trigger sí se puede

        $migracion->up();
        $migracion->up();
        $this->assertSame(2, (int) DB::scalar("select count(*) from pg_trigger where tgrelid = 'audit_log'::regclass and not tgisinternal"));
        $this->rechazada('delete from audit_log where id = ?', [$id]);
    }
}
