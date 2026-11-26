<?php

declare(strict_types=1);

namespace Tests\Feature\Mediateca;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 0D, D9: la imagen de contenido sin publicar se ve con la sesión REAL del editor.
 *
 * `actingAs` se salta el middleware de «frontend con estado» de Sanctum, que las rutas públicas quitan a
 * propósito (AC-26.2: una lectura anónima no abre sesión). Por eso la lectura con sesión opcional falló en
 * staging aunque las pruebas con `actingAs` pasaban: aquí se inicia sesión de verdad, con el `Referer` de la
 * sede, y se manda la cookie de sesión. Sin cookie de sesión la ruta sigue sin abrir sesión ni poner cookies.
 */
final class ImagenContenidoConSesionRealTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32)), 'session.driver' => 'file', 'session.files' => sys_get_temp_dir().'/sesiones-d9']);
        @mkdir((string) config('session.files'), 0777, true);
        $this->seed(PermissionSeeder::class);
        Storage::fake((string) config('filesystems.default'));
    }

    /** Como entre dos peticiones reales: nada de la sesión queda en memoria, solo lo que está en el archivo. */
    private function olvidarSesionEnMemoria(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
    }

    /** @return array<string, string> */
    private function cabeceras(): array
    {
        return ['Referer' => 'http://'.((array) config('sanctum.stateful'))[0].'/', 'Accept' => 'application/json'];
    }

    /**
     * Inicia sesión de verdad y devuelve el nombre y valor (cifrado) de la cookie de sesión.
     *
     * @return array{0: string, 1: string}
     */
    private function iniciarSesion(User $u): array
    {
        $r = $this->withHeaders($this->cabeceras())->post('/api/v1/panel/login', ['email' => $u->email, 'password' => 'password'])->assertOk();
        foreach ($r->headers->getCookies() as $c) {
            if ($c->getName() === config('session.cookie')) {
                return [$c->getName(), (string) $c->getValue()];
            }
        }
        $this->fail('El login no entregó la cookie de sesión.');
    }

    /** @return array{0: User, 1: string} */
    private function editorConImagenSinPublicar(): array
    {
        $u = User::factory()->createOne(['password' => bcrypt('password')]);
        $u->givePermissionTo('noticias.editar');
        $im = imagecreatetruecolor(8, 8);
        imagefill($im, 0, 0, 0x336699);
        ob_start();
        imagepng($im);
        $bytes = (string) ob_get_clean();
        $uuid = (string) $this->actingAs($u)->postJson('/api/v1/panel/contenido/imagenes', [
            'imagen' => UploadedFile::fake()->createWithContent('f.png', $bytes), 'alt' => 'x',
        ])->assertCreated()->json('data.uuid');
        $this->app['auth']->forgetGuards();

        return [$u, $uuid];
    }

    public function test_el_editor_con_cookie_de_sesion_ve_la_imagen_sin_publicar_con_cache_privada(): void
    {
        [$u, $uuid] = $this->editorConImagenSinPublicar();
        [$nombre, $valor] = $this->iniciarSesion($u);
        $this->olvidarSesionEnMemoria();
        $this->olvidarSesionEnMemoria();
        $r = $this->withHeaders($this->cabeceras())->withUnencryptedCookie($nombre, $valor)->get('/api/v1/contenido/imagenes/'.$uuid);

        $r->assertOk();
        $this->assertStringContainsString('no-store', (string) $r->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $r->headers->get('Cache-Control'));
        // T9.7: nginx no guarda una respuesta con `Set-Cookie`; es la segunda defensa (tras la cookie y `private`)
        // para que el borrador del editor no llegue a la caché compartida. Esta prueba fija que la ruta la emite.
        $nombres = array_map(static fn ($c): string => $c->getName(), $r->headers->getCookies());
        $this->assertContains((string) config('session.cookie'), $nombres, 'La respuesta al editor debe renovar su cookie de sesión.');
    }

    public function test_sin_cookie_de_sesion_la_lectura_no_abre_sesion_ni_pone_cookies(): void
    {
        [, $uuid] = $this->editorConImagenSinPublicar();
        DB::table('noticias')->insert([
            'uuid' => (string) Str::uuid(), 'slug' => 'pub', 'titulo' => 'T', 'descripcion_corta' => 'c',
            'contenido' => '<p>x</p>', 'activo' => true, 'fecha_publicacion' => '2026-01-01 10:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('noticia_imagenes')->insert(['noticia_id' => (int) DB::table('noticias')->value('id'), 'file_media_uuid' => $uuid, 'created_at' => now()]);

        $r = $this->withHeaders($this->cabeceras())->get('/api/v1/contenido/imagenes/'.$uuid);

        $r->assertOk();
        $this->assertSame([], $r->headers->getCookies(), 'Una lectura anónima no puede poner cookies (AC-26.2).');
        $this->assertStringContainsString('public', (string) $r->headers->get('Cache-Control'));
    }

    public function test_una_sesion_real_sin_permiso_de_contenido_ve_404_en_lo_sin_publicar(): void
    {
        [, $uuid] = $this->editorConImagenSinPublicar();
        $otro = User::factory()->createOne(['password' => bcrypt('password')]);
        [$nombre, $valor] = $this->iniciarSesion($otro);
        $this->olvidarSesionEnMemoria();

        $this->withHeaders($this->cabeceras())->withUnencryptedCookie($nombre, $valor)->get('/api/v1/contenido/imagenes/'.$uuid)->assertNotFound();
    }
}
