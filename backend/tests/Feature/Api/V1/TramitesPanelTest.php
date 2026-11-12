<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Tramite;
use App\Models\User;
use App\Support\Tramites\FuenteSuit;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gestión de trámites desde el panel (Fase 4).
 *
 * Lo que se protege: que un trámite pueda existir incompleto (borrador) pero no publicarse
 * sin sus seis atributos y su ficha GOV.CO; que lo corregido a mano no lo pise la ingesta
 * automática; que lo que viene de la fuente oficial no se pueda borrar (volvería en la
 * siguiente ingesta); y que el permiso se compruebe en el servidor.
 */
final class TramitesPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function gestor(): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo('tramites.gestionar');

        return $usuario;
    }

    /** @return array<string, mixed> */
    private function completo(string $nombre = 'Certificado de residencia'): array
    {
        return [
            'nombre' => $nombre,
            'resumen' => 'Constancia de que una persona vive en el Distrito.',
            'url_ficha_gov_co' => 'https://www.gov.co/ficha-tramites-y-servicios/T1',
            'modalidad' => 'presencial',
            'tiene_costo' => 'gratuito',
            'tiempo_solucion_dias' => 3,
            'canal_inicio' => 'propio',
            'consulta_estado' => 'En la ventanilla única',
            'requisitos' => [['tipo' => 'documento', 'descripcion' => 'Cédula de ciudadanía', 'obligatorio' => true]],
        ];
    }

    public function test_sin_sesion_es_401_y_sin_permiso_es_403(): void
    {
        $this->getJson('/api/v1/panel/tramites')->assertUnauthorized();

        $sinPermiso = User::factory()->create();
        $this->actingAs($sinPermiso)->getJson('/api/v1/panel/tramites')->assertForbidden();
        $this->actingAs($sinPermiso)->postJson('/api/v1/panel/tramites', ['nombre' => 'X'])->assertForbidden();
    }

    public function test_el_listado_trae_borradores_y_publicados_con_su_estado_y_origen(): void
    {
        Tramite::factory()->published()->create(['nombre' => 'Uno publicado', 'codigo' => 'T1', 'procedencia_fuente' => FuenteSuit::NOMBRE]);
        Tramite::factory()->create(['nombre' => 'Dos borrador', 'codigo' => 'P0001', 'url_ficha_gov_co' => null]);

        $respuesta = $this->actingAs($this->gestor())->getJson('/api/v1/panel/tramites?ordenar_por=nombre&direccion=asc')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $filas = collect($respuesta->json('data'));
        $this->assertSame('publicado', $filas->firstWhere('codigo', 'T1')['estado']);
        $this->assertSame('fuente', $filas->firstWhere('codigo', 'T1')['origen']);
        $this->assertSame('borrador', $filas->firstWhere('codigo', 'P0001')['estado']);
        $this->assertSame('panel', $filas->firstWhere('codigo', 'P0001')['origen']);
        $this->assertNotEmpty($filas->firstWhere('codigo', 'P0001')['problemas']);
    }

    public function test_buscar_y_filtrar_por_estado_se_hace_en_el_servidor(): void
    {
        Tramite::factory()->published()->create(['nombre' => 'Licencia de construcción', 'codigo' => 'T1']);
        Tramite::factory()->create(['nombre' => 'Licencia de tránsito', 'codigo' => 'T2']);
        Tramite::factory()->create(['nombre' => 'Impuesto predial', 'codigo' => 'T3']);
        $gestor = $this->gestor();

        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites?q=licencia')->assertJsonPath('meta.total', 2);
        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites?q=transito')->assertJsonPath('meta.total', 1);
        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites?estado=publicado')->assertJsonPath('meta.total', 1);
        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites?estado=borrador')->assertJsonPath('meta.total', 2);
        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites?q=T3')->assertJsonPath('meta.total', 1);
        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites?estado=raro')->assertUnprocessable();
    }

    public function test_se_puede_crear_un_borrador_solo_con_el_nombre(): void
    {
        $this->actingAs($this->gestor())->postJson('/api/v1/panel/tramites', ['nombre' => 'Certificado de residencia'])
            ->assertCreated()
            ->assertJsonPath('data.estado', 'borrador')
            ->assertJsonPath('data.codigo', 'P0001')
            ->assertJsonPath('data.slug', 'certificado-de-residencia')
            ->assertJsonPath('data.origen', 'panel')
            ->assertJsonPath('data.publicado_en', null);

        $this->actingAs($this->gestor())->postJson('/api/v1/panel/tramites', ['nombre' => 'Certificado de residencia'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'certificado-de-residencia-2')
            ->assertJsonPath('data.codigo', 'P0002');

        $this->assertDatabaseCount('tramites', 2);
    }

    public function test_publicar_un_incompleto_se_rechaza_y_dice_que_falta(): void
    {
        $respuesta = $this->actingAs($this->gestor())->postJson('/api/v1/panel/tramites', ['nombre' => 'A medias', 'publicado' => true])
            ->assertUnprocessable();

        $texto = json_encode($respuesta->json('errors'), JSON_UNESCAPED_UNICODE);
        foreach (['ficha', 'modalidad', 'costo', 'tiempo', 'canal', 'consulta', 'requisito'] as $palabra) {
            $this->assertStringContainsStringIgnoringCase($palabra, (string) $texto);
        }
        $this->assertDatabaseCount('tramites', 0);
    }

    public function test_un_completo_se_publica_y_el_ciudadano_lo_ve(): void
    {
        $datos = $this->completo() + ['publicado' => true];

        $respuesta = $this->actingAs($this->gestor())->postJson('/api/v1/panel/tramites', $datos)
            ->assertCreated()
            ->assertJsonPath('data.estado', 'publicado');
        $this->assertNotNull($respuesta->json('data.publicado_en'));

        $this->getJson('/api/v1/tramites/certificado-de-residencia')->assertOk()
            ->assertJsonPath('data.nombre', 'Certificado de residencia')
            ->assertJsonPath('data.procedencia.fuente', Tramite::FUENTE_PANEL);
    }

    public function test_despublicar_lo_saca_del_catalogo_sin_borrarlo(): void
    {
        $gestor = $this->gestor();
        $uuid = (string) $this->actingAs($gestor)->postJson('/api/v1/panel/tramites', $this->completo() + ['publicado' => true])->json('data.uuid');

        $this->actingAs($gestor)->patchJson("/api/v1/panel/tramites/{$uuid}", ['publicado' => false])
            ->assertOk()->assertJsonPath('data.estado', 'borrador')->assertJsonPath('data.publicado_en', null);

        $this->getJson('/api/v1/tramites/certificado-de-residencia')->assertNotFound();
        $this->assertDatabaseHas('tramites', ['uuid' => $uuid]);
    }

    public function test_publicar_de_nuevo_conserva_la_fecha_original(): void
    {
        $gestor = $this->gestor();
        $tramite = Tramite::factory()->published()->create(['publicado_en' => now()->subYear()->startOfDay(), 'codigo' => 'P0009']);
        $antes = $tramite->publicado_en?->toIso8601String();

        $this->actingAs($gestor)->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['publicado' => true, 'resumen' => 'Nuevo'])
            ->assertOk()->assertJsonPath('data.publicado_en', $antes);
    }

    public function test_editar_un_tramite_de_la_fuente_lo_marca_como_corregido_para_que_la_ingesta_no_lo_pise(): void
    {
        $tramite = Tramite::factory()->published()->create([
            'codigo' => 'T100',
            'procedencia_fuente' => FuenteSuit::NOMBRE,
            'procedencia_url' => 'https://visor/x',
            'procedencia_obtenido_en' => '2026-10-02',
            'procedencia_origen_por_campo' => ['nombre' => 'fuente', 'resumen' => 'fuente'],
        ]);

        $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['resumen' => 'Corregido por la Entidad'])
            ->assertOk()
            ->assertJsonPath('data.origen', 'panel')
            ->assertJsonPath('data.procedencia.fuente', Tramite::FUENTE_PANEL)
            ->assertJsonPath('data.procedencia.url', 'https://visor/x')
            ->assertJsonPath('data.procedencia.origen_por_campo.resumen', 'entidad')
            ->assertJsonPath('data.procedencia.origen_por_campo.nombre', 'fuente');

        $this->assertNotSame(FuenteSuit::NOMBRE, $tramite->fresh()?->procedencia_fuente);
    }

    public function test_el_slug_y_el_codigo_no_cambian_al_editar(): void
    {
        $tramite = Tramite::factory()->create(['codigo' => 'P0003', 'slug' => 'original']);

        $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['nombre' => 'Otro nombre', 'slug' => 'otro', 'codigo' => 'X'])
            ->assertOk()->assertJsonPath('data.slug', 'original')->assertJsonPath('data.codigo', 'P0003')->assertJsonPath('data.nombre', 'Otro nombre');
    }

    public function test_guarda_todas_las_listas_de_la_ficha(): void
    {
        $tramite = Tramite::factory()->create(['codigo' => 'P0005']);
        $datos = [
            'documentos' => [['nombre' => 'Formulario', 'url' => 'https://x.co/f.pdf', 'formato' => 'PDF']],
            'momentos' => [['orden' => 1, 'titulo' => 'Reunir documentos', 'descripcion' => 'Prepare los soportes', 'requisitos' => [['tipo' => 'documento', 'descripcion' => 'Cédula', 'obligatorio' => true]]]],
            'audiencias' => [['grupo' => 'Ciudadano', 'nombre' => 'Juventud', 'descripcion' => null]],
            'perfiles' => ['Ciudadano'],
            'medios_resultado' => ['Correo electrónico'],
            'puntos_atencion' => [['nombre' => 'Palacio', 'direccion' => 'Calle 14 No. 2-49', 'horario' => 'L-V', 'telefono' => '6054209600', 'latitud' => 11.24, 'longitud' => -74.21]],
            'normativa' => [['tipo' => 'Ley', 'numero' => '1437', 'anio' => 2011, 'articulos' => '1-5', 'url' => 'https://x.co/l']],
            'cuentas' => [['banco' => 'Bancolombia', 'tipo' => 'Ahorros', 'numero' => '123456', 'titular' => 'Distrito']],
            'canales_consulta_estado' => [['canal' => 'web', 'habilitado' => true, 'nombre' => 'Sede', 'url' => 'https://x.co']],
            'seguimiento' => ['telefono' => [['numero' => '6054209600', 'horario' => 'L-V']], 'email' => [['email' => 'a@b.co']], 'web' => [['url' => 'https://x.co']]],
            'costo' => ['tiene_costo' => 'con_costo', 'tipo_valor' => 'fijo', 'valor' => '25000', 'moneda' => 'COP', 'url_pago' => 'https://pagos.co', 'descripcion' => 'Derechos', 'cuentas' => [['entidad' => 'Banco', 'numero_cuenta' => '99']]],
            'tiene_costo' => 'con_costo',
            'resultado' => 'Certificado', 'producto_final' => 'Certificado en PDF', 'observaciones_resultado' => 'Depende del caso',
            'fecha_cualquiera' => false, 'cuando_se_puede_realizar' => 'En enero', 'url_calendario' => 'https://x.co/c',
            'palabras_relacionadas' => 'residencia, domicilio', 'url_manual_tramite_en_linea' => 'https://x.co/m',
            'categoria' => ['slug' => 'vivienda', 'nombre' => 'Vivienda'],
        ];

        $respuesta = $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", $datos)->assertOk();

        foreach (['documentos', 'momentos', 'audiencias', 'perfiles', 'medios_resultado', 'puntos_atencion', 'normativa', 'cuentas', 'canales_consulta_estado', 'seguimiento'] as $clave) {
            $this->assertEquals($datos[$clave], $respuesta->json("data.{$clave}"), $clave);
        }
        $this->assertSame('vivienda', $respuesta->json('data.categoria.slug'));
        $this->assertSame('25000.00', $respuesta->json('data.costo.valor'));
        $this->assertSame('COP', $respuesta->json('data.costo.moneda'));
        $this->assertSame('Derechos', $respuesta->json('data.costo.descripcion'));
        $this->assertFalse($respuesta->json('data.fecha_cualquiera'));
    }

    public function test_vaciar_una_lista_la_deja_vacia(): void
    {
        $tramite = Tramite::factory()->create(['codigo' => 'P0006', 'perfiles' => ['Ciudadano']]);

        $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['perfiles' => []])
            ->assertOk()->assertJsonPath('data.perfiles', []);
    }

    public function test_los_datos_mal_formados_se_rechazan_con_mensajes_utiles(): void
    {
        $tramite = Tramite::factory()->create(['codigo' => 'P0007']);
        $gestor = $this->gestor();
        $ruta = "/api/v1/panel/tramites/{$tramite->uuid}";

        $this->actingAs($gestor)->patchJson($ruta, ['modalidad' => 'volando'])->assertUnprocessable()->assertJsonValidationErrors(['modalidad']);
        $this->actingAs($gestor)->patchJson($ruta, ['url_ficha_gov_co' => 'javascript:alert(1)'])->assertUnprocessable()->assertJsonValidationErrors(['url_ficha_gov_co']);
        $this->actingAs($gestor)->patchJson($ruta, ['tiempo_solucion_dias' => -1])->assertUnprocessable()->assertJsonValidationErrors(['tiempo_solucion_dias']);
        $this->actingAs($gestor)->patchJson($ruta, ['requisitos' => [['tipo' => 'otro', 'descripcion' => 'x']]])->assertUnprocessable()->assertJsonValidationErrors(['requisitos.0.tipo']);
        $this->actingAs($gestor)->patchJson($ruta, ['requisitos' => [['tipo' => 'documento']]])->assertUnprocessable()->assertJsonValidationErrors(['requisitos.0.descripcion']);
        $this->actingAs($gestor)->patchJson($ruta, ['normativa' => [['tipo' => 'Ley', 'url' => 'no es url']]])->assertUnprocessable()->assertJsonValidationErrors(['normativa.0.url']);
        $this->actingAs($gestor)->patchJson($ruta, ['puntos_atencion' => [['nombre' => 'x', 'direccion' => 'y', 'latitud' => 200]]])->assertUnprocessable()->assertJsonValidationErrors(['puntos_atencion.0.latitud']);
        $this->actingAs($gestor)->patchJson($ruta, ['seguimiento' => ['email' => [['email' => 'no']]]])->assertUnprocessable()->assertJsonValidationErrors(['seguimiento.email.0.email']);
        $this->actingAs($gestor)->patchJson($ruta, ['nombre' => str_repeat('a', 201)])->assertUnprocessable()->assertJsonValidationErrors(['nombre']);
    }

    public function test_texto_con_etiquetas_se_guarda_tal_cual_como_texto_y_se_publica_escapado_por_el_sitio(): void
    {
        $tramite = Tramite::factory()->create(['codigo' => 'P0008']);

        $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['resumen' => '<script>alert(1)</script> hola'])
            ->assertOk();

        // Es texto plano: el sitio lo pinta con {{ }} (escapado). Aquí solo se verifica que no se interpreta ni se pierde.
        $this->assertSame('<script>alert(1)</script> hola', $tramite->fresh()?->resumen);
    }

    public function test_un_tramite_creado_en_el_panel_se_puede_eliminar_pero_el_de_la_fuente_no(): void
    {
        $gestor = $this->gestor();
        $propio = Tramite::factory()->create(['codigo' => 'P0010']);
        $deLaFuente = Tramite::factory()->create(['codigo' => 'T200', 'procedencia_fuente' => FuenteSuit::NOMBRE]);

        $this->actingAs($gestor)->deleteJson("/api/v1/panel/tramites/{$propio->uuid}")->assertNoContent();
        $this->assertDatabaseMissing('tramites', ['uuid' => $propio->uuid]);

        $this->actingAs($gestor)->deleteJson("/api/v1/panel/tramites/{$deLaFuente->uuid}")->assertStatus(409);
        $this->assertDatabaseHas('tramites', ['uuid' => $deLaFuente->uuid]);
    }

    public function test_un_identificador_que_no_es_uuid_es_404_no_500(): void
    {
        $gestor = $this->gestor();
        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites/undefined')->assertNotFound();
        $this->actingAs($gestor)->patchJson('/api/v1/panel/tramites/undefined', [])->assertNotFound();
        $this->actingAs($gestor)->deleteJson('/api/v1/panel/tramites/undefined')->assertNotFound();
    }

    public function test_el_indice_de_busqueda_se_actualiza_al_cambiar_el_nombre(): void
    {
        $tramite = Tramite::factory()->published()->create(['codigo' => 'P0011', 'nombre' => 'Antiguo']);

        $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['nombre' => 'Zanahoria'])->assertOk();

        $this->assertStringContainsString('zanahoria', (string) $tramite->fresh()?->busqueda);
    }
}
