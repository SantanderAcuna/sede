<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Http\Resources\FileMediaResource;
use App\Models\Banner;
use App\Models\FileMedia;
use App\Services\FilesMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaFilasDeArchivos;
use Tests\TestCase;

/**
 * Retiro de `model_uuid` de la API y de lo que se escribe (Fase 0B, entrega A; ADR 0005).
 *
 * La columna se borró en la entrega B: aquí se comprueba que no se escribe, que no sale en la
 * respuesta y que el recurso y el contrato dicen lo mismo.
 */
final class FileMediaSinModelUuidTest extends TestCase
{
    use CreaFilasDeArchivos;
    use RefreshDatabase;

    public function test_upload_guarda_el_dueno_por_model_id_y_sin_model_uuid(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $banner = Banner::query()->findOrFail($this->banner()['id']);

        $fila = (new FilesMediaService)->upload(UploadedFile::fake()->createWithContent('foto.png', 'x'), 'imagen', $banner);

        $this->assertDatabaseHas('file_media', ['uuid' => $fila->uuid, 'model_id' => $banner->id]);
        $this->assertArrayNotHasKey('model_uuid', $fila->getAttributes());
    }

    public function test_el_recurso_no_expone_model_uuid(): void
    {
        $fila = FileMedia::factory()->create();

        $this->assertArrayNotHasKey('model_uuid', (new FileMediaResource($fila))->toArray(Request::create('/')));
    }

    public function test_el_recurso_y_el_contrato_dicen_lo_mismo(): void
    {
        $fila = FileMedia::factory()->create();
        $claves = array_keys((new FileMediaResource($fila))->toArray(Request::create('/')));
        $contrato = $this->esquemaFileMediaItem();

        $this->assertEqualsCanonicalizing($claves, $contrato['propiedades'], 'El recurso y FileMediaItem deben tener las mismas claves.');
        $this->assertArrayNotHasKey('model_uuid', array_flip($contrato['propiedades']));
    }

    public function test_el_contrato_declara_model_id_entero_y_obligatorio(): void
    {
        $contrato = $this->esquemaFileMediaItem();

        $this->assertContains('model_id', $contrato['requeridas']);
        $this->assertSame('integer', $contrato['tipos']['model_id'], 'model_id es NOT NULL desde T8: ya no admite null.');
        $this->assertIsInt((new FileMediaResource(FileMedia::factory()->create()))->toArray(Request::create('/'))['model_id']);
    }

    /**
     * Lee `FileMediaItem` del contrato sin analizador de YAML (no se añaden dependencias, AGENTS.md).
     *
     * @return array{propiedades: list<string>, requeridas: list<string>, tipos: array<string, string>}
     */
    private function esquemaFileMediaItem(): array
    {
        $lineas = file(base_path('../contract/openapi.yaml'), FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lineas);
        $dentro = false;
        $enPropiedades = false;
        $propiedades = [];
        $requeridas = [];
        $tipos = [];
        foreach ($lineas as $linea) {
            if (! $dentro) {
                $dentro = $linea === '    FileMediaItem:';

                continue;
            }
            if (preg_match('/^    \S/', $linea) === 1) {
                break;
            }
            if (preg_match('/^      required: \[(.*)\]/', $linea, $m) === 1) {
                $requeridas = array_map('trim', explode(',', $m[1]));
            }
            if ($linea === '      properties:') {
                $enPropiedades = true;

                continue;
            }
            if ($enPropiedades && preg_match('/^        (\w+): \{ type: \[?(\w+)/', $linea, $m) === 1) {
                $propiedades[] = $m[1];
                $tipos[$m[1]] = $m[2];
            }
        }
        $this->assertNotEmpty($propiedades, 'No se pudo leer FileMediaItem del contrato: sin esto la prueba pasaría sin comparar nada.');

        return ['propiedades' => $propiedades, 'requeridas' => $requeridas, 'tipos' => $tipos];
    }
}
