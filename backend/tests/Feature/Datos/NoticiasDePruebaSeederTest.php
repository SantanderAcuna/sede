<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\FileMedia;
use App\Models\Noticia;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\NoticiasDePruebaSeeder;
use Database\Seeders\SedeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Las diez noticias de prueba (M01-6).
 *
 * El riesgo que estas pruebas cierran no es que el sembrador funcione, sino que
 * **llegue donde no debe**: la sede no puede publicar noticias inventadas (CT-01),
 * y `staging` y producción se despliegan con `APP_ENV=production` los dos (HECHO,
 * `deploy/plantilla.env`), así que el nombre del entorno no los distingue.
 */
final class NoticiasDePruebaSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake((string) config('filesystems.default'));
    }

    public function test_siembra_diez_noticias_publicadas_y_completas(): void
    {
        $this->seed(NoticiasDePruebaSeeder::class);

        $this->assertSame(10, Noticia::query()->count());
        $this->assertSame(10, Noticia::query()->publicadas()->count());

        foreach (Noticia::query()->with('imagenPrincipal')->get() as $noticia) {
            $this->assertSame([], $noticia->problemas(), "«{$noticia->titulo}» quedó incompleta");
            $this->assertNotNull($noticia->imagenPrincipal);
            $this->assertNotSame('', trim((string) $noticia->imagenAlt()));
        }
    }

    public function test_cada_noticia_dice_que_es_de_prueba(): void
    {
        $this->seed(NoticiasDePruebaSeeder::class);

        foreach (Noticia::query()->get() as $noticia) {
            $this->assertStringContainsStringIgnoringCase('prueba', $noticia->titulo);
            $this->assertStringContainsStringIgnoringCase('no es información institucional', $noticia->contenido);
        }
    }

    public function test_es_idempotente(): void
    {
        $this->seed(NoticiasDePruebaSeeder::class);
        $this->seed(NoticiasDePruebaSeeder::class);

        $this->assertSame(10, Noticia::query()->withTrashed()->count());
        $this->assertSame(10, FileMedia::query()->where('model_type', Noticia::class)->count());
    }

    public function test_respeta_lo_que_una_persona_haya_cambiado(): void
    {
        $this->seed(NoticiasDePruebaSeeder::class);

        $noticia = Noticia::query()->orderBy('id')->firstOrFail();
        $noticia->forceFill(['descripcion_corta' => 'Editada a mano.'])->save();

        $this->seed(NoticiasDePruebaSeeder::class);

        $this->assertSame('Editada a mano.', $noticia->refresh()->descripcion_corta);
    }

    public function test_cubre_los_casos_de_diseno_que_se_quieren_probar(): void
    {
        $this->seed(NoticiasDePruebaSeeder::class);

        $noticias = Noticia::query()->with('imagenPrincipal')->get();

        // Más de una página en /noticias (9 por página) y más de las 6 de la portada.
        $this->assertGreaterThan(9, $noticias->count());

        // Proporciones distintas: ancha, alta y cuadrada, para ver el recorte.
        $proporciones = $noticias->map(function (Noticia $n): string {
            $imagen = $n->imagenPrincipal;
            $bytes = Storage::disk($imagen->disk)->get($imagen->path);
            [$ancho, $alto] = getimagesizefromstring((string) $bytes);

            return match (true) {
                $ancho > $alto => 'ancha',
                $ancho < $alto => 'alta',
                default => 'cuadrada',
            };
        })->unique()->sort()->values()->all();
        $this->assertSame(['alta', 'ancha', 'cuadrada'], $proporciones);

        // Un título en el límite de 150 caracteres, para ver cómo se ajusta en la tarjeta.
        $this->assertSame(150, $noticias->max(fn (Noticia $n): int => mb_strlen($n->titulo)));

        // Destacadas y no destacadas.
        $this->assertTrue($noticias->contains('destacada', true));
        $this->assertTrue($noticias->contains('destacada', false));

        // Fechas distintas, para que el orden inverso sea comprobable.
        $this->assertSame(10, $noticias->pluck('fecha_publicacion')->unique()->count());
    }

    public function test_se_ven_por_la_api_en_orden_inverso(): void
    {
        $this->seed(NoticiasDePruebaSeeder::class);

        $portada = $this->getJson('/api/v1/noticias?per_page=6')->assertOk()->json('data');
        $this->assertCount(6, $portada);

        $fechas = array_column($portada, 'fecha_publicacion');
        $ordenadas = $fechas;
        rsort($ordenadas);
        $this->assertSame($ordenadas, $fechas);

        $this->getJson('/api/v1/noticias?per_page=9&page=2')->assertOk()->assertJsonCount(1, 'data');

        $this->get($portada[0]['imagen']['url'])->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_se_niega_en_produccion_sin_permiso_expreso(): void
    {
        $this->app['env'] = 'production';
        config(['datos_de_prueba.permitir' => false]);

        $this->expectException(RuntimeException::class);

        try {
            $this->seed(NoticiasDePruebaSeeder::class);
        } finally {
            $this->assertSame(0, Noticia::query()->withTrashed()->count());
        }
    }

    public function test_corre_en_staging_con_permiso_expreso_aunque_el_entorno_sea_produccion(): void
    {
        $this->app['env'] = 'production';
        config(['datos_de_prueba.permitir' => true]);

        $this->seed(NoticiasDePruebaSeeder::class);

        $this->assertSame(10, Noticia::query()->count());
    }

    public function test_el_despliegue_no_las_siembra(): void
    {
        $this->seed(SedeSeeder::class);

        $this->assertSame(0, Noticia::query()->withTrashed()->count());
    }

    public function test_el_sembrador_por_defecto_no_las_siembra_fuera_de_local(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Noticia::query()->withTrashed()->count());
    }

    public function test_el_sembrador_por_defecto_si_las_siembra_en_local(): void
    {
        $this->app['env'] = 'local';

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(10, Noticia::query()->count());
    }
}
