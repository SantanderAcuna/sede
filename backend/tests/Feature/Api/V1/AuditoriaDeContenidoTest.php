<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Database\Seeders\EntidadSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PoliticaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Quien crea, cambia, publica o elimina contenido del sitio deja constancia en la auditoría, igual que ya
 * lo hacen el carrusel, los roles, los usuarios y los documentos de política.
 *
 * Lo que se guarda es QUIÉN, QUÉ acción y sobre QUÉ registro, con el título o nombre y los campos tocados:
 * nunca el texto del contenido (puede ser largo y, en trámites, traer datos de contacto).
 */
final class AuditoriaDeContenidoTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, PoliticaSeeder::class, EntidadSeeder::class]);
        Storage::fake((string) config('filesystems.default'));
    }

    private function con(string $modulo): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo(CatalogoPermisos::de($modulo));
        $this->actingAs($usuario);

        return $usuario;
    }

    private function huella(string $accion): AuditLog
    {
        $filas = AuditLog::query()->where('accion', $accion)->get();
        $this->assertCount(1, $filas, "Debía haber exactamente una huella «{$accion}».");

        return $filas->first();
    }

    private function sinHuella(string $accion): void
    {
        $this->assertSame(0, AuditLog::query()->where('accion', $accion)->count(), "No debía haber huella «{$accion}».");
    }

    /** @return array<string, mixed> */
    private function cambios(AuditLog $fila): array
    {
        /** @var array<string, mixed> $c */
        $c = $fila->cambios ?? [];

        return $c;
    }

    // ── Noticias ────────────────────────────────────────────────────────────────

    public function test_noticias_crear_actualizar_imagen_y_eliminar_dejan_huella_sin_el_texto(): void
    {
        $editor = $this->con('noticias');
        $texto = 'Texto largo y privado del cuerpo de la noticia.';

        $uuid = (string) $this->postJson('/api/v1/panel/noticias', ['titulo' => 'Entrega de obras', 'contenido' => $texto, 'descripcion_corta' => 'Resumen', 'fecha_publicacion' => '2026-11-01'])->assertCreated()->json('data.uuid');
        $creada = $this->huella('noticia_creada');
        $this->assertSame('noticias', $creada->recurso);
        $this->assertSame($editor->id, (int) $creada->causer_id);
        $this->assertNotNull($creada->recurso_id);
        $this->assertSame('Entrega de obras', $this->cambios($creada)['titulo']);
        $this->assertSame('borrador', $this->cambios($creada)['estado']);

        $this->patchJson("/api/v1/panel/noticias/{$uuid}", ['titulo' => 'Entrega de obras 2'])->assertOk();
        $actualizada = $this->huella('noticia_actualizada');
        $this->assertSame($creada->recurso_id, $actualizada->recurso_id);
        $this->assertSame(['titulo'], $this->cambios($actualizada)['campos']);

        $this->postJson("/api/v1/panel/noticias/{$uuid}/imagen", [
            'imagen' => UploadedFile::fake()->createWithContent('a.png', (string) base64_decode(self::PNG, true)),
            'alt' => 'Obra terminada',
        ])->assertOk();
        $this->huella('noticia_imagen_cambiada');

        $this->deleteJson("/api/v1/panel/noticias/{$uuid}")->assertOk();
        $this->assertSame('Entrega de obras 2', $this->cambios($this->huella('noticia_eliminada'))['titulo']);

        foreach (AuditLog::query()->where('recurso', 'noticias')->get() as $fila) {
            $this->assertStringNotContainsString($texto, (string) json_encode($fila->cambios), 'El texto de la noticia no debe ir a la auditoría.');
        }
    }

    public function test_noticias_una_peticion_rechazada_no_deja_huella(): void
    {
        $this->con('noticias');
        $this->postJson('/api/v1/panel/noticias', ['titulo' => ''])->assertUnprocessable();
        $this->postJson('/api/v1/panel/noticias', ['titulo' => 'Publicada sin imagen', 'contenido' => 'x', 'activo' => true])->assertUnprocessable();

        $this->sinHuella('noticia_creada');
    }

    public function test_noticias_sin_permiso_no_deja_huella(): void
    {
        $sin = User::factory()->create();
        $this->actingAs($sin)->postJson('/api/v1/panel/noticias', ['titulo' => 'X', 'contenido' => 'y'])->assertForbidden();

        $this->sinHuella('noticia_creada');
    }

    // ── Políticas ───────────────────────────────────────────────────────────────

    public function test_politicas_crear_actualizar_y_eliminar_dejan_huella(): void
    {
        $gestor = $this->con('politicas');

        $slug = (string) $this->postJson('/api/v1/panel/politicas', [
            'nombre' => 'Política de accesibilidad',
            'secciones' => [['id' => 'objeto', 'titulo' => 'Objeto', 'parrafos' => ['Texto confidencial del objeto.']]],
        ])->assertCreated()->json('data.slug');
        $creada = $this->huella('politica_creada');
        $this->assertSame('politicas', $creada->recurso);
        $this->assertSame($gestor->id, (int) $creada->causer_id);
        $this->assertSame('Política de accesibilidad', $this->cambios($creada)['nombre']);

        $this->patchJson("/api/v1/panel/politicas/{$slug}", ['nombre' => 'Política de accesibilidad web'])->assertOk();
        $actualizada = $this->huella('politica_actualizada');
        $this->assertSame($creada->recurso_id, $actualizada->recurso_id);
        $this->assertContains('nombre', $this->cambios($actualizada)['campos']);

        $this->deleteJson("/api/v1/panel/politicas/{$slug}")->assertOk();
        $eliminada = $this->huella('politica_eliminada');
        $this->assertSame($creada->recurso_id, $eliminada->recurso_id);
        $this->assertSame('Política de accesibilidad web', $this->cambios($eliminada)['nombre']);

        foreach (AuditLog::query()->where('accion', 'like', 'politica_%')->get() as $fila) {
            $this->assertStringNotContainsString('Texto confidencial', (string) json_encode($fila->cambios));
        }
    }

    public function test_politicas_una_base_que_no_se_puede_eliminar_no_deja_huella(): void
    {
        $this->con('politicas');
        $this->deleteJson('/api/v1/panel/politicas/terminos-y-condiciones-de-uso')->assertUnprocessable();

        $this->sinHuella('politica_eliminada');
    }

    // ── Trámites ────────────────────────────────────────────────────────────────

    public function test_tramites_crear_actualizar_y_eliminar_dejan_huella(): void
    {
        $gestor = $this->con('tramites');

        $uuid = (string) $this->postJson('/api/v1/panel/tramites', ['nombre' => 'Certificado de residencia'])->assertCreated()->json('data.uuid');
        $creado = $this->huella('tramite_creado');
        $this->assertSame('tramites', $creado->recurso);
        $this->assertSame($gestor->id, (int) $creado->causer_id);
        $this->assertSame('Certificado de residencia', $this->cambios($creado)['nombre']);

        $this->patchJson("/api/v1/panel/tramites/{$uuid}", ['nombre' => 'Certificado de residencia 2'])->assertOk();
        $actualizado = $this->huella('tramite_actualizado');
        $this->assertSame($creado->recurso_id, $actualizado->recurso_id);
        $this->assertContains('nombre', $this->cambios($actualizado)['campos']);

        $this->deleteJson("/api/v1/panel/tramites/{$uuid}")->assertNoContent();
        $this->assertSame('Certificado de residencia 2', $this->cambios($this->huella('tramite_eliminado'))['nombre']);
    }

    public function test_tramites_una_peticion_rechazada_no_deja_huella(): void
    {
        $this->con('tramites');
        $this->postJson('/api/v1/panel/tramites', ['nombre' => ''])->assertUnprocessable();

        $this->sinHuella('tramite_creado');
    }

    // ── Entidad ─────────────────────────────────────────────────────────────────

    public function test_entidad_actualizar_deja_huella_con_los_campos_y_no_los_valores(): void
    {
        $gestor = $this->con('entidad');

        $this->patchJson('/api/v1/panel/entidad', ['telefono' => '+57 601 999 0000'])->assertOk();

        $fila = $this->huella('entidad_actualizada');
        $this->assertSame('entidad', $fila->recurso);
        $this->assertSame($gestor->id, (int) $fila->causer_id);
        $this->assertSame(['telefono'], $this->cambios($fila)['campos']);
        $this->assertStringNotContainsString('999 0000', (string) json_encode($fila->cambios));
    }
}
