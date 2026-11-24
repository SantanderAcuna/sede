<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Banner;
use App\Models\FileMedia;
use App\Models\Noticia;
use App\Models\Politica;
use App\Services\FilesMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\Concerns\CreaFilasDeArchivos;
use Tests\TestCase;

/**
 * Los archivos no tienen dueño polimórfico (0D, D10a): quién los usa lo dicen las claves foráneas de
 * noticias, banners, imágenes del texto y documentos de política; `uso` solo elige la política de limpieza.
 */
final class FileMediaPorUsoTest extends TestCase
{
    use CreaFilasDeArchivos;
    use RefreshDatabase;

    public function test_la_imagen_de_un_banner_se_encuentra_por_su_clave_foranea(): void
    {
        $banner = $this->banner();
        $uuid = $this->fila();
        DB::table('banners')->where('id', $banner['id'])->update(['imagen_uuid' => $uuid]);

        $this->assertNotNull(Banner::query()->findOrFail($banner['id'])->imagenPrincipal);
    }

    public function test_un_archivo_sin_vinculo_no_es_la_imagen_de_ningun_banner(): void
    {
        $banner = $this->banner();
        $this->fila();

        $this->assertNull(Banner::query()->findOrFail($banner['id'])->imagenPrincipal);
    }

    public function test_la_relacion_de_noticia_usa_la_clave_foranea_al_uuid(): void
    {
        $relacion = (new Noticia)->imagenPrincipal();

        $this->assertSame('imagen_principal_uuid', $relacion->getForeignKeyName());
        $this->assertSame('uuid', $relacion->getOwnerKeyName());
    }

    public function test_el_documento_oficial_de_una_politica_se_encuentra_por_su_vinculo(): void
    {
        $politica = $this->politica();
        $uuid = $this->fila(['uso' => FileMedia::USO_DOCUMENTO]);
        DB::table('politica_documentos')->insert(['politica_id' => $politica['id'], 'rol' => 'oficial', 'file_media_uuid' => $uuid]);

        $this->assertNotNull(Politica::query()->findOrFail($politica['id'])->documentoOficial());
    }

    public function test_upload_guarda_el_hash_sha256_el_uso_y_quien_lo_subio(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $archivo = UploadedFile::fake()->createWithContent('foto.png', 'contenido de prueba');

        $fila = (new FilesMediaService)->upload($archivo, FileMedia::USO_CONTENIDO);

        $this->assertSame(hash('sha256', 'contenido de prueba'), $fila->hash_sha256);
        $this->assertDatabaseHas('file_media', ['uuid' => $fila->uuid, 'uso' => 'contenido', 'subido_por' => null]);
        $this->assertSame("file_media/contenido/{$fila->filename}", $fila->path);
    }

    public function test_upload_con_un_uso_desconocido_falla_y_no_deja_archivo_huerfano(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        try {
            (new FilesMediaService)->upload(UploadedFile::fake()->createWithContent('a.png', 'x'), 'logos');
            $this->fail('Debía fallar: el uso no existe.');
        } catch (InvalidArgumentException) {
            $this->assertSame([], Storage::disk('local')->allFiles());
            $this->assertSame(0, DB::table('file_media')->count());
        }
    }
}
