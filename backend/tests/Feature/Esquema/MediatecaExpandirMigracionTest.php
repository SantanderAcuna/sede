<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;
use Tests\TestCase;

/**
 * 0D, D8 (mediateca, lanzamiento 1 de 3: expandir).
 *
 * Solo AGREGA: las piezas nuevas conviven con las viejas y el código sigue leyendo lo viejo. Se prueba
 * con el migrador real (se retrocede, se siembran datos «de antes» y se vuelve a subir), porque lo que
 * importa es el relleno desde lo que ya existe en staging: 4 banners y 13 noticias con imagen, 3 pares
 * de archivos con la misma huella, ninguna imagen de contenido y 5 políticas sin documento.
 */
final class MediatecaExpandirMigracionTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRACION = '2026_11_19_700000_mediateca_expandir.php';

    /** La migración es una clase anónima: se usa sin tipo, como en las demás pruebas de migraciones. */
    private function migracion(): mixed
    {
        return require database_path('migrations/'.self::MIGRACION);
    }

    /** Estado «de antes»: sin las piezas nuevas. */
    private function antes(): mixed
    {
        $migracion = $this->migracion();
        $migracion->down();

        return $migracion;
    }

    /** @param array<string, mixed> $campos */
    private function archivo(string $tipo, int $dueno, string $coleccion, array $campos = []): string
    {
        $uuid = $campos['uuid'] ?? (string) Str::uuid();

        DB::table('file_media')->insert(array_merge([
            'uuid' => $uuid,
            'model_type' => $tipo,
            'model_id' => $dueno,
            'collection' => $coleccion,
            'filename' => $uuid.'.png',
            'original_filename' => 'foto.png',
            'mime_type' => 'image/png',
            'size' => 1000,
            'disk' => 'public',
            'path' => "file_media/x/{$coleccion}/{$uuid}.png",
            'hash_sha256' => hash('sha256', $uuid),
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $campos));

        return (string) $uuid;
    }

    private function noticia(string $contenido = '<p>texto</p>'): int
    {
        return (int) DB::table('noticias')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'slug' => 'n-'.Str::random(8),
            'titulo' => 'Titulo', 'descripcion_corta' => 'corta', 'contenido' => $contenido,
            'fecha_publicacion' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function banner(): int
    {
        return (int) DB::table('banners')->insertGetId([
            'uuid' => (string) Str::uuid(), 'titulo' => 'Banner',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function politica(): int
    {
        return (int) DB::table('politicas')->insertGetId([
            'uuid' => (string) Str::uuid(), 'slug' => 'p-'.Str::random(8),
            'nombre' => 'Politica', 'secciones' => '[]', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function nulosYTipos(string $tabla, string $columna): ?stdClass
    {
        /** @var stdClass|null $fila */
        $fila = DB::selectOne(
            'select data_type, is_nullable from information_schema.columns where table_schema = ? and table_name = ? and column_name = ?',
            ['public', $tabla, $columna],
        );

        return $fila;
    }

    public function test_up_crea_las_piezas_con_las_claves_que_protegen_el_archivo_en_uso(): void
    {
        foreach ([
            ['noticias', 'imagen_principal_uuid', 'uuid'], ['noticias', 'imagen_principal_alt', 'text'],
            ['banners', 'imagen_uuid', 'uuid'], ['banners', 'imagen_alt', 'text'],
            ['file_media', 'subido_por', 'bigint'],
            ['noticia_imagenes', 'noticia_id', 'bigint'], ['noticia_imagenes', 'file_media_uuid', 'uuid'],
            ['politica_documentos', 'politica_id', 'bigint'], ['politica_documentos', 'rol', 'character varying'],
            ['politica_documentos', 'file_media_uuid', 'uuid'],
        ] as [$tabla, $columna, $tipo]) {
            $this->assertSame($tipo, $this->nulosYTipos($tabla, $columna)?->data_type, "{$tabla}.{$columna}");
        }

        // Todas las claves hacia el archivo son RESTRICT: un archivo en uso no se puede borrar.
        /** @var list<stdClass> $reglas */
        $reglas = DB::select(<<<'SQL'
            select c.conrelid::regclass::text as tabla, a.attname as columna, c.confdeltype as al_borrar
            from pg_constraint c join pg_attribute a on a.attrelid = c.conrelid and a.attnum = any (c.conkey)
            where c.contype = 'f' and c.confrelid = 'file_media'::regclass
        SQL);
        $hacia = [];
        foreach ($reglas as $regla) {
            $hacia["{$regla->tabla}.{$regla->columna}"] = $regla->al_borrar;
        }
        foreach (['noticias.imagen_principal_uuid', 'banners.imagen_uuid', 'noticia_imagenes.file_media_uuid', 'politica_documentos.file_media_uuid'] as $clave) {
            $this->assertSame('r', $hacia[$clave] ?? null, "{$clave} debe ser ON DELETE RESTRICT.");
        }
    }

    public function test_un_archivo_en_uso_no_se_puede_borrar(): void
    {
        $noticia = $this->noticia();
        $uuid = $this->archivo('App\Models\Noticia', $noticia, 'imagen');
        DB::table('noticias')->where('id', $noticia)->update(['imagen_principal_uuid' => $uuid]);

        DB::beginTransaction();
        try {
            DB::table('file_media')->where('uuid', $uuid)->delete();
            DB::commit();
            $this->fail('Se borró un archivo que una noticia usa.');
        } catch (QueryException $e) {
            DB::rollBack();
            $this->assertSame('23503', $e->errorInfo[0] ?? null);
        }
    }

    public function test_el_relleno_copia_la_imagen_principal_y_su_alt_de_noticias_y_banners(): void
    {
        $migracion = $this->antes();
        $noticia = $this->noticia();
        $sin = $this->noticia();
        $banner = $this->banner();
        $deNoticia = $this->archivo('App\Models\Noticia', $noticia, 'imagen', ['metadata' => json_encode(['alt' => 'Alcalde en la plaza'])]);
        $deBanner = $this->archivo('App\Models\Banner', $banner, 'imagen', ['metadata' => json_encode(['alt' => 'Portada'])]);

        $migracion->up();

        $this->assertSame($deNoticia, DB::table('noticias')->where('id', $noticia)->value('imagen_principal_uuid'));
        $this->assertSame('Alcalde en la plaza', DB::table('noticias')->where('id', $noticia)->value('imagen_principal_alt'));
        $this->assertNull(DB::table('noticias')->where('id', $sin)->value('imagen_principal_uuid'));
        $this->assertSame($deBanner, DB::table('banners')->where('id', $banner)->value('imagen_uuid'));
        $this->assertSame('Portada', DB::table('banners')->where('id', $banner)->value('imagen_alt'));
    }

    public function test_una_imagen_sin_alt_se_rellena_con_alt_nulo(): void
    {
        $migracion = $this->antes();
        $noticia = $this->noticia();
        $uuid = $this->archivo('App\Models\Noticia', $noticia, 'imagen', ['metadata' => null]);

        $migracion->up();

        $this->assertSame($uuid, DB::table('noticias')->where('id', $noticia)->value('imagen_principal_uuid'));
        $this->assertNull(DB::table('noticias')->where('id', $noticia)->value('imagen_principal_alt'));
    }

    public function test_el_relleno_no_unifica_archivos_con_la_misma_huella(): void
    {
        // Staging tiene 3 pares: D8 solo agrega, no borra ni funde archivos (decisión del 2026-11-18).
        $migracion = $this->antes();
        $uno = $this->noticia();
        $otro = $this->noticia();
        $huella = hash('sha256', 'la misma imagen');
        $a = $this->archivo('App\Models\Noticia', $uno, 'imagen', ['hash_sha256' => $huella]);
        $b = $this->archivo('App\Models\Noticia', $otro, 'imagen', ['hash_sha256' => $huella]);

        $migracion->up();

        $this->assertSame($a, DB::table('noticias')->where('id', $uno)->value('imagen_principal_uuid'));
        $this->assertSame($b, DB::table('noticias')->where('id', $otro)->value('imagen_principal_uuid'));
        $this->assertSame(2, (int) DB::table('file_media')->where('hash_sha256', $huella)->count());
    }

    public function test_el_relleno_asigna_el_documento_oficial_de_cada_politica(): void
    {
        $migracion = $this->antes();
        $con = $this->politica();
        $sin = $this->politica();
        $documento = $this->archivo('App\Models\Politica', $con, 'documento-oficial', ['mime_type' => 'application/pdf']);

        $migracion->up();

        $this->assertSame(
            [$documento],
            DB::table('politica_documentos')->where('politica_id', $con)->where('rol', 'oficial')->pluck('file_media_uuid')->all(),
        );
        $this->assertSame(0, (int) DB::table('politica_documentos')->where('politica_id', $sin)->count());
    }

    public function test_solo_hay_un_documento_oficial_por_politica(): void
    {
        $politica = $this->politica();
        $uno = $this->archivo('App\Models\Politica', $politica, 'documento-oficial');
        $dos = $this->archivo('App\Models\Politica', $politica, 'documento-oficial');
        DB::table('politica_documentos')->insert(['politica_id' => $politica, 'rol' => 'oficial', 'file_media_uuid' => $uno]);

        DB::beginTransaction();
        try {
            DB::table('politica_documentos')->insert(['politica_id' => $politica, 'rol' => 'oficial', 'file_media_uuid' => $dos]);
            DB::commit();
            $this->fail('Una política tiene dos documentos oficiales.');
        } catch (QueryException $e) {
            DB::rollBack();
            $this->assertSame('23505', $e->errorInfo[0] ?? null);
        }
    }

    public function test_el_rol_del_documento_solo_admite_valores_de_la_lista(): void
    {
        $politica = $this->politica();
        $uuid = $this->archivo('App\Models\Politica', $politica, 'documento-oficial');

        DB::beginTransaction();
        try {
            DB::table('politica_documentos')->insert(['politica_id' => $politica, 'rol' => 'inventado', 'file_media_uuid' => $uuid]);
            DB::commit();
            $this->fail('La base aceptó un rol fuera de la lista.');
        } catch (QueryException $e) {
            DB::rollBack();
            $this->assertSame('23514', $e->errorInfo[0] ?? null);
        }
    }

    public function test_el_relleno_lee_el_html_y_llena_noticia_imagenes_y_subido_por(): void
    {
        $migracion = $this->antes();
        $autor = User::factory()->create();
        $a = (string) Str::uuid();
        $b = (string) Str::uuid();
        $this->archivo(User::class, (int) $autor->getKey(), 'contenido', ['uuid' => $a]);
        $this->archivo(User::class, (int) $autor->getKey(), 'contenido', ['uuid' => $b]);
        $suelta = $this->archivo(User::class, (int) $autor->getKey(), 'contenido');
        // La misma imagen dos veces en el texto cuenta una sola vez; una URL absoluta también vale.
        $noticia = $this->noticia(
            '<p><img src="/api/v1/contenido/imagenes/'.$a.'" alt="x"><img src="/api/v1/contenido/imagenes/'.$a.'">'
            .'<img src="https://sede.example/api/v1/contenido/imagenes/'.$b.'"></p>'
        );

        $migracion->up();

        $this->assertEqualsCanonicalizing([$a, $b], DB::table('noticia_imagenes')->where('noticia_id', $noticia)->pluck('file_media_uuid')->all());
        $this->assertSame(0, (int) DB::table('noticia_imagenes')->where('file_media_uuid', $suelta)->count(), 'Una imagen que ninguna noticia usa no entra.');
        $this->assertSame((int) $autor->getKey(), (int) DB::table('file_media')->where('uuid', $a)->value('subido_por'));
        // Fuera de la colección de contenido el dueño NO era quien subió el archivo: no se inventa.
        $this->assertSame(3, (int) DB::table('file_media')->where('collection', 'contenido')->whereNotNull('subido_por')->count());
    }

    public function test_no_se_inventa_quien_subio_un_archivo_de_otra_coleccion(): void
    {
        $migracion = $this->antes();
        $noticia = $this->noticia();
        $uuid = $this->archivo('App\Models\Noticia', $noticia, 'imagen');

        $migracion->up();

        $this->assertNull(DB::table('file_media')->where('uuid', $uuid)->value('subido_por'));
    }

    public function test_ante_una_referencia_rota_no_cambia_nada_y_lista_las_noticias(): void
    {
        $migracion = $this->antes();
        $rota = (string) Str::uuid();
        $noticia = $this->noticia('<img src="/api/v1/contenido/imagenes/'.$rota.'">');

        try {
            $migracion->up();
            $this->fail('La migración debió detenerse.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("noticia {$noticia}", $e->getMessage());
            $this->assertStringContainsString($rota, $e->getMessage());
        }

        $this->assertFalse(Schema::hasTable('noticia_imagenes'), 'La migración cambió el esquema antes de detenerse.');
        $this->assertFalse(Schema::hasColumn('noticias', 'imagen_principal_uuid'));
        $this->assertFalse(Schema::hasColumn('file_media', 'subido_por'));
    }

    public function test_down_retira_lo_nuevo_y_no_toca_file_media(): void
    {
        $noticia = $this->noticia();
        $uuid = $this->archivo('App\Models\Noticia', $noticia, 'imagen');
        $antes = DB::table('file_media')->count();

        $this->migracion()->down();

        $this->assertFalse(Schema::hasTable('noticia_imagenes'));
        $this->assertFalse(Schema::hasTable('politica_documentos'));
        foreach ([['noticias', 'imagen_principal_uuid'], ['noticias', 'imagen_principal_alt'], ['banners', 'imagen_uuid'], ['banners', 'imagen_alt'], ['file_media', 'subido_por']] as [$tabla, $columna]) {
            $this->assertFalse(Schema::hasColumn($tabla, $columna), "{$tabla}.{$columna}");
        }
        $this->assertSame($antes, DB::table('file_media')->count());
        $this->assertSame(1, (int) DB::table('file_media')->where('uuid', $uuid)->count());
    }

    public function test_se_puede_repetir_up_sin_duplicar_nada(): void
    {
        $migracion = $this->antes();
        $noticia = $this->noticia();
        $this->archivo('App\Models\Noticia', $noticia, 'imagen');

        $migracion->up();
        $migracion->up();

        $this->assertSame(1, (int) DB::table('noticias')->whereNotNull('imagen_principal_uuid')->count());
    }
}
