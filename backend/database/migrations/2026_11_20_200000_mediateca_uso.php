<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mediateca, lanzamiento 3 de 3, parte A (D10a): EXPANDIR el reemplazo de `collection`.
 *
 * `file_media.collection` distingue una imagen de contenido sin uso (se borra a los 7 días) de un
 * documento sin uso (nunca se borra, Ley 594/2000), así que no se puede retirar a secas: se
 * reemplaza por `uso`, con CHECK. El dueño polimórfico (`model_type`, `model_id`), `collection` y
 * `metadata` dejan de ser obligatorios aquí y se borran en D10b.
 *
 * Por qué dos lanzamientos: el despliegue migra ANTES de reemplazar los contenedores, y el código
 * anterior (D9) sigue atendiendo durante la migración. Ese código escribe `collection` y no conoce
 * `uso`; sin ayuda, su insert fallaría contra `uso NOT NULL`. Un disparador `BEFORE INSERT`
 * rellena la que venga vacía desde la otra, en las dos direcciones: así D9 sigue funcionando
 * durante la ventana, y volver a D9 después de subir archivos con el código nuevo no deja filas
 * sin `collection` (D9 valida las imágenes del editor por esa columna). D10b quita el disparador.
 *
 * Si alguna fila trae una colección que el sistema nunca escribe, NO se cambia nada y se lista.
 */
return new class extends Migration
{
    public function up(): void
    {
        $raras = DB::table('file_media')
            ->whereNotIn('collection', ['imagen', 'contenido', 'documento-oficial'])
            ->orderBy('uuid')->get(['uuid', 'collection']);

        if ($raras->isNotEmpty()) {
            throw new RuntimeException(
                "Hay archivos con una colección que el sistema no usa; la migración no cambió nada. Revísalos y repite:\n"
                .$raras->map(fn ($f) => "{$f->uuid}: {$f->collection}")->implode("\n")
            );
        }

        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            DB::statement('alter table file_media add column if not exists uso varchar(32)');
            DB::statement("update file_media set uso = case collection when 'documento-oficial' then 'documento' else collection end where uso is null");

            DB::statement(<<<'SQL'
                create or replace function file_media_completar_uso() returns trigger as $$
                begin
                    if new.uso is null and new.collection is not null then
                        new.uso := case new.collection when 'documento-oficial' then 'documento' else new.collection end;
                    elsif new.collection is null and new.uso is not null then
                        new.collection := case new.uso when 'documento' then 'documento-oficial' else new.uso end;
                    end if;

                    return new;
                end
                $$ language plpgsql
            SQL);
            DB::statement('drop trigger if exists file_media_completar_uso on file_media');
            DB::statement('create trigger file_media_completar_uso before insert or update on file_media for each row execute function file_media_completar_uso()');

            DB::statement("alter table file_media add constraint file_media_uso_check check (uso in ('imagen', 'contenido', 'documento'))");
            DB::statement('alter table file_media alter column uso set not null');
            DB::statement('create index file_media_uso_index on file_media (uso)');

            DB::statement('alter table file_media drop constraint if exists file_media_model_collection_filename_unique');
            DB::statement('drop index if exists file_media_model_type_model_id_collection_index');
            foreach (['model_type', 'model_id', 'collection'] as $columna) {
                DB::statement("alter table file_media alter column {$columna} drop not null");
            }
        });
    }

    /**
     * Vuelve a la forma de D9. Las columnas viejas solo recuperan `NOT NULL` si ninguna fila quedó sin
     * ellas (las subidas hechas con el código nuevo no tienen dueño polimórfico); si no, quedan opcionales,
     * que D9 acepta. No se inventa un dueño.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            DB::statement('drop trigger if exists file_media_completar_uso on file_media');
            DB::statement('drop function if exists file_media_completar_uso()');

            DB::statement("update file_media set collection = case uso when 'documento' then 'documento-oficial' else uso end where collection is null");
            DB::statement('alter table file_media drop constraint if exists file_media_uso_check');
            DB::statement('drop index if exists file_media_uso_index');
            DB::statement('alter table file_media drop column if exists uso');

            foreach (['model_type', 'model_id', 'collection'] as $columna) {
                $sinValor = DB::table('file_media')->whereNull($columna)->exists();
                if (! $sinValor) {
                    DB::statement("alter table file_media alter column {$columna} set not null");
                }
            }

            DB::statement('create index if not exists file_media_model_type_model_id_collection_index on file_media (model_type, model_id, collection)');
            if (! DB::table('file_media')->whereNull('model_type')->orWhereNull('model_id')->exists()) {
                DB::statement('create unique index if not exists file_media_model_collection_filename_unique on file_media (model_type, model_id, collection, filename)');
            }
        });
    }
};
