<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El carrusel de la portada (banners) administrado desde el panel (Fase 6).
 *
 * Lo que se protege: que una diapositiva no se publique sin imagen ni texto alternativo
 * (Res. 1519/2020, Anexo 2), que el enlace no pueda ser un `javascript:` ni otro dominio
 * disfrazado, que el sitio solo reciba las vigentes y en el orden elegido, y que el permiso
 * se compruebe en el servidor.
 */
final class BannersTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake((string) config('filesystems.default'));
    }

    private function gestor(): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo(CatalogoPermisos::de('banners'));

        return $usuario;
    }

    private function png(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('portada.png', (string) base64_decode(self::PNG, true));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function crear(User $gestor, string $titulo = 'Pague su predial', array $extra = []): array
    {
        $respuesta = $this->actingAs($gestor)->postJson('/api/v1/panel/banners', ['titulo' => $titulo] + $extra)->assertCreated();

        /** @var array<string, mixed> $datos */
        $datos = $respuesta->json('data');

        return $datos;
    }

    private function conImagen(User $gestor, string $uuid, string $alt = 'Fachada de la Alcaldía'): void
    {
        $this->actingAs($gestor)->post("/api/v1/panel/banners/{$uuid}/imagen", ['imagen' => $this->png(), 'alt' => $alt], ['Accept' => 'application/json'])->assertOk();
    }

    public function test_sin_sesion_es_401_y_sin_permiso_es_403(): void
    {
        $this->getJson('/api/v1/panel/banners')->assertUnauthorized();

        $sinPermiso = User::factory()->create();
        $sinPermiso->givePermissionTo(CatalogoPermisos::de('noticias'));
        $this->actingAs($sinPermiso)->getJson('/api/v1/panel/banners')->assertForbidden();
        $this->actingAs($sinPermiso)->postJson('/api/v1/panel/banners', ['titulo' => 'x'])->assertForbidden();
    }

    public function test_se_crea_como_borrador_con_solo_el_titulo_y_cada_nuevo_va_al_final(): void
    {
        $gestor = $this->gestor();

        $primero = $this->crear($gestor, 'Uno');
        $segundo = $this->crear($gestor, 'Dos');

        $this->assertSame('borrador', $primero['estado']);
        $this->assertSame(1, $primero['orden']);
        $this->assertSame(2, $segundo['orden']);
        $this->assertNotEmpty($primero['problemas']);
        $this->assertNotNull(AuditLog::query()->where('accion', 'banner_creado')->first());
    }

    public function test_no_se_publica_sin_imagen_y_el_mensaje_dice_que_falta(): void
    {
        $gestor = $this->gestor();
        $banner = $this->crear($gestor);

        $this->actingAs($gestor)->patchJson("/api/v1/panel/banners/{$banner['uuid']}", ['activo' => true])
            ->assertUnprocessable()
            ->assertJsonPath('errors.banner.0', fn ($m) => str_contains((string) $m, 'imagen'));
    }

    public function test_la_imagen_exige_su_texto_alternativo_y_solo_acepta_imagenes(): void
    {
        $gestor = $this->gestor();
        $banner = $this->crear($gestor);

        $this->actingAs($gestor)->post("/api/v1/panel/banners/{$banner['uuid']}/imagen", ['imagen' => $this->png()], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors(['alt'], 'errors');

        $this->actingAs($gestor)->post("/api/v1/panel/banners/{$banner['uuid']}/imagen", [
            'imagen' => UploadedFile::fake()->createWithContent('x.svg', '<svg onload="alert(1)"/>'), 'alt' => 'x',
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['imagen'], 'errors');
    }

    public function test_con_imagen_y_alt_se_publica_y_el_panel_ve_la_imagen_aunque_sea_borrador(): void
    {
        $gestor = $this->gestor();
        $banner = $this->crear($gestor);
        $this->conImagen($gestor, $banner['uuid']);

        $this->actingAs($gestor)->get("/api/v1/panel/banners/{$banner['uuid']}/imagen")->assertOk();
        $this->get("/api/v1/banners/{$banner['uuid']}/imagen")->assertNotFound();

        $this->actingAs($gestor)->patchJson("/api/v1/panel/banners/{$banner['uuid']}", ['activo' => true])
            ->assertOk()->assertJsonPath('data.estado', 'vigente')->assertJsonPath('data.imagen.alt', 'Fachada de la Alcaldía');

        $this->get("/api/v1/banners/{$banner['uuid']}/imagen")->assertOk();
    }

    public function test_el_enlace_solo_puede_ser_una_ruta_del_sitio_o_https(): void
    {
        $gestor = $this->gestor();

        foreach (['javascript:alert(1)', '//evil.example/x', 'http://inseguro.example', 'data:text/html,hola', 'transparencia'] as $malo) {
            $this->actingAs($gestor)->postJson('/api/v1/panel/banners', ['titulo' => 'X', 'enlace' => $malo])
                ->assertUnprocessable()->assertJsonValidationErrors(['enlace'], 'errors');
        }
        foreach (['/transparencia', 'https://www.gov.co/ficha'] as $bueno) {
            $this->actingAs($gestor)->postJson('/api/v1/panel/banners', ['titulo' => 'Y', 'enlace' => $bueno])->assertCreated();
        }
    }

    public function test_valida_titulo_y_vigencia(): void
    {
        $gestor = $this->gestor();

        $this->actingAs($gestor)->postJson('/api/v1/panel/banners', [])->assertUnprocessable()->assertJsonValidationErrors(['titulo'], 'errors');
        $this->actingAs($gestor)->postJson('/api/v1/panel/banners', ['titulo' => str_repeat('a', 81)])->assertUnprocessable();
        $this->actingAs($gestor)->postJson('/api/v1/panel/banners', [
            'titulo' => 'X', 'vigente_desde' => '2026-12-10T00:00:00-05:00', 'vigente_hasta' => '2026-12-01T00:00:00-05:00',
        ])->assertUnprocessable()->assertJsonValidationErrors(['vigente_hasta'], 'errors');
    }

    public function test_el_estado_se_dice_con_palabras_segun_la_vigencia(): void
    {
        $gestor = $this->gestor();
        $banner = $this->crear($gestor);
        $this->conImagen($gestor, $banner['uuid']);

        $casos = [
            ['activo' => true, 'vigente_desde' => now()->addDays(2)->toIso8601String(), 'vigente_hasta' => null, 'esperado' => 'programado'],
            ['activo' => true, 'vigente_desde' => now()->subDays(5)->toIso8601String(), 'vigente_hasta' => now()->subDay()->toIso8601String(), 'esperado' => 'vencido'],
            ['activo' => true, 'vigente_desde' => null, 'vigente_hasta' => now()->addDay()->toIso8601String(), 'esperado' => 'vigente'],
            ['activo' => false, 'vigente_desde' => null, 'vigente_hasta' => null, 'esperado' => 'borrador'],
        ];
        foreach ($casos as $caso) {
            $esperado = $caso['esperado'];
            unset($caso['esperado']);
            $this->actingAs($gestor)->patchJson("/api/v1/panel/banners/{$banner['uuid']}", $caso)->assertOk()->assertJsonPath('data.estado', $esperado);
        }
    }

    public function test_el_sitio_recibe_solo_las_vigentes_en_el_orden_elegido_y_sin_datos_internos(): void
    {
        $gestor = $this->gestor();
        $a = $this->crear($gestor, 'A');
        $b = $this->crear($gestor, 'B');
        $c = $this->crear($gestor, 'C', ['enlace' => '/transparencia', 'descripcion' => 'Texto de C']);
        $borrador = $this->crear($gestor, 'Borrador');
        $programado = $this->crear($gestor, 'Programado');
        foreach ([$a, $b, $c, $programado] as $banner) {
            $this->conImagen($gestor, $banner['uuid']);
        }
        foreach ([$a, $b, $c] as $banner) {
            $this->actingAs($gestor)->patchJson("/api/v1/panel/banners/{$banner['uuid']}", ['activo' => true])->assertOk();
        }
        $this->actingAs($gestor)->patchJson("/api/v1/panel/banners/{$programado['uuid']}", ['activo' => true, 'vigente_desde' => now()->addWeek()->toIso8601String()])->assertOk();
        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$c['uuid'], $a['uuid'], $b['uuid'], $borrador['uuid'], $programado['uuid']], 'antes' => $this->ordenActual($gestor)])->assertOk();

        $respuesta = $this->getJson('/api/v1/banners')->assertOk();
        /** @var list<array<string, mixed>> $datos */
        $datos = $respuesta->json('data');

        $this->assertSame(['C', 'A', 'B'], array_column($datos, 'titulo'));
        $this->assertSame('/transparencia', $datos[0]['enlace']);
        $this->assertSame('Texto de C', $datos[0]['descripcion']);
        $this->assertSame('Fachada de la Alcaldía', $datos[0]['imagen']['alt']);
        $this->assertStringEndsWith("/api/v1/banners/{$c['uuid']}/imagen", (string) $datos[0]['imagen']['url']);
        $this->assertArrayNotHasKey('activo', $datos[0]);
        $this->assertArrayNotHasKey('problemas', $datos[0]);
        $this->assertArrayNotHasKey('id', $datos[0]);
    }

    public function test_reordenar_exige_la_lista_completa_y_sin_desconocidos(): void
    {
        $gestor = $this->gestor();
        $a = $this->crear($gestor, 'A');
        $b = $this->crear($gestor, 'B');
        $antes = $this->ordenActual($gestor);

        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$a['uuid']], 'antes' => $antes])->assertUnprocessable();
        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$a['uuid'], '00000000-0000-0000-0000-000000000000'], 'antes' => $antes])->assertUnprocessable();
        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$a['uuid'], $a['uuid']], 'antes' => $antes])->assertUnprocessable();

        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$b['uuid'], $a['uuid']], 'antes' => $antes])->assertOk();
        $lista = $this->actingAs($gestor)->getJson('/api/v1/panel/banners')->assertOk()->json('data');
        $this->assertSame(['B', 'A'], array_column($lista, 'titulo'));
    }

    /** Sin `antes` no se sabe qué orden vio quien guarda, y no se puede detectar que otra persona lo cambió. */
    public function test_reordenar_exige_el_orden_que_vio_quien_guarda(): void
    {
        $gestor = $this->gestor();
        $a = $this->crear($gestor, 'A');
        $b = $this->crear($gestor, 'B');

        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$b['uuid'], $a['uuid']]])
            ->assertUnprocessable()->assertJsonValidationErrors(['antes']);
    }

    /**
     * Dos administradores abren la lista [A, B, C]. Uno baja A y guarda [B, A, C]. El otro, con la lista vieja,
     * sube C y envía [A, C, B]: es una lista completa y válida, así que antes se aceptaba y el cambio del
     * primero se perdía en silencio. Ahora el orden que vio quien guarda debe ser el que hay: si no, 409.
     */
    public function test_reordenar_con_una_lista_vieja_responde_409_y_no_pisa_el_cambio_de_otra_persona(): void
    {
        $gestor = $this->gestor();
        $a = $this->crear($gestor, 'A');
        $b = $this->crear($gestor, 'B');
        $c = $this->crear($gestor, 'C');
        $vieja = [$a['uuid'], $b['uuid'], $c['uuid']];

        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$b['uuid'], $a['uuid'], $c['uuid']], 'antes' => $vieja])->assertOk();

        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$a['uuid'], $c['uuid'], $b['uuid']], 'antes' => $vieja])
            ->assertStatus(409);

        $this->assertSame(['B', 'A', 'C'], array_column($this->actingAs($gestor)->getJson('/api/v1/panel/banners')->json('data'), 'titulo'));
    }

    /** Un cliente que envía el orden actual pero una lista final incompleta se equivoca él: 422, no 409. */
    public function test_reordenar_con_lista_final_incompleta_y_orden_vigente_es_422(): void
    {
        $gestor = $this->gestor();
        $a = $this->crear($gestor, 'A');
        $this->crear($gestor, 'B');

        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$a['uuid']], 'antes' => $this->ordenActual($gestor)])
            ->assertUnprocessable();
    }

    /** Un uuid en mayúsculas es el mismo uuid (RFC 4122); no debe rechazarse ni dejar de coincidir. */
    public function test_reordenar_acepta_uuid_en_mayusculas(): void
    {
        $gestor = $this->gestor();
        $a = $this->crear($gestor, 'A');
        $b = $this->crear($gestor, 'B');
        $antes = array_map('strtoupper', $this->ordenActual($gestor));

        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [strtoupper($b['uuid']), strtoupper($a['uuid'])], 'antes' => $antes])->assertOk();

        $this->assertSame(['B', 'A'], array_column($this->actingAs($gestor)->getJson('/api/v1/panel/banners')->json('data'), 'titulo'));
    }

    /**
     * Dos altas a la vez leen el mismo `max(orden)` y quedan con el mismo número. Se serializan con un
     * bloqueo de aviso de PostgreSQL tomado antes de leer el máximo.
     */
    public function test_crear_toma_el_bloqueo_de_orden_antes_de_leer_el_maximo(): void
    {
        $gestor = $this->gestor();
        /** @var list<string> $sentencias */
        $sentencias = [];
        DB::listen(function ($consulta) use (&$sentencias): void {
            $sentencias[] = strtolower($consulta->sql);
        });

        $this->crear($gestor, 'A');

        $this->assertOrdenDeSentencias($sentencias, 'pg_advisory_xact_lock', 'max("orden")');
    }

    /** Reordenar y crear comparten el bloqueo: si no, un alta a mitad de un reordenamiento repite números. */
    public function test_reordenar_toma_el_mismo_bloqueo_antes_de_bloquear_las_filas(): void
    {
        $gestor = $this->gestor();
        $a = $this->crear($gestor, 'A');
        $antes = $this->ordenActual($gestor);
        /** @var list<string> $sentencias */
        $sentencias = [];
        DB::listen(function ($consulta) use (&$sentencias): void {
            $sentencias[] = strtolower($consulta->sql);
        });

        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$a['uuid']], 'antes' => $antes])->assertOk();

        $this->assertOrdenDeSentencias($sentencias, 'pg_advisory_xact_lock', 'for update');
    }

    /** @param list<string> $sentencias */
    private function assertOrdenDeSentencias(array $sentencias, string $primero, string $despues): void
    {
        $i = $j = null;
        foreach ($sentencias as $n => $sql) {
            if ($i === null && str_contains($sql, $primero)) {
                $i = $n;
            }
            if ($j === null && str_contains($sql, $despues)) {
                $j = $n;
            }
        }
        $this->assertNotNull($i, "No se ejecutó {$primero}.");
        $this->assertNotNull($j, "No se ejecutó {$despues}.");
        $this->assertLessThan($j, $i, "{$primero} debe ir antes que {$despues}.");
    }

    /** @return list<string> */
    private function ordenActual(User $gestor): array
    {
        /** @var list<array{uuid: string}> $lista */
        $lista = $this->actingAs($gestor)->getJson('/api/v1/panel/banners')->assertOk()->json('data');

        return array_column($lista, 'uuid');
    }

    /**
     * Dos personas que reordenan a la vez, una X→Y y otra Y→X, se bloquean entre sí si cada una actualiza
     * las filas en el orden que le llega: PostgreSQL aborta una con `deadlock detected` y la API responde 500
     * (reproducido con dos sesiones reales). Si antes de actualizar se bloquean todas las filas en un orden fijo,
     * la segunda espera a la primera y no hay ciclo.
     */
    public function test_reordenar_bloquea_las_filas_en_un_orden_fijo_antes_de_actualizar(): void
    {
        $gestor = $this->gestor();
        $a = $this->crear($gestor, 'A');
        $b = $this->crear($gestor, 'B');
        $c = $this->crear($gestor, 'C');

        $antesDelOrden = $this->ordenActual($gestor);
        /** @var list<string> $sentencias */
        $sentencias = [];
        DB::listen(function ($consulta) use (&$sentencias): void {
            $sentencias[] = strtolower($consulta->sql);
        });

        $this->actingAs($gestor)->putJson('/api/v1/panel/banners/orden', ['uuids' => [$c['uuid'], $b['uuid'], $a['uuid']], 'antes' => $antesDelOrden])->assertOk();

        $bloqueo = null;
        $primeraActualizacion = null;
        foreach ($sentencias as $i => $sql) {
            if ($bloqueo === null && str_contains($sql, 'from "banners"') && str_contains($sql, 'for update')) {
                $bloqueo = $i;
                $this->assertStringContainsString('order by "id"', $sql, 'El bloqueo debe pedir las filas por id, no en el orden recibido.');
            }
            if ($primeraActualizacion === null && str_starts_with($sql, 'update "banners"')) {
                $primeraActualizacion = $i;
            }
        }
        $this->assertNotNull($bloqueo, 'No se bloquearon las filas de banners con FOR UPDATE.');
        $this->assertNotNull($primeraActualizacion);
        $this->assertLessThan($primeraActualizacion, $bloqueo, 'El bloqueo debe ir antes de la primera actualización.');
    }

    /**
     * Entre la validación de la petición y el bloqueo otra persona puede borrar o crear una diapositiva.
     * Con el bloqueo tomado se vuelve a comparar con el orden que vio quien guarda: si cambió, 409 y no se toca ningún orden.
     */
    public function test_si_la_lista_cambia_mientras_se_ordena_responde_409_y_no_cambia_nada(): void
    {
        $gestor = $this->gestor();
        $a = $this->crear($gestor, 'A');
        $b = $this->crear($gestor, 'B');
        $c = $this->crear($gestor, 'C');

        $antesDelOrden = $this->ordenActual($gestor);
        $borrada = false;
        DB::beforeExecuting(function (string $sql) use (&$borrada, $c): void {
            if (! $borrada && str_contains(strtolower($sql), 'for update') && str_contains(strtolower($sql), 'from "banners"')) {
                $borrada = true;
                DB::table('banners')->where('uuid', $c['uuid'])->update(['deleted_at' => now()]);
            }
        });

        $this->actingAs($gestor)
            ->putJson('/api/v1/panel/banners/orden', ['uuids' => [$c['uuid'], $b['uuid'], $a['uuid']], 'antes' => $antesDelOrden])
            ->assertStatus(409);

        $this->assertTrue($borrada, 'La prueba no llegó a simular el borrado concurrente.');
        $ordenes = DB::table('banners')->whereIn('uuid', [$a['uuid'], $b['uuid']])->orderBy('uuid')->pluck('orden', 'uuid')->all();
        $this->assertSame(1, (int) $ordenes[$a['uuid']]);
        $this->assertSame(2, (int) $ordenes[$b['uuid']]);
    }

    public function test_cambiar_solo_el_alt_no_pide_subir_otra_vez_la_imagen(): void
    {
        $gestor = $this->gestor();
        $banner = $this->crear($gestor);
        $this->conImagen($gestor, $banner['uuid']);

        $this->actingAs($gestor)->patchJson("/api/v1/panel/banners/{$banner['uuid']}", ['imagen_alt' => 'Nueva descripción'])
            ->assertOk()->assertJsonPath('data.imagen.alt', 'Nueva descripción');
    }

    public function test_eliminar_lo_retira_del_listado_y_del_sitio_y_deja_huella(): void
    {
        $gestor = $this->gestor();
        $banner = $this->crear($gestor);
        $this->conImagen($gestor, $banner['uuid']);
        $this->actingAs($gestor)->patchJson("/api/v1/panel/banners/{$banner['uuid']}", ['activo' => true])->assertOk();
        $this->assertCount(1, $this->getJson('/api/v1/banners')->json('data'));

        $this->actingAs($gestor)->deleteJson("/api/v1/panel/banners/{$banner['uuid']}")->assertOk();

        $this->assertCount(0, $this->getJson('/api/v1/banners')->json('data'));
        $this->assertCount(0, $this->actingAs($gestor)->getJson('/api/v1/panel/banners')->json('data'));
        $this->actingAs($gestor)->getJson("/api/v1/panel/banners/{$banner['uuid']}")->assertNotFound();
        $this->assertNotNull(AuditLog::query()->where('accion', 'banner_eliminado')->first());
    }

    public function test_la_lectura_publica_es_valida_sin_banners(): void
    {
        $this->getJson('/api/v1/banners')->assertOk()->assertJsonPath('data', []);
    }
}
