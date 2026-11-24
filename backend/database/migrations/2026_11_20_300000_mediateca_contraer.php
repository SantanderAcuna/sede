<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mediateca, lanzamiento 3 de 3, parte B (D10b): CONTRAER.
 *
 * D10a dejó el código leyendo y escribiendo solo `uso` y las relaciones explícitas. Aquí se borra lo
 * que quedó de la forma vieja: el dueño polimórfico (`model_type`, `model_id`), `collection` y
 * `metadata`, junto con el disparador que los mantenía sincronizados con `uso`. Y la huella del
 * contenido pasa a ser obligatoria, con forma de SHA-256 en hexadecimal minúscula.
 *
 * Si algún archivo sigue sin huella, NO se cambia nada y se lista: el cálculo corre después de migrar
 * en cada despliegue (`archivos:calcular-hash`), de modo que la primera pasada pudo no haber
 * terminado; se repite el comando y se vuelve a migrar. No se inventa ninguna huella.
 *
 * `down()` vuelve a la forma de D10a y reconstruye todo lo que las relaciones permiten:
 * `model_type`/`model_id` desde la noticia, el banner o la política que usa el archivo (o desde quien
 * lo subió, en `contenido`), `collection` desde `uso` y `metadata.alt` desde el alt de la noticia o el
 * banner. Los archivos SIN USO nunca tuvieron un dueño que se pueda conocer ahora: quedan con
 * `model_type` y `model_id` en NULL, que D10a admite. El alt de las imágenes de `contenido` (que nada leía)
 * no se reconstruye. Por eso se pide copia de la base antes de D10a.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sinHuella = DB::table('file_media')->whereNull('hash_sha256')->orderBy('uuid')->get(['uuid', 'path']);
        if ($sinHuella->isNotEmpty()) {
            throw new RuntimeException(
                "Hay {$sinHuella->count()} archivo(s) sin huella; la migración no cambió nada. Ejecuta `php artisan archivos:calcular-hash` y repite:\n"
                .$sinHuella->map(fn ($f) => "{$f->uuid}: {$f->path}")->implode("\n")
            );
        }

        $malformadas = DB::table('file_media')->whereRaw("hash_sha256 !~ '^[0-9a-f]{64}$'")->orderBy('uuid')->pluck('uuid');
        if ($malformadas->isNotEmpty()) {
            throw new RuntimeException(
                "Hay huellas que no son un SHA-256 en hexadecimal minúscula; la migración no cambió nada:\n".$malformadas->implode("\n")
            );
        }

        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            DB::statement('drop trigger if exists file_media_completar_uso on file_media');
            DB::statement('drop function if exists file_media_completar_uso()');

            foreach (['model_type', 'model_id', 'collection', 'metadata'] as $columna) {
                DB::statement("alter table file_media drop column if exists {$columna}");
            }

            DB::statement('alter table file_media alter column hash_sha256 set not null');
            DB::statement("alter table file_media add constraint file_media_hash_sha256_check check (hash_sha256 ~ '^[0-9a-f]{64}$')");
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            DB::statement('alter table file_media drop constraint if exists file_media_hash_sha256_check');
            DB::statement('alter table file_media alter column hash_sha256 drop not null');

            DB::statement('alter table file_media add column if not exists model_type varchar(255)');
            DB::statement('alter table file_media add column if not exists model_id bigint');
            DB::statement('alter table file_media add column if not exists collection varchar(64)');
            DB::statement('alter table file_media add column if not exists metadata json');

            DB::statement("update file_media set collection = case uso when 'documento' then 'documento-oficial' else uso end");

            $this->reconstruirDuenos();
            $this->reconstruirAlt();

            DB::statement('create index if not exists file_media_model_type_model_id_collection_index on file_media (model_type, model_id, collection)');
            $this->crearDisparador();
        });
    }

    /** Dueño polimórfico desde la relación que usa el archivo; el primero que se encuentre manda. */
    private function reconstruirDuenos(): void
    {
        $origenes = [
            ['App\Models\Noticia', 'select imagen_principal_uuid as uuid, id as dueno from noticias where imagen_principal_uuid is not null'],
            ['App\Models\Banner', 'select imagen_uuid as uuid, id as dueno from banners where imagen_uuid is not null'],
            ['App\Models\Politica', 'select file_media_uuid as uuid, politica_id as dueno from politica_documentos'],
        ];

        foreach ($origenes as [$modelo, $consulta]) {
            DB::statement(<<<SQL
                update file_media f set model_type = ?, model_id = o.dueno
                from ({$consulta}) o
                where f.uuid = o.uuid and f.model_type is null
            SQL, [$modelo]);
        }

        // En `contenido` el dueño ES quien subió el archivo.
        DB::statement(<<<'SQL'
            update file_media set model_type = ?, model_id = subido_por
            where model_type is null and uso = 'contenido' and subido_por is not null
        SQL, ['App\Models\User']);
    }

    private function reconstruirAlt(): void
    {
        foreach ([
            ['noticias', 'imagen_principal_uuid', 'imagen_principal_alt'],
            ['banners', 'imagen_uuid', 'imagen_alt'],
        ] as [$tabla, $uuid, $alt]) {
            DB::statement(<<<SQL
                update file_media f set metadata = json_build_object('alt', t.{$alt})
                from {$tabla} t
                where t.{$uuid} = f.uuid and t.{$alt} is not null
            SQL);
        }
    }

    /** Mismo disparador que crea D10a; si cambia allí, cambia aquí. */
    private function crearDisparador(): void
    {
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
    }
};
