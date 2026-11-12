<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Tramite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El catálogo público de trámites.
 *
 * Lo que estas pruebas protegen no es que la ruta responda, sino las promesas del contrato: que el
 * catálogo se recorre por páginas, que la tarjeta trae los metadatos que el directorio compara
 * (modalidad, costo, tiempo), que la búsqueda perdona las tildes, que las facetas acompañan al
 * listado y que la integración con GOV.CO se cumple —un trámite sin ficha nacional no se publica—.
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
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.from', 11)
            ->assertJsonPath('meta.to', 20);

        /** @var list<array<string, mixed>> $datos */
        $datos = $respuesta->json('data');

        $this->assertCount(10, $datos);
        $this->assertSame('Trámite de prueba 11', $datos[0]['nombre']);
        $this->assertIsString($respuesta->json('links.next'));
    }

    public function test_la_tarjeta_trae_los_metadatos_del_directorio(): void
    {
        $tramite = $this->publicar();

        $this->getJson('/api/v1/tramites')
            ->assertOk()
            ->assertJsonPath('data.0.id', $tramite->id)
            ->assertJsonPath('data.0.type', 'tramites')
            ->assertJsonPath('data.0.slug', $tramite->slug)
            ->assertJsonPath('data.0.modalidad', 'parcialmente_en_linea')
            ->assertJsonPath('data.0.tiene_costo', 'gratuito')
            ->assertJsonPath('data.0.tiempo_solucion_dias', 15)
            ->assertJsonPath('data.0.url_ficha_gov_co', $tramite->url_ficha_gov_co)
            // La fuente no clasifica ni asigna dependencia por defecto: viajan en nulo, no inventados.
            ->assertJsonPath('data.0.categoria', null)
            ->assertJsonPath('data.0.dependencia', null);
    }

    public function test_el_listado_trae_sus_facetas_en_meta(): void
    {
        $this->publicar(['modalidad' => 'en_linea']);
        $this->publicar(['modalidad' => 'presencial']);

        $respuesta = $this->getJson('/api/v1/tramites')->assertOk();

        $this->assertIsArray($respuesta->json('meta.facetas'));
        $this->assertIsArray($respuesta->json('meta.facetas.modalidades'));
        $this->assertIsArray($respuesta->json('meta.facetas.tipos'));
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

        $this->getJson('/api/v1/tramites?buscar=rectificacion')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nombre', 'Rectificación de áreas y linderos');

        $this->getJson('/api/v1/tramites?buscar='.rawurlencode('ÁREAS'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/v1/tramites?buscar='.rawurlencode('inmuebles'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nombre', 'Impuesto predial unificado');
    }

    public function test_un_tramite_sin_ficha_en_gov_co_no_se_publica(): void
    {
        $conFicha = $this->publicar();

        // Integración GOV.CO: un trámite sin ficha nacional puede existir como borrador, pero no se
        // publica (RF-B1-021).
        $sinFicha = $this->publicar([
            'nombre' => 'Trámite sin ficha en GOV.CO',
            'url_ficha_gov_co' => null,
        ]);

        $this->getJson('/api/v1/tramites')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', $conFicha->slug);

        $this->getJson('/api/v1/tramites/'.$sinFicha->slug)->assertStatus(404);
    }

    public function test_una_consulta_no_exige_ficha_en_gov_co(): void
    {
        // Las consultas de acceso a información pública no están en el SUIT (RN-B2-002): se publican
        // sin ficha nacional.
        $consulta = $this->publicar([
            'type' => 'consultas',
            'nombre' => 'Consulta de estado de cuenta de predial',
            'url_ficha_gov_co' => null,
        ]);

        $this->getJson('/api/v1/tramites?type=consultas')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', $consulta->slug);
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
            ->assertJsonPath('data.slug', $tramite->slug)
            ->assertJsonPath('data.url_ficha_gov_co', $tramite->url_ficha_gov_co)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id', 'type', 'slug', 'nombre', 'resumen', 'url_ficha_gov_co',
                    'modalidad', 'tiene_costo', 'costo', 'tiempo_solucion_dias',
                    'canal_inicio', 'consulta_estado', 'pasos', 'requisitos', 'documentos',
                    'puntos_atencion', 'normativa', 'canales_consulta_estado', 'solicitantes',
                    'url_inicio', 'categoria', 'dependencia', 'publicado_en', 'actualizado_en',
                ],
                'errors',
            ]);
    }

    public function test_per_page_por_encima_del_maximo_responde_422_con_el_sobre(): void
    {
        $this->getJson('/api/v1/tramites?per_page=101')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.per_page.0', 'El campo per_page no puede ser mayor que 100.');
    }

    /**
     * Publica un trámite completo. Quien quiera comprobar qué pasa cuando falta un atributo dice
     * cuál quita en `$cambios`, y así se lee en la propia prueba.
     *
     * @param  array<string, mixed>  $cambios
     */
    private function publicar(array $cambios = []): Tramite
    {
        return Tramite::factory()->published()->create(array_merge([
            'nombre' => 'Trámite de prueba',
            'resumen' => 'Sirve para comprobar el catálogo.',
            'modalidad' => 'parcialmente_en_linea',
            'tiene_costo' => 'gratuito',
            'costo' => null,
            'tiempo_solucion_dias' => 15,
            'canal_inicio' => 'propio',
            'url_inicio' => null,
            'consulta_estado' => '/seguimiento?radicado=…',
        ], $cambios));
    }
}
