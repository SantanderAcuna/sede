<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Tramite;
use App\Models\TramiteValoracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La valoración pública de una ficha: «¿Cómo fue tu experiencia?» (RF-B2-033/034) y la encuesta de
 * 1 a 5 estrellas (RF-B1-094). Es una escritura abierta a internet: sin sesión, con su propio límite
 * por IP, y sólo sobre fichas publicadas.
 */
final class ValoracionTramiteTest extends TestCase
{
    use RefreshDatabase;

    private function publicado(): Tramite
    {
        return Tramite::factory()->published()->create(['nombre' => 'Certificado de residencia', 'codigo' => 'T900']);
    }

    public function test_un_ciudadano_valora_una_ficha_publicada_como_facil(): void
    {
        $tramite = $this->publicado();

        $this->postJson("/api/v1/tramites/{$tramite->slug}/valoracion", ['valor' => 'facil'])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('tramite_valoraciones', ['tramite_id' => $tramite->id, 'valor' => 'facil']);
    }

    public function test_acepta_estrellas_y_comentario(): void
    {
        $tramite = $this->publicado();

        $this->postJson("/api/v1/tramites/{$tramite->slug}/valoracion", [
            'valor' => 'dificil',
            'estrellas' => 2,
            'comentario' => 'El formulario era confuso.',
        ])->assertCreated();

        $fila = TramiteValoracion::query()->where('tramite_id', $tramite->id)->firstOrFail();
        $this->assertSame('dificil', $fila->valor);
        $this->assertSame(2, $fila->estrellas);
        $this->assertSame('El formulario era confuso.', $fila->comentario);
    }

    public function test_acepta_solo_estrellas_sin_facil_ni_dificil(): void
    {
        $tramite = $this->publicado();

        $this->postJson("/api/v1/tramites/{$tramite->slug}/valoracion", ['estrellas' => 5])
            ->assertCreated();

        $this->assertDatabaseHas('tramite_valoraciones', ['tramite_id' => $tramite->id, 'estrellas' => 5]);
    }

    public function test_un_valor_invalido_es_422(): void
    {
        $tramite = $this->publicado();

        $this->postJson("/api/v1/tramites/{$tramite->slug}/valoracion", ['valor' => 'regular'])
            ->assertStatus(422);
    }

    public function test_sin_valor_ni_estrellas_es_422(): void
    {
        $tramite = $this->publicado();

        $this->postJson("/api/v1/tramites/{$tramite->slug}/valoracion", ['comentario' => 'Solo un comentario.'])
            ->assertStatus(422);
    }

    public function test_una_ficha_no_publicada_no_se_puede_valorar(): void
    {
        $borrador = Tramite::factory()->create(['nombre' => 'Borrador', 'codigo' => 'T901', 'url_ficha_gov_co' => null, 'publicado_en' => null]);

        $this->postJson("/api/v1/tramites/{$borrador->slug}/valoracion", ['valor' => 'facil'])
            ->assertNotFound();

        $this->postJson('/api/v1/tramites/no-existe/valoracion', ['valor' => 'facil'])
            ->assertNotFound();
    }

    public function test_tiene_su_propio_limite_por_ip(): void
    {
        config(['valoraciones.limite_por_minuto' => 2]);
        $tramite = $this->publicado();

        $this->postJson("/api/v1/tramites/{$tramite->slug}/valoracion", ['valor' => 'facil'])->assertCreated();
        $this->postJson("/api/v1/tramites/{$tramite->slug}/valoracion", ['valor' => 'facil'])->assertCreated();
        $this->postJson("/api/v1/tramites/{$tramite->slug}/valoracion", ['valor' => 'facil'])->assertStatus(429);
    }
}
