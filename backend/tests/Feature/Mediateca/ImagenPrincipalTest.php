<?php

declare(strict_types=1);

namespace Tests\Feature\Mediateca;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Models\FileMedia;
use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * 0D, D9: la imagen principal de noticias y banners se lee y se escribe por las columnas nuevas
 * (`imagen_principal_uuid` / `imagen_uuid` y su alt), con ESCRITURA DOBLE de lo viejo hasta D10 para poder
 * volver atrás. Reemplazar una imagen: primero se sube la nueva, luego se actualiza el vínculo y solo
 * después se borra la anterior, así una subida fallida nunca deja a la noticia sin imagen.
 */
final class ImagenPrincipalTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake((string) config('filesystems.default'));
    }

    private function png(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('foto.png', (string) base64_decode(self::PNG, true));
    }

    private function editor(): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo(CatalogoPermisos::de('noticias'));

        return $u;
    }

    private function gestor(): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo(CatalogoPermisos::de('banners'));

        return $u;
    }

    private function borrador(User $editor): string
    {
        $uuid = (string) $this->actingAs($editor)->postJson('/api/v1/panel/noticias', [
            'titulo' => 'Noticia de prueba', 'descripcion_corta' => 'Resumen', 'contenido' => 'Texto',
            'fecha_publicacion' => '2026-11-01T10:00:00-05:00',
        ])->assertCreated()->json('data.uuid');

        return $uuid;
    }

    private function subirANoticia(User $editor, string $uuid, string $alt = 'Una playa'): void
    {
        $this->actingAs($editor)->post("/api/v1/panel/noticias/{$uuid}/imagen", ['imagen' => $this->png(), 'alt' => $alt], ['Accept' => 'application/json'])->assertOk();
    }

    /** Un archivo suelto: solo lo encuentra quien siga las claves foráneas de la noticia o del banner. */
    private function archivoSoloNuevo(string $alt): string
    {
        $uuid = (string) Str::uuid();
        $disco = (string) config('filesystems.default');
        $ruta = "file_media/prueba/{$uuid}.png";
        Storage::disk($disco)->put($ruta, (string) base64_decode(self::PNG, true));

        DB::table('file_media')->insert([
            'uuid' => $uuid, 'uso' => 'imagen',
            'filename' => "{$uuid}.png", 'original_filename' => 'foto.png', 'mime_type' => 'image/png', 'size' => 70,
            'disk' => $disco, 'path' => $ruta,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $uuid;
    }

    // ---- lecturas por las columnas del uso ---------------------------------------------------------------

    public function test_la_noticia_publica_lee_imagen_y_alt_de_sus_columnas(): void
    {
        $archivo = $this->archivoSoloNuevo('x');
        DB::table('noticias')->insert([
            'uuid' => (string) Str::uuid(), 'slug' => 'con-foto', 'titulo' => 'Con foto', 'descripcion_corta' => 'r',
            'contenido' => '<p>t</p>', 'fecha_publicacion' => now()->subDay(), 'activo' => true,
            'imagen_principal_uuid' => $archivo, 'imagen_principal_alt' => 'Alt de la columna nueva',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/noticias/con-foto')->assertOk()->assertJsonPath('data.imagen.alt', 'Alt de la columna nueva');
        $this->get('/api/v1/noticias/con-foto/imagen')->assertOk();
    }

    public function test_el_banner_publico_lee_imagen_y_alt_de_sus_columnas(): void
    {
        $archivo = $this->archivoSoloNuevo('x');
        $uuid = (string) Str::uuid();
        DB::table('banners')->insert([
            'uuid' => $uuid, 'titulo' => 'Banner', 'descripcion' => 'd', 'activo' => true, 'orden' => 1,
            'imagen_uuid' => $archivo, 'imagen_alt' => 'Alt del banner nuevo',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/banners')->assertOk()->assertJsonPath('data.0.imagen.alt', 'Alt del banner nuevo');
        $this->get("/api/v1/banners/{$uuid}/imagen")->assertOk();
    }

    // ---- escritura ---------------------------------------------------------------------------------------

    public function test_subir_la_imagen_de_una_noticia_vincula_el_archivo_y_guarda_el_alt_en_la_noticia(): void
    {
        $editor = $this->editor();
        $uuid = $this->borrador($editor);

        $this->subirANoticia($editor, $uuid, 'Una playa');

        $noticia = DB::table('noticias')->where('uuid', $uuid)->sole();
        $this->assertNotNull($noticia->imagen_principal_uuid);
        $this->assertSame('Una playa', $noticia->imagen_principal_alt);

        $archivo = DB::table('file_media')->where('uuid', $noticia->imagen_principal_uuid)->sole();
        $this->assertSame('imagen', $archivo->uso);
        $this->assertNull($archivo->subido_por, 'Solo el editor de contenido registra quién subió.');
    }

    public function test_reemplazar_la_imagen_deja_una_sola_y_borra_la_anterior_de_la_base_y_del_disco(): void
    {
        $editor = $this->editor();
        $uuid = $this->borrador($editor);

        $this->subirANoticia($editor, $uuid, 'Primera');
        $primera = DB::table('noticias')->where('uuid', $uuid)->value('imagen_principal_uuid');
        $ruta = (string) DB::table('file_media')->where('uuid', $primera)->value('path');
        Storage::disk((string) config('filesystems.default'))->assertExists($ruta);

        $this->subirANoticia($editor, $uuid, 'Segunda');

        $noticia = DB::table('noticias')->where('uuid', $uuid)->sole();
        $this->assertNotSame($primera, $noticia->imagen_principal_uuid);
        $this->assertSame('Segunda', $noticia->imagen_principal_alt);
        $this->assertFalse(DB::table('file_media')->where('uuid', $primera)->exists());
        Storage::disk((string) config('filesystems.default'))->assertMissing($ruta);
        $this->assertSame(1, DB::table('file_media')->where('uso', 'imagen')->count(), 'Solo queda la nueva.');
    }

    public function test_si_la_subida_falla_la_imagen_anterior_sigue_en_su_sitio(): void
    {
        $editor = $this->editor();
        // El estado de partida se arma en la base: el controlador de la ruta se guarda en caché dentro de una
        // misma prueba, así que la primera petición a la ruta ya debe llevar el servicio falso.
        $primera = $this->archivoSoloNuevo('Primera');
        $uuid = (string) Str::uuid();
        DB::table('noticias')->insert([
            'uuid' => $uuid, 'slug' => 'con-foto', 'titulo' => 'Con foto', 'descripcion_corta' => 'r', 'contenido' => '<p>t</p>',
            'fecha_publicacion' => now(), 'activo' => false, 'imagen_principal_uuid' => $primera,
            'imagen_principal_alt' => 'Primera', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $falso = Mockery::mock(FilesMediaServiceInterface::class);
        $falso->shouldReceive('upload')->once()->andThrow(new RuntimeException('disco lleno'));
        $falso->shouldNotReceive('delete');
        $this->app->instance(FilesMediaServiceInterface::class, $falso);

        $this->withoutExceptionHandling();
        try {
            $this->actingAs($editor)->post("/api/v1/panel/noticias/{$uuid}/imagen", ['imagen' => $this->png(), 'alt' => 'Segunda'], ['Accept' => 'application/json']);
            $this->fail('La subida fallida debía propagarse.');
        } catch (RuntimeException $e) {
            $this->assertSame('disco lleno', $e->getMessage());
        }

        $this->assertSame($primera, DB::table('noticias')->where('uuid', $uuid)->value('imagen_principal_uuid'));
        $this->assertSame('Primera', DB::table('noticias')->where('uuid', $uuid)->value('imagen_principal_alt'));
        $this->assertTrue(DB::table('file_media')->where('uuid', $primera)->exists());
    }

    public function test_cambiar_solo_el_alt_de_la_noticia_escribe_la_columna_de_la_noticia(): void
    {
        $editor = $this->editor();
        $uuid = $this->borrador($editor);
        $this->subirANoticia($editor, $uuid, 'Primera');

        $this->actingAs($editor)->patchJson("/api/v1/panel/noticias/{$uuid}", ['imagen_alt' => 'Alt corregido'])
            ->assertOk()->assertJsonPath('data.imagen.alt', 'Alt corregido');

        $noticia = DB::table('noticias')->where('uuid', $uuid)->sole();
        $this->assertSame('Alt corregido', $noticia->imagen_principal_alt);
    }

    public function test_un_banner_vincula_su_imagen_y_reemplazar_borra_la_anterior(): void
    {
        $gestor = $this->gestor();
        $banner = (string) $this->actingAs($gestor)->postJson('/api/v1/panel/banners', ['titulo' => 'Predial'])->assertCreated()->json('data.uuid');

        $this->actingAs($gestor)->post("/api/v1/panel/banners/{$banner}/imagen", ['imagen' => $this->png(), 'alt' => 'Fachada'], ['Accept' => 'application/json'])->assertOk();
        $fila = DB::table('banners')->where('uuid', $banner)->sole();
        $primera = (string) $fila->imagen_uuid;
        $this->assertNotSame('', $primera);
        $this->assertSame('Fachada', $fila->imagen_alt);
        $this->assertSame('imagen', DB::table('file_media')->where('uuid', $primera)->value('uso'));

        $this->actingAs($gestor)->post("/api/v1/panel/banners/{$banner}/imagen", ['imagen' => $this->png(), 'alt' => 'Nueva'], ['Accept' => 'application/json'])->assertOk();
        $this->assertFalse(DB::table('file_media')->where('uuid', $primera)->exists());
        $this->assertSame('Nueva', DB::table('banners')->where('uuid', $banner)->value('imagen_alt'));

        $this->actingAs($gestor)->patchJson("/api/v1/panel/banners/{$banner}", ['imagen_alt' => 'Otra'])->assertOk();
        $nueva = (string) DB::table('banners')->where('uuid', $banner)->value('imagen_uuid');
        $this->assertSame('Otra', DB::table('banners')->where('uuid', $banner)->value('imagen_alt'));
        $this->assertInstanceOf(FileMedia::class, FileMedia::query()->where('uuid', $nueva)->first());
    }
}
