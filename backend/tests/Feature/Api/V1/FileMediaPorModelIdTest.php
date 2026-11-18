<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Banner;
use App\Models\Noticia;
use App\Models\Politica;
use App\Services\FilesMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\Concerns\CreaFilasDeArchivos;
use Tests\TestCase;

/**
 * Las lecturas de `file_media` van por `model_type` + `model_id` (Fase 0B, T8).
 *
 * Antes iban por `model_uuid`; las filas se siguen escribiendo con los dos, pero el dueño
 * se reconoce por el id numérico, que es el que la migración vuelve obligatorio y el que
 * cubre la unicidad. `model_type` se conserva en cada lectura: dos modelos distintos
 * pueden tener el mismo id.
 */
final class FileMediaPorModelIdTest extends TestCase
{
    use CreaFilasDeArchivos;
    use RefreshDatabase;

    public function test_la_imagen_de_un_banner_se_encuentra_por_model_id(): void
    {
        $banner = $this->banner();
        $this->fila(['model_type' => Banner::class, 'model_id' => $banner['id'], 'model_uuid' => null]);

        $this->assertNotNull(Banner::query()->findOrFail($banner['id'])->imagenPrincipal);
    }

    public function test_la_imagen_de_un_banner_no_se_confunde_con_otro_modelo_del_mismo_id(): void
    {
        $banner = $this->banner();
        $this->fila(['model_type' => Noticia::class, 'model_id' => $banner['id'], 'model_uuid' => null]);

        $this->assertNull(Banner::query()->findOrFail($banner['id'])->imagenPrincipal);
    }

    public function test_la_relacion_de_noticia_usa_model_id(): void
    {
        $relacion = (new Noticia)->imagenPrincipal();

        $this->assertSame('model_id', $relacion->getForeignKeyName());
        $this->assertSame('id', $relacion->getLocalKeyName());
    }

    public function test_el_documento_oficial_de_una_politica_se_encuentra_por_model_id(): void
    {
        $politica = $this->politica();
        $this->fila([
            'model_type' => Politica::class, 'model_id' => $politica['id'], 'model_uuid' => null,
            'collection' => Politica::COLECCION_DOCUMENTO,
        ]);

        $this->assertNotNull(Politica::query()->findOrFail($politica['id'])->documentoOficial());
    }

    public function test_get_by_collection_busca_por_model_id_y_model_type(): void
    {
        $banner = $this->banner();
        $propia = $this->fila(['model_type' => Banner::class, 'model_id' => $banner['id'], 'model_uuid' => null]);
        $this->fila(['model_type' => Noticia::class, 'model_id' => $banner['id']]);
        $this->fila(['model_type' => Banner::class, 'model_id' => $banner['id'] + 100, 'collection' => 'otra']);

        $archivos = (new FilesMediaService)->getByCollection(Banner::query()->findOrFail($banner['id']), 'imagen');

        $this->assertSame([$propia], $archivos->pluck('uuid')->all());
    }

    public function test_get_by_collection_de_un_objeto_sin_id_no_devuelve_archivos_ajenos(): void
    {
        $this->fila(['model_type' => 'class@anonymous', 'model_id' => 1]);
        $sinId = new class
        {
            public ?int $id = null;
        };

        $this->assertCount(0, (new FilesMediaService)->getByCollection($sinId, 'imagen'));
    }

    public function test_upload_guarda_el_hash_sha256_del_contenido(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $banner = Banner::query()->findOrFail($this->banner()['id']);
        $archivo = UploadedFile::fake()->createWithContent('foto.png', 'contenido de prueba');

        $fila = (new FilesMediaService)->upload($archivo, 'imagen', $banner);

        $this->assertSame(hash('sha256', 'contenido de prueba'), $fila->hash_sha256);
        $this->assertDatabaseHas('file_media', ['uuid' => $fila->uuid, 'hash_sha256' => hash('sha256', 'contenido de prueba'), 'model_id' => $banner->id]);
    }

    public function test_upload_sin_id_de_modelo_falla_claro_y_no_deja_archivo_huerfano(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $sinId = new class
        {
            public ?int $id = null;
        };

        try {
            (new FilesMediaService)->upload(UploadedFile::fake()->createWithContent('a.png', 'x'), 'imagen', $sinId);
            $this->fail('Debía fallar: no hay dueño al que asociar el archivo.');
        } catch (InvalidArgumentException) {
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }
}
