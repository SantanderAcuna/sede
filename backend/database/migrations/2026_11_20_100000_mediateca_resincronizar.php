<?php

declare(strict_types=1);

use App\Support\Mediateca\Resincronizador;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mediateca, lanzamiento 2 de 3, pieza 1: RESINCRONIZAR (0D, D9).
 *
 * Entre el despliegue de D8 y este, el código siguió escribiendo solo lo viejo: aquí se vuelve a copiar
 * de lo viejo a lo nuevo para todas las filas (`Resincronizador`, con documentos). Además:
 *  - `politica_documentos` pasa a admitir varios documentos por política: clave (política, archivo),
 *    rol `oficial` o `anterior` y un único `oficial` por política (índice único parcial).
 *  - Índice de la huella de los archivos, para la reutilización por huella.
 *
 * Todo en una transacción: si hay una imagen rota o un archivo sin dueño vinculable, se aborta y se
 * lista, sin cambiar nada (ni siquiera el esquema: en PostgreSQL el DDL también se revierte).
 *
 * `down()` vuelve a la forma de D8. Con varios `anterior` por política deja solo el `oficial` antes de
 * restaurar la clave vieja: solo se pierden vínculos, nunca archivos.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            $this->ajustarDocumentos();
            DB::statement('create index if not exists file_media_hash_sha256_index on file_media (hash_sha256)');

            (new Resincronizador)->ejecutar(true);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            DB::statement('drop index if exists file_media_hash_sha256_index');

            if (! Schema::hasTable('politica_documentos')) {
                return;
            }

            DB::statement("delete from politica_documentos where rol <> 'oficial'");
            DB::statement('drop index if exists politica_documentos_un_oficial');
            $this->reemplazarClave(['politica_id', 'rol']);
            $this->reemplazarRegla("rol in ('oficial')");
        });
    }

    private function ajustarDocumentos(): void
    {
        if (! Schema::hasTable('politica_documentos')) {
            return;
        }

        $this->reemplazarClave(['politica_id', 'file_media_uuid']);
        $this->reemplazarRegla("rol in ('oficial', 'anterior')");
        DB::statement("create unique index if not exists politica_documentos_un_oficial on politica_documentos (politica_id) where rol = 'oficial'");
    }

    /** @param list<string> $columnas */
    private function reemplazarClave(array $columnas): void
    {
        $actuales = DB::select(<<<'SQL'
            select a.attname
            from pg_index i
            join pg_attribute a on a.attrelid = i.indrelid and a.attnum = any(i.indkey)
            where i.indrelid = 'politica_documentos'::regclass and i.indisprimary
            order by array_position(i.indkey::int2[], a.attnum)
        SQL);

        if (array_map(static fn (object $f): string => (string) $f->attname, $actuales) === $columnas) {
            return;
        }

        DB::statement('alter table politica_documentos drop constraint if exists politica_documentos_pkey');
        DB::statement('alter table politica_documentos add primary key ('.implode(', ', $columnas).')');
    }

    private function reemplazarRegla(string $condicion): void
    {
        DB::statement('alter table politica_documentos drop constraint if exists politica_documentos_rol_check');
        DB::statement("alter table politica_documentos add constraint politica_documentos_rol_check check ({$condicion})");
    }
};
