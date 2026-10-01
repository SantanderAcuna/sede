<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Tramite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * El catálogo público de trámites.
 *
 * Lo que estas pruebas protegen no es que la ruta responda, sino las tres
 * promesas que el contrato hace al ciudadano: que el catálogo se puede recorrer
 * por páginas, que la búsqueda perdona las tildes —el sitio ya lo asume— y que
 * nada sin ficha en GOV.CO llega a publicarse.
 */
final class TramiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_listado_pagina_el_catalogo(): void
    {
        foreach (range(1, 25) as $numero) {
            $this->publicar(['nombre' => sprintf('Trámite de prueba %02d', $numero)]);
        }

        $respuesta = $this->getJson('/api/v1/tramites?per_page=10&page=2');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', null)
            ->assertJsonPath('errors', null)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.from', 11)
            ->assertJsonPath('meta.to', 20);

        /** @var list<array<string, mixed>> $datos */
        $datos = $respuesta->json('data');

        $this->assertCount(10, $datos);

        // La segunda página empieza donde terminó la primera: con un orden total
        // —el nombre— ninguna fila se repite ni se pierde entre páginas.
        $this->assertSame('Trámite de prueba 11', $datos[0]['nombre']);

        $this->assertIsString($respuesta->json('links.first'));
        $this->assertIsString($respuesta->json('links.last'));
        $this->assertIsString($respuesta->json('links.prev'));
        $this->assertIsString($respuesta->json('links.next'));
    }

    public function test_el_listado_trae_los_seis_atributos_de_cada_tramite(): void
    {
        $tramite = $this->publicar();

        $respuesta = $this->getJson('/api/v1/tramites');

        $respuesta->assertOk()
            ->assertJsonPath('data.0.id', $tramite->id)
            ->assertJsonPath('data.0.type', 'tramites')
            ->assertJsonPath('data.0.slug', $tramite->slug)
            ->assertJsonPath('data.0.modalidad', 'parcialmente_en_linea')
            ->assertJsonPath('data.0.tiene_costo', 'gratuito')
            ->assertJsonPath('data.0.tiempo_solucion_dias', 15)
            ->assertJsonPath('data.0.canal_inicio', 'propio')
            ->assertJsonPath('data.0.consulta_estado', '/seguimiento?radicado=…')
            ->assertJsonPath('data.0.url_ficha_gov_co', 'https://www.gov.co/ficha-tramites-y-servicios/T00001')
            ->assertJsonPath('data.0.requisitos.0.descripcion', 'Cédula de ciudadanía')
            ->assertJsonPath('data.0.procedencia.fuente', 'SUIT — Función Pública (entidad 0043)')
            ->assertJsonPath('data.0.procedencia.obtenido_en', '2026-09-30')
            // La fuente no clasifica los trámites: el campo viaja en nulo y no
            // con una categoría inventada.
            ->assertJsonPath('data.0.categoria', null);
    }

    public function test_la_busqueda_no_distingue_tildes(): void
    {
        $this->publicar([
            'nombre' => 'Rectificación de áreas y linderos',
            'resumen' => 'Corrige la cabida del predio cuando la escritura no coincide.',
        ]);
        $this->publicar([
            'nombre' => 'Impuesto predial unificado',
            'resumen' => 'Pago anual sobre los bienes inmuebles.',
        ]);

        // El ciudadano escribe sin tilde lo que el catálogo dice con tilde.
        $this->getJson('/api/v1/tramites?buscar=rectificacion')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nombre', 'Rectificación de áreas y linderos');

        // Y al revés: busca «áreas» con tilde y encuentra lo que la tiene.
        $this->getJson('/api/v1/tramites?buscar='.rawurlencode('ÁREAS'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        // También busca en el resumen, no sólo en el nombre.
        $this->getJson('/api/v1/tramites?buscar='.rawurlencode('inmuebles'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nombre', 'Impuesto predial unificado');
    }

    public function test_un_tramite_sin_ficha_en_gov_co_no_se_publica(): void
    {
        $conFicha = $this->publicar();

        // Un trámite guardado pero sin ficha: puede existir —la Entidad todavía
        // no la tiene— y no puede publicarse, porque el clic en su nombre no
        // llevaría a ninguna parte.
        $sinFicha = $this->publicar([
            'nombre' => 'Trámite sin ficha en GOV.CO',
            'slug' => 'tramite-sin-ficha',
            'url_ficha_gov_co' => null,
        ]);

        $respuesta = $this->getJson('/api/v1/tramites');

        $respuesta->assertOk()->assertJsonPath('meta.total', 1);
        $respuesta->assertJsonPath('data.0.slug', $conFicha->slug);

        // Tampoco por su ficha: no se publica es no estar en ninguna respuesta.
        $this->getJson('/api/v1/tramites/'.$sinFicha->slug)
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null);
    }

    public function test_un_tramite_sin_publicar_no_se_publica(): void
    {
        $this->publicar(['slug' => 'borrador', 'publicado_en' => null]);

        $this->getJson('/api/v1/tramites')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/tramites/borrador')->assertStatus(404);
    }

    public function test_la_ficha_de_un_tramite_publicado_trae_el_sobre_del_contrato(): void
    {
        $tramite = $this->publicar();

        $this->getJson('/api/v1/tramites/'.$tramite->slug)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', null)
            ->assertJsonPath('errors', null)
            ->assertJsonPath('data.slug', $tramite->slug)
            ->assertJsonPath('data.url_ficha_gov_co', $tramite->url_ficha_gov_co)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id', 'type', 'slug', 'nombre', 'resumen', 'url_ficha_gov_co',
                    'modalidad', 'tiene_costo', 'costo', 'tiempo_solucion_dias',
                    'canal_inicio', 'consulta_estado', 'requisitos', 'documentos',
                    'url_inicio', 'categoria', 'publicado_en', 'actualizado_en',
                    'procedencia' => ['fuente', 'url', 'obtenido_en', 'nota'],
                ],
                'errors',
            ]);
    }

    public function test_un_slug_desconocido_responde_404_con_el_sobre(): void
    {
        $this->getJson('/api/v1/tramites/no-existe')
            ->assertStatus(404)
            ->assertExactJson([
                'success' => false,
                'message' => 'El recurso no fue encontrado.',
                'data' => null,
                'errors' => null,
            ]);
    }

    public function test_per_page_por_encima_del_maximo_responde_422_con_el_sobre(): void
    {
        $this->getJson('/api/v1/tramites?per_page=101')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Error de validación.')
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.per_page.0', 'El campo per_page no puede ser mayor que 100.');
    }

    /**
     * Publica un trámite con todos sus atributos.
     *
     * Los valores por defecto son los de un trámite completo a propósito: una
     * prueba que quiera comprobar qué pasa cuando falta un atributo tiene que
     * decir cuál quita, y así se lee en la propia prueba.
     *
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
