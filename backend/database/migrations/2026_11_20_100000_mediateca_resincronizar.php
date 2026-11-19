<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mediateca, lanzamiento 2 de 3, pieza 1: RESINCRONIZAR (0D, D9).
 *
 * Entre el despliegue de D8 y este, el código siguió escribiendo solo lo viejo: aquí se vuelve a copiar
 * de lo viejo a lo nuevo para todas las filas (imágenes principales, alt, imágenes del texto, documentos
 * y `subido_por`; el código vive aquí: la migración no depende de clases de la aplicación). Además:
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

            $this->resincronizar();
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

    private const PATRON = '#/api/v1/contenido/imagenes/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})#';

    private const NOTICIA = 'App\\Models\\Noticia';

    private const BANNER = 'App\\Models\\Banner';

    private const POLITICA = 'App\\Models\\Politica';

    private const USUARIO = 'App\\Models\\User';

    /**
     * @return array{imagenes: int, usos: int, documentos: int, subido_por: int}
     *
     * @throws RuntimeException si hay imágenes rotas o archivos con un dueño que nadie vincularía
     */
    private function resincronizar(): array
    {
        /** @var array{imagenes: int, usos: int, documentos: int, subido_por: int} $resumen */
        $resumen = DB::transaction(function (): array {
            $usos = $this->imagenesPorNoticia();
            $this->exigirDuenosConocidos();

            return [
                'imagenes' => $this->imagenesPrincipales(),
                'usos' => $this->sincronizarUsos($usos),
                'documentos' => $this->sincronizarDocumentos(),
                'subido_por' => $this->subidoPor(),
            ];
        });

        return $resumen;
    }

    /**
     * @return array<int, list<string>> uuids (en minúsculas, sin repetir) de las imágenes de contenido de cada noticia
     */
    private function imagenesPorNoticia(): array
    {
        $usos = [];
        $rotas = [];

        foreach (DB::table('noticias')->select('id', 'contenido')->orderBy('id')->cursor() as $noticia) {
            if (preg_match_all(self::PATRON, (string) $noticia->contenido, $coincidencias) === 0) {
                continue;
            }

            $uuids = array_values(array_unique(array_map('strtolower', $coincidencias[1])));
            $existentes = DB::table('file_media')->whereIn('uuid', $uuids)->pluck('uuid')->all();

            foreach (array_diff($uuids, $existentes) as $falta) {
                $rotas[] = "noticia {$noticia->id}: {$falta}";
            }
            $usos[(int) $noticia->id] = $uuids;
        }

        if ($rotas !== []) {
            throw new RuntimeException(
                "Hay imágenes en el texto de noticias que ya no existen; no se cambió nada. Corrígelas y repite:\n"
                .implode("\n", $rotas)
            );
        }

        return $usos;
    }

    private function exigirDuenosConocidos(): void
    {
        $sueltos = DB::table('file_media')
            ->select('uuid', 'model_type', 'collection')
            ->whereNot(function ($q): void {
                $q->where(fn ($c) => $c->where('model_type', self::NOTICIA)->where('collection', 'imagen'))
                    ->orWhere(fn ($c) => $c->where('model_type', self::BANNER)->where('collection', 'imagen'))
                    ->orWhere(fn ($c) => $c->where('model_type', self::POLITICA)->where('collection', 'documento-oficial'))
                    ->orWhere(fn ($c) => $c->where('model_type', self::USUARIO)->where('collection', 'contenido'));
            })
            ->orderBy('uuid')
            ->get();

        if ($sueltos->isNotEmpty()) {
            $lineas = $sueltos->map(
                static fn (object $f): string => "{$f->uuid}: dueño {$f->model_type}, colección {$f->collection}"
            )->all();

            throw new RuntimeException(
                'Hay archivos cuyo dueño no es noticia, banner, política ni usuario de contenido; no se cambió nada. '
                ."Decide qué hacer con cada uno y repite:\n".implode("\n", $lineas)
            );
        }
    }

    /** Imagen principal y su alt (el archivo más reciente de cada dueño). */
    private function imagenesPrincipales(): int
    {
        $cambiadas = 0;

        foreach ([
            ['noticias', self::NOTICIA, 'imagen_principal_uuid', 'imagen_principal_alt'],
            ['banners', self::BANNER, 'imagen_uuid', 'imagen_alt'],
        ] as [$tabla, $modelo, $uuid, $alt]) {
            $cambiadas += DB::affectingStatement(<<<SQL
                update {$tabla} t
                set {$uuid} = f.uuid, {$alt} = f.alt
                from (
                    select distinct on (model_id) model_id, uuid, nullif(btrim(metadata::jsonb ->> 'alt'), '') as alt
                    from file_media
                    where model_type = ? and collection = 'imagen'
                    order by model_id, created_at desc nulls last, uuid
                ) f
                where f.model_id = t.id
                  and (t.{$uuid} is distinct from f.uuid or t.{$alt} is distinct from f.alt)
            SQL, [$modelo]);
        }

        return $cambiadas;
    }

    /**
     * Deja `noticia_imagenes` igual a las imágenes que hay hoy en el texto de cada noticia.
     *
     * @param  array<int, list<string>>  $usos
     */
    private function sincronizarUsos(array $usos): int
    {
        $cambios = 0;
        $existentes = [];

        foreach (DB::table('noticia_imagenes')->select('noticia_id', 'file_media_uuid')->cursor() as $fila) {
            $existentes[(int) $fila->noticia_id][] = (string) $fila->file_media_uuid;
        }

        foreach ($existentes as $noticia => $uuids) {
            $sobran = array_diff($uuids, $usos[$noticia] ?? []);
            if ($sobran !== []) {
                $cambios += DB::table('noticia_imagenes')->where('noticia_id', $noticia)->whereIn('file_media_uuid', $sobran)->delete();
            }
        }

        foreach ($usos as $noticia => $uuids) {
            foreach (array_diff($uuids, $existentes[$noticia] ?? []) as $uuid) {
                $cambios += DB::table('noticia_imagenes')->insertOrIgnore(['noticia_id' => $noticia, 'file_media_uuid' => $uuid]);
            }
        }

        return $cambios;
    }

    /**
     * El documento más reciente de cada política queda `oficial` y los demás `anterior`.
     * Se llama una sola vez, con la clave nueva ya puesta.
     */
    private function sincronizarDocumentos(): int
    {
        $porPolitica = [];

        $filas = DB::table('file_media')
            ->select('uuid', 'model_id')
            ->where('model_type', self::POLITICA)
            ->where('collection', 'documento-oficial')
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('politicas')->whereColumn('politicas.id', 'file_media.model_id'))
            ->orderBy('model_id')
            ->orderByRaw('created_at desc nulls last')
            ->orderBy('uuid')
            ->get();

        foreach ($filas as $fila) {
            $porPolitica[(int) $fila->model_id][] = (string) $fila->uuid;
        }

        $cambios = 0;

        foreach ($porPolitica as $politica => $uuids) {
            $oficial = $uuids[0];

            $cambios += DB::table('politica_documentos')->where('politica_id', $politica)->whereNotIn('file_media_uuid', $uuids)->delete();
            // Primero se baja al `oficial` que ya no lo es: el índice único parcial admite uno solo.
            $cambios += DB::table('politica_documentos')
                ->where('politica_id', $politica)->where('rol', 'oficial')->where('file_media_uuid', '!=', $oficial)
                ->update(['rol' => 'anterior']);

            foreach ($uuids as $uuid) {
                $rol = $uuid === $oficial ? 'oficial' : 'anterior';
                $cambios += DB::affectingStatement(<<<'SQL'
                    insert into politica_documentos (politica_id, rol, file_media_uuid) values (?, ?, ?)
                    on conflict (politica_id, file_media_uuid) do update set rol = excluded.rol
                    where politica_documentos.rol is distinct from excluded.rol
                SQL, [$politica, $rol, $uuid]);
            }
        }

        return $cambios;
    }

    /** Solo en `contenido` el dueño es quien subió el archivo; en otras colecciones no se inventa. */
    private function subidoPor(): int
    {
        return DB::affectingStatement(<<<'SQL'
            update file_media set subido_por = model_id
            where collection = 'contenido' and model_type = ?
              and subido_por is distinct from model_id
              and exists (select 1 from users u where u.id = file_media.model_id)
        SQL, [self::USUARIO]);
    }
};
