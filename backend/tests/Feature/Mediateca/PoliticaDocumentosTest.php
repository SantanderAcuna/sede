<?php

declare(strict_types=1);

namespace Tests\Feature\Mediateca;

use App\Models\Politica;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PoliticaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 0D, D9 (decisión 4, opción A): una política conserva sus documentos.
 *
 * Subir uno nuevo baja al oficial a `anterior`; «quitar» solo desvincula y el archivo se queda en la mediateca
 * (Ley 594/2000); si se quita el oficial, el anterior más reciente lo releva. Adjuntar y quitar dejan bitácora.
 */
final class PoliticaDocumentosTest extends TestCase
{
    use RefreshDatabase;

    private Politica $politica;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, PoliticaSeeder::class]);
        Storage::fake((string) config('filesystems.default'));
        $this->politica = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail();
    }

    private function gestor(): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo(['politicas.ver', 'politicas.editar', 'archivos.ver', 'archivos.crear', 'archivos.eliminar']);

        return $u;
    }

    private function subir(User $u, string $contenido): string
    {
        $r = $this->actingAs($u)->postJson("/api/v1/panel/entidad/{$this->politica->uuid}/archivos", [
            'archivo' => UploadedFile::fake()->createWithContent('acto.pdf', "%PDF-1.4\n{$contenido}"),
            'collection' => Politica::COLECCION_DOCUMENTO,
        ])->assertOk();

        return (string) $r->json('data.uuid');
    }

    /** @return array<string, string> uuid => rol */
    private function roles(): array
    {
        return DB::table('politica_documentos')->where('politica_id', $this->politica->id)->pluck('rol', 'file_media_uuid')->all();
    }

    public function test_subir_un_segundo_documento_pasa_el_primero_a_anterior(): void
    {
        $u = $this->gestor();
        $a = $this->subir($u, 'a');
        $b = $this->subir($u, 'b');

        $this->assertSame([$a => 'anterior', $b => 'oficial'], $this->roles());
        $this->assertSame($b, $this->politica->refresh()->documentoOficial()?->uuid);
        $this->assertSame([$b, $a], $this->politica->documentos()->pluck('uuid')->all());
    }

    public function test_quitar_el_oficial_conserva_el_archivo_y_el_anterior_mas_reciente_lo_releva(): void
    {
        $u = $this->gestor();
        $a = $this->subir($u, 'a');
        $b = $this->subir($u, 'b');
        $c = $this->subir($u, 'c');
        DB::table('file_media')->where('uuid', $a)->update(['created_at' => now()->subDays(3)]);
        DB::table('file_media')->where('uuid', $b)->update(['created_at' => now()->subDays(2)]);

        $this->actingAs($u)->deleteJson("/api/v1/panel/archivos/{$c}")
            ->assertOk()->assertJsonPath('message', 'Documento quitado. El archivo se conserva.');

        $this->assertSame([$a => 'anterior', $b => 'oficial'], $this->roles());
        $this->assertDatabaseHas('file_media', ['uuid' => $c]);
        $rutaC = (string) DB::table('file_media')->where('uuid', $c)->value('path');
        Storage::disk((string) config('filesystems.default'))->assertExists($rutaC);
    }

    public function test_quitar_un_anterior_no_cambia_al_oficial(): void
    {
        $u = $this->gestor();
        $a = $this->subir($u, 'a');
        $b = $this->subir($u, 'b');

        $this->actingAs($u)->deleteJson("/api/v1/panel/archivos/{$a}")->assertOk();

        $this->assertSame([$b => 'oficial'], $this->roles());
    }

    public function test_quitar_el_unico_deja_la_politica_sin_documento_publicado(): void
    {
        $u = $this->gestor();
        $a = $this->subir($u, 'a');

        $this->actingAs($u)->deleteJson("/api/v1/panel/archivos/{$a}")->assertOk();

        $this->assertSame([], $this->roles());
        $this->assertNull($this->politica->refresh()->documentoOficial());
    }

    public function test_un_documento_oficial_sin_vinculo_no_se_borra_desde_la_api(): void
    {
        $u = $this->gestor();
        $a = $this->subir($u, 'a');
        DB::table('politica_documentos')->where('file_media_uuid', $a)->delete();

        $this->actingAs($u)->deleteJson("/api/v1/panel/archivos/{$a}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Los documentos oficiales no se eliminan del sistema.');
        $this->assertDatabaseHas('file_media', ['uuid' => $a]);
    }

    public function test_adjuntar_y_quitar_dejan_constancia_en_la_bitacora(): void
    {
        $u = $this->gestor();
        $a = $this->subir($u, 'a');
        $this->actingAs($u)->deleteJson("/api/v1/panel/archivos/{$a}")->assertOk();

        $filas = DB::table('audit_log')->where('recurso', 'politicas')->where('recurso_id', $this->politica->id)
            ->whereIn('accion', ['politica_documento_adjuntado', 'politica_documento_quitado'])
            ->orderBy('id')->get();

        $this->assertSame(['politica_documento_adjuntado', 'politica_documento_quitado'], $filas->pluck('accion')->all());
        $this->assertSame($u->id, (int) $filas[0]->causer_id);
        $this->assertSame($a, json_decode((string) $filas[1]->cambios, true)['archivo']);
        $this->assertSame('oficial', json_decode((string) $filas[1]->cambios, true)['era']);
    }

    public function test_el_listado_de_la_api_trae_el_oficial_primero(): void
    {
        $u = $this->gestor();
        $a = $this->subir($u, 'a');
        $b = $this->subir($u, 'b');

        $r = $this->actingAs($u)->getJson("/api/v1/panel/entidad/{$this->politica->uuid}/archivos?collection=".Politica::COLECCION_DOCUMENTO)->assertOk();

        $this->assertSame([$b, $a], array_column((array) $r->json('data'), 'uuid'));
    }

    public function test_sin_permiso_de_politicas_no_se_quita_un_documento(): void
    {
        $u = $this->gestor();
        $a = $this->subir($u, 'a');
        $solo = User::factory()->create();
        $solo->givePermissionTo(['archivos.eliminar']);

        $this->actingAs($solo)->deleteJson("/api/v1/panel/archivos/{$a}")->assertForbidden();
        $this->assertSame([$a => 'oficial'], $this->roles());
    }
}
