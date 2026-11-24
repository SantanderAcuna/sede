<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La bitácora `audit_log` es de solo inserción (0D, D6; R7 del plan de robustez).
 *
 * Quien comprometa la aplicación no debe poder borrar su propio rastro. Dos barreras:
 *  1. Permisos: `sede_app` solo inserta y lee. Eso lo aplica `RolesBaseDeDatos::aplicar()`, que corre
 *     en cada despliegue (aquí no: el `GRANT` general lo volvería a abrir).
 *  2. Este trigger: `UPDATE`, `DELETE` y `TRUNCATE` se rechazan para cualquiera, también el dueño.
 *
 * `cambios` pasa de `json` a `jsonb`: se valida y se puede indexar. Ningún código del proyecto lee
 * esa columna como texto ni depende del orden de sus claves (el modelo la castea a arreglo).
 *
 * Retención: NO hay depuración automática (se desconoce el plazo legal, Ley 594 de 2000). Si algún
 * día hay que depurar, lo hace una persona con el procedimiento de `docs/bitacora-auditoria.md`.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            // Antes de crear los triggers: la conversión reescribe la tabla.
            DB::statement('alter table audit_log alter column cambios type jsonb using cambios::jsonb');

            DB::unprepared(<<<'SQL'
                create or replace function audit_log_solo_insercion() returns trigger
                language plpgsql as $$
                begin
                    raise exception 'audit_log es de solo inserción: no admite %', tg_op
                        using errcode = 'restrict_violation';
                end;
                $$
            SQL);
            DB::statement('drop trigger if exists audit_log_sin_cambios_fila on audit_log');
            DB::statement('drop trigger if exists audit_log_sin_truncate on audit_log');
            DB::statement('create trigger audit_log_sin_cambios_fila before update or delete on audit_log for each row execute function audit_log_solo_insercion()');
            DB::statement('create trigger audit_log_sin_truncate before truncate on audit_log for each statement execute function audit_log_solo_insercion()');
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");
            DB::statement('drop trigger if exists audit_log_sin_cambios_fila on audit_log');
            DB::statement('drop trigger if exists audit_log_sin_truncate on audit_log');
            DB::statement('drop function if exists audit_log_solo_insercion()');
            DB::statement('alter table audit_log alter column cambios type json using cambios::json');
        });
    }
};
