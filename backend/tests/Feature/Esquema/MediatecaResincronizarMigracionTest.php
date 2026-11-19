<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * 0D, D9 (mediateca, lanzamiento 2 de 3), pieza 1: la resincronización.
 *
 * Entre el despliegue de D8 y el de D9 el código sigue escribiendo solo lo viejo, así que lo nuevo
 * puede haber quedado desfasado. La migración vuelve a copiar de lo viejo a lo nuevo para TODAS las
 * filas, ajusta `politica_documentos` para admitir varios documentos por política (uno solo `oficial`)
 * y crea el índice de la huella. Se prueba con el migrador real: se retrocede a la forma de D8, se
 * siembra el estado «de antes» (con desfases) y se vuelve a subir.
 *
 * Regla que protege esta prueba: el comando `mediateca:resincronizar` NO toca documentos, porque
 * «quitar» un documento solo lo desvincula y una copia viejo→nuevo lo resucitaría.
 */
final class MediatecaResincronizarMigracionTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRACION = '2026_11_20_100000_mediateca_resincronizar.php';

    private const NOTICIA = 'App\\Models\\Noticia';

    private const BANNER = 'App\\Models\\Banner';

    private const POLITICA = 'App\\Models\\Politica';

    private const USUARIO = 'App\\Models\\User';

    private function migracion(): mixed
    {
        return require database_path('migrations/'.self::MIGRACION);
    }

    /** Estado «de antes»: la forma de D8 (sin ajustes de D9). */
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
            'hash_sha256' => hash('sha256', (string) $uuid),
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

    /** @return list<string> columnas de la clave primaria, en orden */
    private function clavePrimaria(string $tabla): array
    {
        $filas = DB::select(<<<'SQL'
            select a.attname
            from pg_index i
            join pg_attribute a on a.attrelid = i.indrelid and a.attnum = any(i.indkey)
            where i.indrelid = ?::regclass and i.indisprimary
            order by array_position(i.indkey::int2[], a.attnum)
        SQL, [$tabla]);

        return array_map(static fn (object $f): string => (string) $f->attname, $filas);
    }

    private function hayIndice(string $nombre): bool
    {
        return DB::selectOne('select 1 as hay from pg_indexes where schemaname = ? and indexname = ?', ['public', $nombre]) !== null;
    }

    // ---- imágenes principales y alt ----------------------------------------------------------

    public function test_corrige_el_uuid_y_el_alt_desfasados_de_noticias_y_banners(): void
    {
        $migracion = $this->antes();

        $noticia = $this->noticia();
        $banner = $this->banner();
        $vieja = $this->archivo(self::NOTICIA, $noticia, 'imagen', ['metadata' => json_encode(['alt' => '  Playa de Taganga  '])]);
        $otra = $this->archivo(self::NOTICIA, $noticia, 'imagen', ['created_at' => now()->subDay(), 'metadata' => json_encode(['alt' => 'Vieja'])]);
        $deBanner = $this->archivo(self::BANNER, $banner, 'imagen', ['metadata' => json_encode(['alt' => 'Carrusel'])]);

        // Lo nuevo quedó desfasado (apunta al archivo equivocado y con otro alt) o vacío.
        DB::table('noticias')->where('id', $noticia)->update(['imagen_principal_uuid' => $otra, 'imagen_principal_alt' => 'desfasado']);

        $migracion->up();

        $fila = DB::table('noticias')->where('id', $noticia)->first();
        $this->assertSame($vieja, $fila->imagen_principal_uuid, 'Gana el archivo más reciente, que es el que lee el código viejo.');
        $this->assertSame('Playa de Taganga', $fila->imagen_principal_alt);

        $filaBanner = DB::table('banners')->where('id', $banner)->first();
        $this->assertSame($deBanner, $filaBanner->imagen_uuid);
        $this->assertSame('Carrusel', $filaBanner->imagen_alt);
    }

    public function test_sin_imagen_vieja_deja_lo_nuevo_vacio_y_sin_alt(): void
    {
        $migracion = $this->antes();
        $noticia = $this->noticia();
        $migracion->up();

        $fila = DB::table('noticias')->where('id', $noticia)->first();
        $this->assertNull($fila->imagen_principal_uuid);
        $this->assertNull($fila->imagen_principal_alt);
    }

    public function test_llena_subido_por_solo_en_contenido_con_dueno_usuario(): void
    {
        $migracion = $this->antes();
        $autor = User::factory()->create();
        $contenido = $this->archivo(self::USUARIO, (int) $autor->id, 'contenido');
        $migracion->up();

        $this->assertSame((int) $autor->id, (int) DB::table('file_media')->where('uuid', $contenido)->value('subido_por'));
    }

    // ---- noticia_imagenes ----------------------------------------------------------------------

    public function test_noticia_imagenes_queda_igual_a_las_imagenes_del_texto_sin_duplicar_mayusculas(): void
    {
        $migracion = $this->antes();
        $autor = User::factory()->create();
        $uno = $this->archivo(self::USUARIO, (int) $autor->id, 'contenido');
        $dos = $this->archivo(self::USUARIO, (int) $autor->id, 'contenido');
        $sobra = $this->archivo(self::USUARIO, (int) $autor->id, 'contenido');

        $html = '<p>a</p><img src="/api/v1/contenido/imagenes/'.$uno.'" alt="a">'
            .'<img src="/api/v1/contenido/imagenes/'.strtoupper($uno).'" alt="a">'
            .'<img src="/api/v1/contenido/imagenes/'.$dos.'" alt="b">';
        $noticia = $this->noticia($html);

        // Una fila que ya no corresponde al texto: la resincronización la quita.
        DB::table('noticia_imagenes')->insert(['noticia_id' => $noticia, 'file_media_uuid' => $sobra]);

        $migracion->up();

        $this->assertEqualsCanonicalizing(
            [$uno, $dos],
            DB::table('noticia_imagenes')->where('noticia_id', $noticia)->pluck('file_media_uuid')->all(),
        );
    }

    // ---- documentos de política ----------------------------------------------------------------

    public function test_el_documento_mas_reciente_queda_oficial_y_los_demas_anteriores(): void
    {
        $migracion = $this->antes();
        $politica = $this->politica();
        $viejo = $this->archivo(self::POLITICA, $politica, 'documento-oficial', ['created_at' => now()->subDays(3)]);
        $nuevo = $this->archivo(self::POLITICA, $politica, 'documento-oficial', ['created_at' => now()->subDay()]);

        // Estado de D8 con la ventana de despliegue: el vínculo apunta al viejo (se subió otro después).
        DB::table('politica_documentos')->insert(['politica_id' => $politica, 'rol' => 'oficial', 'file_media_uuid' => $viejo]);

        $migracion->up();

        $roles = DB::table('politica_documentos')->where('politica_id', $politica)->pluck('rol', 'file_media_uuid')->all();
        $this->assertEquals([$nuevo => 'oficial', $viejo => 'anterior'], $roles);
    }

    public function test_la_clave_y_las_reglas_de_politica_documentos_admiten_varios_con_un_solo_oficial(): void
    {
        $this->assertSame(['politica_id', 'file_media_uuid'], $this->clavePrimaria('politica_documentos'));

        $politica = $this->politica();
        $a = $this->archivo(self::POLITICA, $politica, 'documento-oficial');
        $b = $this->archivo(self::POLITICA, $politica, 'documento-oficial');
        $c = $this->archivo(self::POLITICA, $politica, 'documento-oficial');

        DB::table('politica_documentos')->insert(['politica_id' => $politica, 'rol' => 'oficial', 'file_media_uuid' => $a]);
        DB::table('politica_documentos')->insert(['politica_id' => $politica, 'rol' => 'anterior', 'file_media_uuid' => $b]);
        DB::table('politica_documentos')->insert(['politica_id' => $politica, 'rol' => 'anterior', 'file_media_uuid' => $c]);

        $otro = $this->archivo(self::POLITICA, $politica, 'documento-oficial');

        DB::beginTransaction();
        try {
            DB::table('politica_documentos')->insert(['politica_id' => $politica, 'rol' => 'oficial', 'file_media_uuid' => $otro]);
            $this->fail('La base aceptó dos documentos oficiales en la misma política.');
        } catch (QueryException $e) {
            DB::rollBack();
            $this->assertSame('23505', $e->errorInfo[0] ?? null);
        }

        DB::beginTransaction();
        try {
            DB::table('politica_documentos')->insert(['politica_id' => $this->politica(), 'rol' => 'inventado', 'file_media_uuid' => $a]);
            $this->fail('La base aceptó un rol fuera de la lista.');
        } catch (QueryException $e) {
            DB::rollBack();
            $this->assertSame('23514', $e->errorInfo[0] ?? null);
        }
    }

    // ---- índice de la huella ---------------------------------------------------------------------

    public function test_crea_el_indice_de_la_huella(): void
    {
        $this->assertTrue($this->hayIndice('file_media_hash_sha256_index'));
    }

    // ---- aborta sin cambiar nada ------------------------------------------------------------------

    public function test_aborta_y_lista_las_imagenes_rotas_sin_cambiar_el_esquema(): void
    {
        $migracion = $this->antes();
        $falta = (string) Str::uuid();
        $noticia = $this->noticia('<img src="/api/v1/contenido/imagenes/'.$falta.'" alt="x">');

        try {
            $migracion->up();
            $this->fail('Debió abortar por la imagen que ya no existe.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($falta, $e->getMessage());
            $this->assertStringContainsString((string) $noticia, $e->getMessage());
        }

        $this->assertSame(['politica_id', 'rol'], $this->clavePrimaria('politica_documentos'), 'El esquema quedó como estaba.');
        $this->assertFalse($this->hayIndice('file_media_hash_sha256_index'));
    }

    public function test_aborta_y_lista_los_archivos_con_un_dueno_que_nadie_vincularia(): void
    {
        $migracion = $this->antes();
        $suelto = $this->archivo('App\\Models\\Entidad', 1, 'logos');

        try {
            $migracion->up();
            $this->fail('Debió abortar por el archivo sin vínculo posible.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($suelto, $e->getMessage());
        }

        $this->assertSame(['politica_id', 'rol'], $this->clavePrimaria('politica_documentos'));
    }

    // ---- idempotencia, down y comando ----------------------------------------------------------------

    public function test_repetirla_no_duplica_nada(): void
    {
        $migracion = $this->antes();
        $politica = $this->politica();
        $doc = $this->archivo(self::POLITICA, $politica, 'documento-oficial');
        $noticia = $this->noticia();
        $imagen = $this->archivo(self::NOTICIA, $noticia, 'imagen', ['metadata' => json_encode(['alt' => 'Mar'])]);

        $migracion->up();
        $migracion->up();

        $this->assertSame(1, DB::table('politica_documentos')->where('politica_id', $politica)->count());
        $this->assertSame($doc, DB::table('politica_documentos')->where('politica_id', $politica)->value('file_media_uuid'));
        $this->assertSame($imagen, DB::table('noticias')->where('id', $noticia)->value('imagen_principal_uuid'));
    }

    public function test_down_con_varios_anteriores_deja_solo_el_oficial_y_restaura_la_forma_de_d8(): void
    {
        $politica = $this->politica();
        $oficial = $this->archivo(self::POLITICA, $politica, 'documento-oficial');
        $a = $this->archivo(self::POLITICA, $politica, 'documento-oficial');
        $b = $this->archivo(self::POLITICA, $politica, 'documento-oficial');
        DB::table('politica_documentos')->insert([
            ['politica_id' => $politica, 'rol' => 'oficial', 'file_media_uuid' => $oficial],
            ['politica_id' => $politica, 'rol' => 'anterior', 'file_media_uuid' => $a],
            ['politica_id' => $politica, 'rol' => 'anterior', 'file_media_uuid' => $b],
        ]);

        $this->migracion()->down();

        $this->assertSame(['politica_id', 'rol'], $this->clavePrimaria('politica_documentos'));
        $this->assertSame([$oficial], DB::table('politica_documentos')->where('politica_id', $politica)->pluck('file_media_uuid')->all());
        $this->assertSame(3, DB::table('file_media')->where('collection', 'documento-oficial')->count(), 'Solo se pierden vínculos, nunca archivos.');
        $this->assertFalse($this->hayIndice('file_media_hash_sha256_index'));
    }

    public function test_el_comando_corrige_imagenes_pero_no_resucita_un_documento_desvinculado(): void
    {
        $politica = $this->politica();
        $vigente = $this->archivo(self::POLITICA, $politica, 'documento-oficial', ['created_at' => now()->subDay()]);
        $quitado = $this->archivo(self::POLITICA, $politica, 'documento-oficial', ['created_at' => now()->subDays(2)]);
        DB::table('politica_documentos')->insert(['politica_id' => $politica, 'rol' => 'oficial', 'file_media_uuid' => $vigente]);
        // `$quitado` conserva su fila vieja (dueño política, colección documento-oficial) pero no tiene vínculo.

        $noticia = $this->noticia();
        $imagen = $this->archivo(self::NOTICIA, $noticia, 'imagen', ['metadata' => json_encode(['alt' => 'Mar'])]);
        DB::table('noticias')->where('id', $noticia)->update(['imagen_principal_uuid' => null, 'imagen_principal_alt' => 'desfasado']);

        $this->artisan('mediateca:resincronizar')->assertSuccessful();

        $this->assertSame($imagen, DB::table('noticias')->where('id', $noticia)->value('imagen_principal_uuid'));
        $this->assertSame('Mar', DB::table('noticias')->where('id', $noticia)->value('imagen_principal_alt'));
        $this->assertSame(
            [$vigente],
            DB::table('politica_documentos')->where('politica_id', $politica)->pluck('file_media_uuid')->all(),
            'El documento que el editor quitó no vuelve.',
        );
        $this->assertNotContains($quitado, DB::table('politica_documentos')->pluck('file_media_uuid')->all());
    }
}
