<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Tramite;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\TramiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Gestión de trámites, OPA y consultas desde el panel.
 *
 * Lo que se protege: que un elemento pueda existir incompleto (borrador) pero no publicarse sin su
 * contenido propio y, para trámites y OPA, su ficha de GOV.CO; que todo elemento sea editable y
 * borrable (papelera); que la ficha viaje y vuelva en forma relacional sin perderse; que se puedan
 * crear OPA y consultas; y que el permiso se compruebe en el servidor.
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

    public function test_lista_las_dependencias_para_el_selector(): void
    {
        $respuesta = $this->actingAs($this->gestor())->getJson('/api/v1/panel/dependencias');
        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['id', 'nombre']]]);
        $this->assertNotEmpty($respuesta->json('data'));

        // Sin el permiso de gestión no se pueden ver.
        $this->actingAs(User::factory()->create())->getJson('/api/v1/panel/dependencias')->assertForbidden();
    }

    public function test_el_listado_trae_borradores_y_publicados_con_su_estado(): void
    {
        Tramite::factory()->published()->create(['nombre' => 'Uno publicado', 'codigo' => 'T1']);
        Tramite::factory()->create(['nombre' => 'Dos borrador', 'codigo' => 'T2', 'url_ficha_gov_co' => null]);

        $respuesta = $this->actingAs($this->gestor())->getJson('/api/v1/panel/tramites?ordenar_por=nombre&direccion=asc')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        /** @var list<array<string, mixed>> $datos */
        $datos = $respuesta->json('data');
        $filas = array_column($datos, null, 'codigo');
        $this->assertSame('publicado', $filas['T1']['estado']);
        $this->assertSame('borrador', $filas['T2']['estado']);
        $this->assertNotEmpty($filas['T2']['problemas']);
    }

    public function test_buscar_y_filtrar_en_el_servidor(): void
    {
        Tramite::factory()->published()->create(['nombre' => 'Licencia de construcción', 'codigo' => 'T1']);
        Tramite::factory()->create(['nombre' => 'Licencia de tránsito', 'codigo' => 'T2']);
        Tramite::factory()->opa()->create(['nombre' => 'OPA de ejemplo']);
        $gestor = $this->gestor();

        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites?q=licencia')->assertJsonPath('meta.total', 2);
        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites?estado=publicado')->assertJsonPath('meta.total', 1);
        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites?type=opa')->assertJsonPath('meta.total', 1);
        $this->actingAs($gestor)->getJson('/api/v1/panel/tramites?estado=raro')->assertUnprocessable();
    }

    public function test_se_puede_crear_un_borrador_solo_con_el_nombre(): void
    {
        $this->actingAs($this->gestor())->postJson('/api/v1/panel/tramites', ['nombre' => 'Certificado de residencia'])
            ->assertCreated()
            ->assertJsonPath('data.estado', 'borrador')
            ->assertJsonPath('data.type', 'tramites')
            ->assertJsonPath('data.codigo', 'T0001')
            ->assertJsonPath('data.slug', 'certificado-de-residencia')
            ->assertJsonPath('data.publicado_en', null);

        $this->actingAs($this->gestor())->postJson('/api/v1/panel/tramites', ['nombre' => 'Certificado de residencia'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'certificado-de-residencia-2')
            ->assertJsonPath('data.codigo', 'T0002');

        $this->assertDatabaseCount('tramites', 2);
    }

    public function test_se_puede_crear_una_opa_y_una_consulta(): void
    {
        $gestor = $this->gestor();

        $this->actingAs($gestor)->postJson('/api/v1/panel/tramites', ['nombre' => 'OPA de ejemplo', 'type' => 'opa'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'opa')
            ->assertJsonPath('data.codigo', 'O0001');

        $this->actingAs($gestor)->postJson('/api/v1/panel/tramites', ['nombre' => 'Consulta de ejemplo', 'type' => 'consultas'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'consultas')
            ->assertJsonPath('data.codigo', 'C0001');
    }

    public function test_publicar_un_incompleto_se_rechaza_y_dice_que_falta(): void
    {
        $respuesta = $this->actingAs($this->gestor())->postJson('/api/v1/panel/tramites', ['nombre' => 'A medias', 'publicado' => true])
            ->assertUnprocessable();

        $texto = (string) json_encode($respuesta->json('errors'), JSON_UNESCAPED_UNICODE);
        foreach (['ficha', 'modalidad', 'costo', 'tiempo', 'canal', 'consulta', 'requisito'] as $palabra) {
            $this->assertStringContainsStringIgnoringCase($palabra, $texto);
        }
        $this->assertDatabaseCount('tramites', 0);
    }

    public function test_una_consulta_se_publica_sin_ficha_gov_co(): void
    {
        $datos = [
            'nombre' => 'Consulta de estado de cuenta',
            'type' => 'consultas',
            'resumen' => 'Permite consultar el estado de cuenta.',
            'modalidad' => 'en_linea',
            'tiene_costo' => 'gratuito',
            'tiempo_solucion_dias' => 0,
            'canal_inicio' => 'propio',
            'consulta_estado' => 'En línea',
            'requisitos' => [['tipo' => 'documento', 'descripcion' => 'Número de predio', 'obligatorio' => true]],
            'publicado' => true,
        ];

        $this->actingAs($this->gestor())->postJson('/api/v1/panel/tramites', $datos)
            ->assertCreated()
            ->assertJsonPath('data.estado', 'publicado');
    }

    public function test_un_completo_se_publica_y_el_ciudadano_lo_ve(): void
    {
        $datos = $this->completo() + ['publicado' => true];

        $respuesta = $this->actingAs($this->gestor())->postJson('/api/v1/panel/tramites', $datos)
            ->assertCreated()
            ->assertJsonPath('data.estado', 'publicado');
        $this->assertNotNull($respuesta->json('data.publicado_en'));

        $this->getJson('/api/v1/tramites/certificado-de-residencia')->assertOk()
            ->assertJsonPath('data.nombre', 'Certificado de residencia');
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
        $tramite = Tramite::factory()->published()->create(['publicado_en' => now()->subYear()->startOfDay(), 'codigo' => 'T9']);
        $antes = $tramite->publicado_en?->toIso8601String();

        $this->actingAs($gestor)->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['publicado' => true, 'resumen' => 'Nuevo'])
            ->assertOk()->assertJsonPath('data.publicado_en', $antes);
    }

    public function test_el_slug_y_el_codigo_no_cambian_al_editar(): void
    {
        $tramite = Tramite::factory()->create(['codigo' => 'T0003', 'slug' => 'original']);

        $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['nombre' => 'Otro nombre', 'slug' => 'otro', 'codigo' => 'X'])
            ->assertOk()->assertJsonPath('data.slug', 'original')->assertJsonPath('data.codigo', 'T0003')->assertJsonPath('data.nombre', 'Otro nombre');
    }

    public function test_guarda_y_devuelve_la_ficha_relacional(): void
    {
        $tramite = Tramite::factory()->create(['codigo' => 'T0005']);
        $datos = [
            'pasos' => [['orden' => 1, 'titulo' => 'Reunir documentos', 'descripcion' => 'Prepare los soportes']],
            'requisitos' => [['tipo' => 'documento', 'descripcion' => 'Cédula', 'obligatorio' => true, 'orden' => 1, 'paso' => 1]],
            'documentos' => [['nombre' => 'Formulario', 'url' => 'https://x.co/f.pdf', 'formato' => 'PDF']],
            'puntos_atencion' => [['nombre' => 'Palacio', 'direccion' => 'Calle 14 No. 2-49', 'horario' => 'L-V', 'telefono' => '6054209600', 'latitud' => 11.24, 'longitud' => -74.21]],
            'normativa' => [['tipo' => 'Ley', 'numero' => '1437', 'anio' => 2011, 'articulos' => '1-5', 'url' => 'https://x.co/l']],
            'canales_consulta_estado' => [['canal' => 'web', 'habilitado' => true, 'nombre' => 'Sede', 'url' => 'https://x.co']],
            'solicitantes' => ['ciudadano', 'organizaciones'],
            'medios_resultado' => ['Correo electrónico'],
            'costo' => ['tiene_costo' => 'con_costo', 'tipo_valor' => 'fijo', 'valor' => '25000', 'moneda' => 'COP', 'url_pago' => 'https://pagos.co', 'descripcion' => 'Derechos', 'cuentas' => [['banco' => 'Bancolombia', 'tipo' => 'Ahorros', 'numero' => '99', 'titular' => 'Distrito']]],
            'tiene_costo' => 'con_costo',
            'resultado' => 'Certificado', 'observaciones_resultado' => 'Depende del caso',
            'categoria' => ['slug' => 'vivienda', 'nombre' => 'Vivienda'],
        ];

        $r = $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", $datos)->assertOk();

        $r->assertJsonPath('data.pasos.0.titulo', 'Reunir documentos')
            ->assertJsonPath('data.requisitos.0.descripcion', 'Cédula')
            ->assertJsonPath('data.requisitos.0.paso', 1)
            ->assertJsonPath('data.documentos.0.nombre', 'Formulario')
            ->assertJsonPath('data.puntos_atencion.0.nombre', 'Palacio')
            ->assertJsonPath('data.normativa.0.numero', '1437')
            ->assertJsonPath('data.canales_consulta_estado.0.canal', 'web')
            ->assertJsonPath('data.solicitantes', ['ciudadano', 'organizaciones'])
            ->assertJsonPath('data.medios_resultado', ['Correo electrónico'])
            ->assertJsonPath('data.costo.valor', '25000.00')
            ->assertJsonPath('data.costo.cuentas.0.banco', 'Bancolombia')
            ->assertJsonPath('data.categoria.slug', 'vivienda');
    }

    public function test_vaciar_una_lista_la_deja_vacia(): void
    {
        $tramite = Tramite::factory()->create(['codigo' => 'T0006']);
        $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", [
            'documentos' => [['nombre' => 'Uno']],
        ])->assertOk()->assertJsonPath('data.documentos.0.nombre', 'Uno');

        $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['documentos' => []])
            ->assertOk()->assertJsonPath('data.documentos', []);
    }

    public function test_los_datos_mal_formados_se_rechazan_con_mensajes_utiles(): void
    {
        $tramite = Tramite::factory()->create(['codigo' => 'T0007']);
        $gestor = $this->gestor();
        $ruta = "/api/v1/panel/tramites/{$tramite->uuid}";

        $this->actingAs($gestor)->patchJson($ruta, ['modalidad' => 'volando'])->assertUnprocessable()->assertJsonValidationErrors(['modalidad']);
        $this->actingAs($gestor)->patchJson($ruta, ['url_ficha_gov_co' => 'javascript:alert(1)'])->assertUnprocessable()->assertJsonValidationErrors(['url_ficha_gov_co']);
        $this->actingAs($gestor)->patchJson($ruta, ['tiempo_solucion_dias' => -1])->assertUnprocessable()->assertJsonValidationErrors(['tiempo_solucion_dias']);
        $this->actingAs($gestor)->patchJson($ruta, ['requisitos' => [['tipo' => 'otro', 'descripcion' => 'x']]])->assertUnprocessable()->assertJsonValidationErrors(['requisitos.0.tipo']);
        $this->actingAs($gestor)->patchJson($ruta, ['normativa' => [['tipo' => 'Ley', 'url' => 'no es url']]])->assertUnprocessable()->assertJsonValidationErrors(['normativa.0.url']);
        $this->actingAs($gestor)->patchJson($ruta, ['puntos_atencion' => [['nombre' => 'x', 'direccion' => 'y', 'latitud' => 200]]])->assertUnprocessable()->assertJsonValidationErrors(['puntos_atencion.0.latitud']);
        $this->actingAs($gestor)->patchJson($ruta, ['nombre' => str_repeat('a', 201)])->assertUnprocessable()->assertJsonValidationErrors(['nombre']);
    }

    public function test_texto_con_etiquetas_se_guarda_tal_cual(): void
    {
        $tramite = Tramite::factory()->create(['codigo' => 'T0008']);

        $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['resumen' => '<script>alert(1)</script> hola'])
            ->assertOk();

        $this->assertSame('<script>alert(1)</script> hola', $tramite->fresh()?->resumen);
    }

    public function test_todo_elemento_se_puede_borrar(): void
    {
        $gestor = $this->gestor();
        $propio = Tramite::factory()->create(['codigo' => 'T0010']);
        $otro = Tramite::factory()->published()->create(['codigo' => 'T200']);

        $this->actingAs($gestor)->deleteJson("/api/v1/panel/tramites/{$propio->uuid}")->assertNoContent();
        $this->actingAs($gestor)->deleteJson("/api/v1/panel/tramites/{$otro->uuid}")->assertNoContent();

        // A la papelera (soft delete): la fila sigue existiendo con su fecha de borrado.
        $this->assertSoftDeleted('tramites', ['uuid' => $propio->uuid]);
        $this->assertSoftDeleted('tramites', ['uuid' => $otro->uuid]);
        $this->getJson('/api/v1/tramites/'.$otro->slug)->assertNotFound();
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
        $tramite = Tramite::factory()->published()->create(['codigo' => 'T0011', 'nombre' => 'Antiguo']);

        $this->actingAs($this->gestor())->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['nombre' => 'Zanahoria'])->assertOk();

        $this->assertStringContainsString('zanahoria', (string) $tramite->fresh()?->busqueda);
    }

    /**
     * Abrir un trámite real del catálogo cargado y guardarlo sin tocarlo no puede perder ni
     * reordenar la ficha: el GET y el POST usan la misma forma relacional.
     */
    public function test_guardar_sin_cambios_un_tramite_del_catalogo_no_pierde_nada(): void
    {
        $this->seed(TramiteSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        $gestor = $this->gestor();
        $campos = ['resumen', 'url_ficha_gov_co', 'modalidad', 'tiene_costo', 'tiempo_solucion_dias', 'canal_inicio',
            'url_inicio', 'consulta_estado', 'resultado', 'medios_resultado', 'costo', 'categoria',
            'pasos', 'requisitos', 'documentos', 'puntos_atencion', 'normativa', 'canales_consulta_estado', 'solicitantes'];

        $revisados = 0;
        foreach (Tramite::query()->take(30)->get() as $tramite) {
            $antes = $this->actingAs($gestor)->getJson("/api/v1/panel/tramites/{$tramite->uuid}")->assertOk()->json('data');
            $envio = array_intersect_key($antes, array_flip($campos));
            $envio['nombre'] = $antes['nombre'];

            $despues = $this->actingAs($gestor)->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", $envio)
                ->assertOk()->json('data');

            foreach (['requisitos', 'pasos', 'documentos', 'puntos_atencion', 'normativa', 'canales_consulta_estado', 'solicitantes'] as $campo) {
                $this->assertSame(
                    self::canonico($antes[$campo]),
                    self::canonico($despues[$campo]),
                    "{$tramite->codigo}: se alteró «{$campo}» al guardar sin cambios.",
                );
            }
            $revisados++;
        }

        $this->assertGreaterThan(20, $revisados);
    }

    /** Orden de claves, espacios de borde (incluido el no-separable) y «vacío» no son información. */
    private static function canonico(mixed $valor): mixed
    {
        if (! is_array($valor)) {
            $valor = is_string($valor) ? (string) preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $valor) : $valor;

            return $valor === '' ? null : $valor;
        }
        $limpio = array_map(self::canonico(...), $valor);
        if (! array_is_list($limpio)) {
            ksort($limpio);
        }

        return $limpio;
    }
}
