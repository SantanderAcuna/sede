<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Un requisito solo puede apuntar a un paso de su propio trámite (0D, D5; R6 del plan, FNBC).
 *
 * `tramite_requisitos` guardaba `tramite_id` y `paso_id`, y el paso ya pertenece a un trámite:
 * `paso_id` determinaba `tramite_id` sin ser clave, y nada impedía que se contradijeran. Ahora la
 * clave foránea es compuesta `(paso_id, tramite_id) → tramite_pasos (id, tramite_id)`.
 *
 * - `tramite_pasos (id, tramite_id)` pasa a ser único (lo exige una clave foránea compuesta).
 * - `ON DELETE SET NULL (paso_id)` (PostgreSQL 15 o superior; staging corre 18): al borrar el paso el
 *   requisito queda sin paso y **conserva su trámite**. Un `SET NULL` común pondría también
 *   `tramite_id` en NULL, que es obligatorio, y el borrado fallaría.
 * - Con `paso_id` nulo la clave no se comprueba (`MATCH SIMPLE`): un requisito sin paso sigue valiendo.
 * - El índice `(paso_id, tramite_id)` sirve a la clave y reemplaza al de `paso_id` solo (D3).
 *
 * Si ya hubiera un requisito con el paso de otro trámite, la migración **no cambia nada**, lista las
 * filas y se detiene: cuál de los dos datos es el correcto lo decide una persona.
 */
return new class extends Migration
{
    private const MAXIMO_LISTADO = 50;

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            $contradicciones = DB::select(<<<'SQL'
                select r.id as requisito, r.tramite_id as tramite_del_requisito, p.id as paso, p.tramite_id as tramite_del_paso
                from tramite_requisitos r
                join tramite_pasos p on p.id = r.paso_id
                where p.tramite_id <> r.tramite_id
                order by r.id
                limit 51
            SQL);

            if ($contradicciones !== []) {
                throw new RuntimeException($this->mensaje($contradicciones));
            }

            DB::statement('alter table tramite_pasos add constraint tramite_pasos_id_tramite_id_unique unique (id, tramite_id)');
            DB::statement('alter table tramite_requisitos drop constraint tramite_requisitos_paso_id_foreign');
            DB::statement('drop index if exists tramite_requisitos_paso_id_index');
            DB::statement(<<<'SQL'
                alter table tramite_requisitos add constraint tramite_requisitos_paso_tramite_foreign
                foreign key (paso_id, tramite_id) references tramite_pasos (id, tramite_id)
                on delete set null (paso_id) not valid
            SQL);
            DB::statement('alter table tramite_requisitos validate constraint tramite_requisitos_paso_tramite_foreign');
            DB::statement('create index tramite_requisitos_paso_id_tramite_id_index on tramite_requisitos (paso_id, tramite_id)');
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            DB::statement('alter table tramite_requisitos drop constraint if exists tramite_requisitos_paso_tramite_foreign');
            DB::statement('drop index if exists tramite_requisitos_paso_id_tramite_id_index');
            DB::statement('create index if not exists tramite_requisitos_paso_id_index on tramite_requisitos (paso_id)');
            DB::statement(<<<'SQL'
                alter table tramite_requisitos add constraint tramite_requisitos_paso_id_foreign
                foreign key (paso_id) references tramite_pasos (id) on delete set null
            SQL);
            DB::statement('alter table tramite_pasos drop constraint if exists tramite_pasos_id_tramite_id_unique');
        });
    }

    /** @param list<object> $filas */
    private function mensaje(array $filas): string
    {
        $lineas = array_map(
            fn (object $f): string => "  requisito {$f->requisito} (trámite {$f->tramite_del_requisito}) → paso {$f->paso} (trámite {$f->tramite_del_paso})",
            array_slice($filas, 0, self::MAXIMO_LISTADO),
        );
        $mas = count($filas) > self::MAXIMO_LISTADO ? "\n  … y más (se muestran ".self::MAXIMO_LISTADO.')' : '';

        return "No se creó la clave compuesta: hay requisitos con el paso de otro trámite. Corrija cada uno (¿cuál dato es el correcto: el trámite o el paso?) y repita.\n".implode("\n", $lineas).$mas;
    }
};
