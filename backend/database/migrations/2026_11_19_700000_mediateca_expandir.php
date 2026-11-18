<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mediateca, lanzamiento 1 de 3: EXPANDIR (0D, D8).
 *
 * Hoy `file_media` es polimórfica (`model_type` + `model_id` sin clave foránea): la base no puede
 * impedir que se borre un archivo en uso ni que quede uno huérfano. Se reemplaza por relaciones
 * explícitas, en tres lanzamientos (expandir → migrar el código → contraer). Este es el primero y
 * SOLO AGREGA: el código sigue leyendo lo viejo, así que volver atrás no pierde nada.
 *
 *  - `noticias.imagen_principal_uuid` / `imagen_principal_alt`, `banners.imagen_uuid` / `imagen_alt`:
 *    el texto alternativo pertenece al USO de la imagen, no al archivo.
 *  - `noticia_imagenes`: imágenes incrustadas en el HTML de cada noticia.
 *  - `politica_documentos`: documento oficial de cada política (hoy uno; el rol deja espacio).
 *  - `file_media.subido_por`: solo para la colección `contenido`, donde el dueño ES quien subió.
 *
 * Todas las claves hacia el archivo son ON DELETE RESTRICT: un archivo en uso no se borra.
 * No se funden archivos con la misma huella (decisión del 2026-11-18); D9 los reutiliza al subir.
 *
 * Si alguna noticia referencia una imagen que ya no existe, NO se toca nada y se lista el problema:
 * corregirlo es una decisión editorial, no de la migración.
 */
return new class extends Migration
{
    private const PATRON = '#/api/v1/contenido/imagenes/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})#';

    public function up(): void
    {
        // Lectura previa: si hay referencias rotas se aborta antes de cambiar el esquema.
        $usos = $this->imagenesPorNoticia();

        DB::transaction(function () use ($usos): void {
            DB::statement("set local lock_timeout = '3s'");

            $this->agregarColumnas();
            $this->crearTablas();
            $this->rellenar($usos);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            Schema::dropIfExists('noticia_imagenes');
            Schema::dropIfExists('politica_documentos');

            foreach ([
                'noticias' => ['imagen_principal_uuid', 'imagen_principal_alt'],
                'banners' => ['imagen_uuid', 'imagen_alt'],
                'file_media' => ['subido_por'],
            ] as $tabla => $columnas) {
                foreach ($columnas as $columna) {
                    if (Schema::hasColumn($tabla, $columna)) {
                        Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn($columna));
                    }
                }
            }
        });
    }

    /**
     * @return array<int, list<string>> uuids de imágenes de contenido usadas por cada noticia
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
                "Hay imágenes en el texto de noticias que ya no existen; la migración no cambió nada. Corrígelas y repite:\n"
                .implode("\n", $rotas)
            );
        }

        return $usos;
    }

    private function agregarColumnas(): void
    {
        $nuevas = [
            ['noticias', 'imagen_principal_uuid'], ['noticias', 'imagen_principal_alt'],
            ['banners', 'imagen_uuid'], ['banners', 'imagen_alt'],
            ['file_media', 'subido_por'],
        ];

        foreach ($nuevas as [$tabla, $columna]) {
            if (Schema::hasColumn($tabla, $columna)) {
                continue;
            }
            Schema::table($tabla, function (Blueprint $t) use ($tabla, $columna): void {
                match ($columna) {
                    'imagen_principal_alt', 'imagen_alt' => $t->text($columna)->nullable(),
                    'subido_por' => $t->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete(),
                    default => $t->foreignUuid($columna)->nullable()->constrained('file_media', 'uuid')->restrictOnDelete(),
                };
                if ($columna === 'subido_por' || str_ends_with($columna, 'uuid')) {
                    $t->index($columna, "{$tabla}_{$columna}_index");
                }
            });
        }
    }

    private function crearTablas(): void
    {
        if (! Schema::hasTable('noticia_imagenes')) {
            Schema::create('noticia_imagenes', function (Blueprint $t): void {
                $t->foreignId('noticia_id')->constrained('noticias')->cascadeOnDelete();
                $t->foreignUuid('file_media_uuid')->constrained('file_media', 'uuid')->restrictOnDelete();
                $t->timestampTz('created_at')->useCurrent();
                $t->primary(['noticia_id', 'file_media_uuid']);
                $t->index('file_media_uuid');
            });
        }

        if (! Schema::hasTable('politica_documentos')) {
            Schema::create('politica_documentos', function (Blueprint $t): void {
                $t->foreignId('politica_id')->constrained('politicas')->cascadeOnDelete();
                $t->string('rol', 32);
                $t->foreignUuid('file_media_uuid')->constrained('file_media', 'uuid')->restrictOnDelete();
                $t->timestampTz('created_at')->useCurrent();
                $t->primary(['politica_id', 'rol']);
                $t->index('file_media_uuid');
            });
            DB::statement("alter table politica_documentos add constraint politica_documentos_rol_check check (rol in ('oficial'))");
        }
    }

    /** @param array<int, list<string>> $usos */
    private function rellenar(array $usos): void
    {
        // Imagen principal y su alt (el más reciente si hubiera más de un archivo); solo donde aún no hay.
        foreach ([
            ['noticias', 'App\Models\Noticia', 'imagen_principal_uuid', 'imagen_principal_alt'],
            ['banners', 'App\Models\Banner', 'imagen_uuid', 'imagen_alt'],
        ] as [$tabla, $modelo, $uuid, $alt]) {
            DB::statement(<<<SQL
                update {$tabla} t set {$uuid} = f.uuid, {$alt} = nullif(btrim(f.metadata::jsonb ->> 'alt'), '')
                from (
                    select distinct on (model_id) model_id, uuid, metadata
                    from file_media
                    where model_type = ? and collection = 'imagen'
                    order by model_id, created_at desc nulls last, uuid
                ) f
                where f.model_id = t.id and t.{$uuid} is null
            SQL, [$modelo]);
        }

        DB::statement(<<<'SQL'
            insert into politica_documentos (politica_id, rol, file_media_uuid)
            select distinct on (model_id) model_id, 'oficial', uuid
            from file_media
            where model_type = ? and collection = 'documento-oficial'
              and exists (select 1 from politicas p where p.id = file_media.model_id)
            order by model_id, created_at desc nulls last, uuid
            on conflict do nothing
        SQL, ['App\Models\Politica']);

        foreach ($usos as $noticia => $uuids) {
            foreach ($uuids as $uuid) {
                DB::table('noticia_imagenes')->insertOrIgnore(['noticia_id' => $noticia, 'file_media_uuid' => $uuid]);
            }
        }

        // Solo en `contenido` el dueño es quien subió el archivo; en otras colecciones no se inventa.
        DB::statement(<<<'SQL'
            update file_media set subido_por = model_id
            where collection = 'contenido' and model_type = ? and subido_por is null
              and exists (select 1 from users u where u.id = file_media.model_id)
        SQL, ['App\Models\User']);
    }
};
