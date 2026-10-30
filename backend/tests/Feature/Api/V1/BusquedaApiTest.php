<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Politica;
use App\Models\Tramite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * La API de búsqueda de la sede (T-22, entrega 2): AC-22.2, 22.4, 22.5, 22.6, 22.7 y 22.9.
 *
 * Estas pruebas fijan la **forma** de las respuestas y las reglas que el contrato
 * promete al ciudadano: qué se encuentra, qué no, cómo se pagina y qué se niega.
 * La tolerancia a erratas, medida sobre el catálogo real, la prueba el banco
 * (`BancoDeErratasTest`).
 */
final class BusquedaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_sugerencias_traen_los_campos_del_contrato_y_se_ordenan_por_pertinencia(): void
    {
        $this->publicar(['nombre' => 'Licencia de exhumación', 'slug' => 'licencia-de-exhumacion']);
        $this->publicar(['nombre' => 'Renovación de licencia de construcción', 'slug' => 'renovacion-de-licencia-de-construccion']);

        $respuesta = $this->getJson('/api/v1/busqueda/sugerencias?q=licencia');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('errors', null);

        /** @var list<array<string, mixed>> $datos */
        $datos = $respuesta->json('data');

        $this->assertCount(2, $datos);
        // El que empieza por el término va primero (premio de prefijo).
        $this->assertSame('Licencia de exhumación', $datos[0]['titulo']);
        $this->assertSame(
            ['id', 'type', 'titulo', 'categoria', 'tipo_recurso', 'url', 'score'],
            array_keys($datos[0]),
        );
        $this->assertSame('sugerencias-busqueda', $datos[0]['type']);
        $this->assertSame('Trámite', $datos[0]['categoria']);
        $this->assertSame('tramite', $datos[0]['tipo_recurso']);
        $this->assertSame('/tramites/licencia-de-exhumacion', $datos[0]['url']);
        $this->assertIsFloat($datos[0]['score']);
        $this->assertGreaterThanOrEqual($datos[1]['score'], $datos[0]['score']);
    }

    public function test_una_errata_y_la_falta_de_tildes_siguen_encontrando_el_tramite(): void
    {
        $this->publicar(['nombre' => 'Licencia de exhumación', 'slug' => 'licencia-de-exhumacion']);

        foreach (['licensia', 'LICENCIA DE EXHUMACION', 'exumacion'] as $escrito) {
            $this->getJson('/api/v1/busqueda/sugerencias?q='.rawurlencode($escrito))
                ->assertOk()
                ->assertJsonPath('data.0.titulo', 'Licencia de exhumación');
        }
    }

    public function test_las_sugerencias_no_pasan_de_diez_y_respetan_limite(): void
    {
        foreach (range(1, 14) as $n) {
            $this->publicar(['nombre' => sprintf('Certificado de prueba %02d', $n), 'slug' => 'certificado-'.$n]);
        }

        $this->getJson('/api/v1/busqueda/sugerencias?q=certificado')
            ->assertOk()
            ->assertJsonCount(10, 'data');

        $this->getJson('/api/v1/busqueda/sugerencias?q=certificado&limite=3')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_la_validacion_de_las_sugerencias_responde_422_en_espanol(): void
    {
        $this->getJson('/api/v1/busqueda/sugerencias')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.q.0', 'Escriba qué desea buscar.');

        $this->getJson('/api/v1/busqueda/sugerencias?q=ab')
            ->assertStatus(422)
            ->assertJsonPath('errors.q.0', 'Escriba al menos 3 caracteres para buscar.');

        $this->getJson('/api/v1/busqueda/sugerencias?q='.str_repeat('a', 121))
            ->assertStatus(422)
            ->assertJsonPath('errors.q.0', 'El texto de búsqueda no puede tener más de 120 caracteres.');

        $this->getJson('/api/v1/busqueda/sugerencias?q=licencia&limite=11')
            ->assertStatus(422)
            ->assertJsonPath('errors.limite.0', 'El campo limite no puede ser mayor que 10.');

        $this->getJson('/api/v1/busqueda/sugerencias?q=licencia&limite=0')
            ->assertStatus(422)
            ->assertJsonPath('errors.limite.0', 'El campo limite no puede ser menor que 1.');
    }

    public function test_tres_caracteres_exactos_son_validos(): void
    {
        $this->getJson('/api/v1/busqueda/sugerencias?q=abc')->assertOk();
        $this->getJson('/api/v1/busqueda/sugerencias?q='.str_repeat('a', 120))->assertOk();
    }

    public function test_un_texto_que_queda_vacio_al_normalizar_no_devuelve_el_catalogo(): void
    {
        // «___» tiene tres caracteres, pasa la validación y queda vacío: es un
        // comodín de LIKE disfrazado de búsqueda.
        $this->publicar();

        $this->getJson('/api/v1/busqueda/sugerencias?q=___')
            ->assertOk()
            ->assertExactJson(['success' => true, 'message' => null, 'data' => [], 'errors' => null]);

        $this->getJson('/api/v1/buscar?q=%25%25%25')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    public function test_un_tramite_sin_ficha_en_gov_co_no_aparece(): void
    {
        $this->publicar(['nombre' => 'Licencia con ficha', 'slug' => 'con-ficha']);
        $this->publicar(['nombre' => 'Licencia sin ficha', 'slug' => 'sin-ficha', 'url_ficha_gov_co' => null]);

        $this->getJson('/api/v1/busqueda/sugerencias?q=licencia')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.url', '/tramites/con-ficha');

        $this->getJson('/api/v1/buscar?q=licencia')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_las_politicas_tambien_se_encuentran_y_su_enlace_es_el_de_su_pagina(): void
    {
        Politica::query()->create([
            'slug' => 'uso-de-cookies',
            'nombre' => 'Uso de cookies',
            'vigente_desde' => '2026-03-01',
            'secciones' => [
                ['id' => 'que-son', 'titulo' => 'Qué son', 'parrafos' => ['Pequeños archivos que el sitio guarda en su navegador.']],
            ],
        ]);

        $respuesta = $this->getJson('/api/v1/buscar?q=cookies');

        $respuesta->assertOk()
            ->assertJsonPath('data.0.tipo_recurso', 'politica')
            ->assertJsonPath('data.0.categoria', 'Política')
            ->assertJsonPath('data.0.url', '/politicas/uso-de-cookies')
            ->assertJsonPath('data.0.fecha', '2026-03-01')
            ->assertJsonPath('data.0.extracto', 'Pequeños archivos que el sitio guarda en su navegador.');
    }

    public function test_los_resultados_traen_los_diez_campos_y_autor_y_miniatura_son_nulos(): void
    {
        $this->publicar([
            'nombre' => 'Certificado de residencia',
            'slug' => 'certificado-de-residencia',
            'resumen' => 'Constancia de domicilio.',
            'publicado_en' => Carbon::parse('2026-09-15 10:00:00'),
        ]);

        $respuesta = $this->getJson('/api/v1/buscar?q=residencia');

        $respuesta->assertOk()
            ->assertJsonPath('data.0.type', 'resultados-busqueda')
            ->assertJsonPath('data.0.extracto', 'Constancia de domicilio.')
            ->assertJsonPath('data.0.fecha', '2026-09-15')
            ->assertJsonPath('data.0.autor', null)
            ->assertJsonPath('data.0.miniatura', null);

        /** @var array<string, mixed> $primero */
        $primero = $respuesta->json('data.0');

        $this->assertSame(
            ['id', 'type', 'titulo', 'categoria', 'tipo_recurso', 'extracto', 'fecha', 'autor', 'miniatura', 'url', 'score'],
            array_keys($primero),
        );
        $this->assertArrayHasKey('autor', $primero);
        $this->assertArrayHasKey('miniatura', $primero);
    }

    public function test_el_extracto_se_recorta_sin_partir_palabras(): void
    {
        $this->publicar([
            'nombre' => 'Certificado largo',
            'slug' => 'certificado-largo',
            'resumen' => str_repeat('constancia ', 40),
        ]);

        $extracto = (string) $this->getJson('/api/v1/buscar?q=certificado')->json('data.0.extracto');

        $this->assertLessThanOrEqual(200, mb_strlen($extracto));
        $this->assertStringEndsWith('…', $extracto);
        $this->assertStringEndsWith('constancia…', $extracto);
    }

    public function test_el_filtro_tipo_acota_la_busqueda(): void
    {
        $this->publicar(['nombre' => 'Datos personales de contribuyentes', 'slug' => 'datos-contribuyentes']);
        Politica::query()->create([
            'slug' => 'proteccion-y-tratamiento-de-datos-personales',
            'nombre' => 'Protección y tratamiento de datos personales',
            'secciones' => [],
        ]);

        $this->getJson('/api/v1/buscar?q=datos%20personales')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/buscar?q=datos%20personales&tipo=politica')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.tipo_recurso', 'politica');
        $this->getJson('/api/v1/buscar?q=datos%20personales&tipo=tramite')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.tipo_recurso', 'tramite');
        // Un `tipo` vacío no es un filtro.
        $this->getJson('/api/v1/buscar?q=datos%20personales&tipo=')->assertOk()->assertJsonPath('meta.total', 2);
    }

    public function test_un_tipo_desconocido_o_una_paginacion_invalida_responden_422(): void
    {
        $this->getJson('/api/v1/buscar?q=licencia&tipo=noticia')
            ->assertStatus(422)
            ->assertJsonPath('errors.tipo.0', 'El tipo de contenido debe ser tramite o politica.');

        $this->getJson('/api/v1/buscar?q=licencia&per_page=101')
            ->assertStatus(422)
            ->assertJsonPath('errors.per_page.0', 'El campo per_page no puede ser mayor que 100.');

        $this->getJson('/api/v1/buscar?q=licencia&page=0')
            ->assertStatus(422)
            ->assertJsonPath('errors.page.0', 'La página empieza en 1.');

        $this->getJson('/api/v1/buscar')
            ->assertStatus(422)
            ->assertJsonPath('errors.q.0', 'Escriba qué desea buscar.');
    }

    public function test_la_paginacion_no_repite_ni_pierde_resultados_y_conserva_la_consulta_en_los_enlaces(): void
    {
        foreach (range(1, 12) as $n) {
            $this->publicar(['nombre' => sprintf('Licencia de prueba %02d', $n), 'slug' => 'licencia-'.$n]);
        }

        $vistos = [];

        foreach ([1, 2, 3] as $pagina) {
            $respuesta = $this->getJson("/api/v1/buscar?q=licencia&per_page=5&page={$pagina}");

            $respuesta->assertOk()
                ->assertJsonPath('meta.current_page', $pagina)
                ->assertJsonPath('meta.per_page', 5)
                ->assertJsonPath('meta.last_page', 3)
                ->assertJsonPath('meta.total', 12);

            /** @var list<array{id: int}> $datos */
            $datos = $respuesta->json('data');
            $this->assertCount($pagina === 3 ? 2 : 5, $datos);

            foreach ($datos as $dato) {
                $vistos[] = $dato['id'];
            }

            if ($pagina === 1) {
                $this->assertStringContainsString('q=licencia', (string) $respuesta->json('links.next'));
                $this->assertNull($respuesta->json('links.prev'));
            }
        }

        $this->assertCount(12, array_unique($vistos), 'Una página repitió o se saltó resultados.');
    }

    public function test_sin_coincidencias_la_respuesta_es_una_pagina_vacia_valida(): void
    {
        $this->publicar(['nombre' => 'Certificado de residencia', 'slug' => 'residencia']);

        $this->getJson('/api/v1/buscar?q=pasaporte')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.from', null);
    }

    public function test_el_limite_de_peticiones_responde_429_con_retry_after(): void
    {
        config(['busqueda.limite_por_minuto' => 3]);

        foreach (range(1, 3) as $_) {
            $this->getJson('/api/v1/busqueda/sugerencias?q=licencia')->assertOk();
        }

        // El cupo es uno solo para las dos operaciones.
        $respuesta = $this->getJson('/api/v1/buscar?q=licencia');

        $respuesta->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null);
        $this->assertNotNull($respuesta->headers->get('Retry-After'));
        $this->assertGreaterThan(0, (int) $respuesta->headers->get('Retry-After'));
    }

    public function test_cada_ip_tiene_su_propio_cupo(): void
    {
        config(['busqueda.limite_por_minuto' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->getJson('/api/v1/busqueda/sugerencias?q=licencia')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->getJson('/api/v1/busqueda/sugerencias?q=licencia')->assertStatus(429);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->getJson('/api/v1/busqueda/sugerencias?q=licencia')->assertOk();
    }

    public function test_lo_que_el_ciudadano_escribe_no_se_registra_en_el_log_de_la_aplicacion(): void
    {
        // AC-22.7: la consulta puede traer un número de documento. Ni éxito, ni
        // validación fallida, ni 429 pueden dejarla en el registro de la aplicación.
        $this->publicar(['nombre' => 'Certificado de residencia', 'slug' => 'residencia']);

        $registro = [];
        Event::listen(MessageLogged::class, function (MessageLogged $evento) use (&$registro): void {
            $registro[] = $evento->message.' '.json_encode($evento->context);
        });

        $secreto = 'documento1234567890';

        $this->getJson('/api/v1/busqueda/sugerencias?q='.$secreto)->assertOk();
        $this->getJson('/api/v1/buscar?q='.$secreto)->assertOk();
        $this->getJson('/api/v1/buscar?q='.$secreto.'&per_page=500')->assertStatus(422);

        config(['busqueda.limite_por_minuto' => 1]);
        $this->getJson('/api/v1/buscar?q='.$secreto)->assertStatus(429);

        foreach ($registro as $linea) {
            $this->assertStringNotContainsString($secreto, $linea);
        }
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function publicar(array $cambios = []): Tramite
    {
        $numero = Tramite::query()->count() + 1;

        /** @var array<string, mixed> $atributos */
        $atributos = array_merge([
            'codigo' => 'T'.str_pad((string) $numero, 5, '0', STR_PAD_LEFT),
            'slug' => 'tramite-de-prueba-'.$numero,
            'nombre' => 'Trámite de prueba',
            'resumen' => 'Sirve para comprobar el catálogo.',
            'modalidad' => 'parcialmente_en_linea',
            'tiene_costo' => 'gratuito',
            'costo' => null,
            'tiempo_solucion_dias' => 15,
            'canal_inicio' => 'propio',
            'url_inicio' => null,
            'consulta_estado' => '/seguimiento?radicado=…',
            'requisitos' => [['descripcion' => 'Cédula de ciudadanía', 'obligatorio' => true]],
            'documentos' => [['nombre' => 'Formulario', 'url' => null, 'formato' => 'PDF']],
            'categoria_slug' => null,
            'categoria_nombre' => null,
            'url_ficha_gov_co' => 'https://www.gov.co/ficha-tramites-y-servicios/T00001',
            'procedencia_fuente' => 'SUIT — Función Pública (entidad 0043)',
            'procedencia_url' => 'https://www.funcionpublica.gov.co/suit',
            'procedencia_obtenido_en' => '2026-09-30',
            'procedencia_nota' => null,
            'publicado_en' => Carbon::now(),
        ], $cambios);

        return Tramite::create($atributos);
    }
}
