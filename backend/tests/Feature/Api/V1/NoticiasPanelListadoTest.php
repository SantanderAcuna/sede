<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Listado de noticias del panel: buscar, filtrar por estado, ordenar y paginar (M01-6).
 *
 * El panel muestra las noticias en una tabla de datos. Una tabla que solo ve la primera
 * página y filtra «en el navegador» miente: el filtro tiene que hacerse en el servidor,
 * sobre todas las noticias, y el estado (publicada / programada / borrador) tiene que
 * decidirlo el servidor con su reloj, no el navegador con el suyo.
 */
final class NoticiasPanelListadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function editor(): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo('noticias.gestionar');

        return $usuario;
    }

    /**
     * @param  array<string, mixed>  $campos
     */
    private function noticia(string $titulo, array $campos = []): string
    {
        $uuid = (string) Str::uuid();

        DB::table('noticias')->insert($campos + [
            'uuid' => $uuid,
            'slug' => Str::slug($titulo).'-'.Str::lower(Str::random(5)),
            'titulo' => $titulo,
            'descripcion_corta' => 'Resumen.',
            'contenido' => 'Cuerpo largo de la noticia.',
            'fecha_publicacion' => now()->subDay(),
            'destacada' => false,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $uuid;
    }

    /**
     * @param  array<string, scalar>  $consulta
     * @return list<array<string, mixed>>
     */
    private function listar(array $consulta = []): array
    {
        /** @var list<array<string, mixed>> $datos */
        $datos = $this->actingAs($this->editor(), 'web')
            ->getJson('/api/v1/panel/noticias?'.http_build_query($consulta))
            ->assertOk()
            ->json('data');

        return $datos;
    }

    /**
     * @param  list<array<string, mixed>>  $datos
     * @return list<string>
     */
    private function titulos(array $datos): array
    {
        return array_map(static fn (array $n): string => (string) $n['titulo'], $datos);
    }

    public function test_sin_filtros_van_primero_las_de_fecha_mas_reciente(): void
    {
        $this->noticia('Vieja', ['fecha_publicacion' => now()->subDays(10)]);
        $this->noticia('Reciente', ['fecha_publicacion' => now()->subDay()]);
        $this->noticia('Intermedia', ['fecha_publicacion' => now()->subDays(5)]);

        $this->assertSame(['Reciente', 'Intermedia', 'Vieja'], $this->titulos($this->listar()));
    }

    public function test_el_estado_lo_decide_el_servidor_con_su_reloj(): void
    {
        $this->noticia('Pública', ['activo' => true, 'fecha_publicacion' => now()->subHour()]);
        $this->noticia('Programada', ['activo' => true, 'fecha_publicacion' => now()->addDays(3)]);
        $this->noticia('Borrador', ['activo' => false, 'fecha_publicacion' => now()->subHour()]);
        $this->noticia('Borrador a futuro', ['activo' => false, 'fecha_publicacion' => now()->addDays(3)]);

        $estados = [];
        foreach ($this->listar() as $n) {
            $estados[(string) $n['titulo']] = $n['estado'];
        }

        $this->assertSame('publicada', $estados['Pública']);
        $this->assertSame('programada', $estados['Programada']);
        $this->assertSame('borrador', $estados['Borrador']);
        $this->assertSame('borrador', $estados['Borrador a futuro'], 'Sin marcar «publicada» es borrador aunque la fecha sea futura.');
    }

    public function test_filtra_por_cada_estado(): void
    {
        $this->noticia('Pública', ['activo' => true, 'fecha_publicacion' => now()->subHour()]);
        $this->noticia('Programada', ['activo' => true, 'fecha_publicacion' => now()->addDays(3)]);
        $this->noticia('Borrador', ['activo' => false]);

        $this->assertSame(['Pública'], $this->titulos($this->listar(['estado' => 'publicada'])));
        $this->assertSame(['Programada'], $this->titulos($this->listar(['estado' => 'programada'])));
        $this->assertSame(['Borrador'], $this->titulos($this->listar(['estado' => 'borrador'])));
    }

    public function test_busca_por_titulo_sin_distinguir_mayusculas(): void
    {
        $this->noticia('Inauguración del Parque Central');
        $this->noticia('Jornada de vacunación');

        $this->assertSame(['Inauguración del Parque Central'], $this->titulos($this->listar(['q' => 'parque central'])));
        $this->assertSame(['Jornada de vacunación'], $this->titulos($this->listar(['q' => 'VACUNA'])));
        $this->assertSame([], $this->listar(['q' => 'no existe']));
    }

    public function test_los_comodines_de_like_se_buscan_como_texto_literal(): void
    {
        $this->noticia('Descuento del 100% en impuestos');
        $this->noticia('Descuento del 100 por ciento');
        $this->noticia('Fondo_de_pantalla');
        $this->noticia('Fondo de pantalla');

        $this->assertSame(['Descuento del 100% en impuestos'], $this->titulos($this->listar(['q' => '100%'])));
        $this->assertSame(['Fondo_de_pantalla'], $this->titulos($this->listar(['q' => 'Fondo_de'])));
        $this->assertSame([], $this->listar(['q' => '\\']), 'Una barra invertida suelta no debe romper la consulta.');
    }

    public function test_una_busqueda_vacia_o_de_espacios_no_filtra(): void
    {
        $this->noticia('Una');
        $this->noticia('Dos');

        $this->assertCount(2, $this->listar(['q' => '']));
        $this->assertCount(2, $this->listar(['q' => '   ']));
    }

    public function test_ordena_por_titulo_en_ambos_sentidos(): void
    {
        $this->noticia('Banco');
        $this->noticia('Alcaldía');
        $this->noticia('Concejo');

        $this->assertSame(['Alcaldía', 'Banco', 'Concejo'], $this->titulos($this->listar(['ordenar_por' => 'titulo', 'direccion' => 'asc'])));
        $this->assertSame(['Concejo', 'Banco', 'Alcaldía'], $this->titulos($this->listar(['ordenar_por' => 'titulo', 'direccion' => 'desc'])));
    }

    public function test_ordenar_por_titulo_sin_direccion_va_de_la_a_a_la_z(): void
    {
        $this->noticia('Banco');
        $this->noticia('Alcaldía');

        $this->assertSame(['Alcaldía', 'Banco'], $this->titulos($this->listar(['ordenar_por' => 'titulo'])));
    }

    public function test_ordena_por_fecha_en_ambos_sentidos(): void
    {
        $this->noticia('Vieja', ['fecha_publicacion' => now()->subDays(10)]);
        $this->noticia('Reciente', ['fecha_publicacion' => now()->subDay()]);

        $this->assertSame(['Vieja', 'Reciente'], $this->titulos($this->listar(['ordenar_por' => 'fecha_publicacion', 'direccion' => 'asc'])));
        $this->assertSame(['Reciente', 'Vieja'], $this->titulos($this->listar(['ordenar_por' => 'fecha_publicacion', 'direccion' => 'desc'])));
    }

    public function test_con_la_misma_fecha_el_orden_es_estable_entre_paginas(): void
    {
        $fecha = now()->subDay();
        for ($i = 1; $i <= 5; $i++) {
            $this->noticia("Misma fecha {$i}", ['fecha_publicacion' => $fecha]);
        }

        $vistos = [];
        foreach ([1, 2, 3] as $pagina) {
            foreach ($this->titulos($this->listar(['per_page' => 2, 'page' => $pagina])) as $titulo) {
                $vistos[] = $titulo;
            }
        }

        $this->assertCount(5, $vistos);
        $this->assertCount(5, array_unique($vistos), 'Ninguna noticia puede repetirse ni faltar entre páginas.');
    }

    public function test_el_total_y_las_paginas_reflejan_el_filtro_no_toda_la_tabla(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->noticia("Obra vial {$i}");
        }
        for ($i = 1; $i <= 4; $i++) {
            $this->noticia("Cultura {$i}");
        }

        $respuesta = $this->actingAs($this->editor(), 'web')
            ->getJson('/api/v1/panel/noticias?'.http_build_query(['q' => 'obra', 'per_page' => 3, 'page' => 3]))
            ->assertOk();

        $respuesta->assertJsonPath('meta.total', 7);
        $respuesta->assertJsonPath('meta.last_page', 3);
        $respuesta->assertJsonPath('meta.current_page', 3);
        $this->assertCount(1, $respuesta->json('data'));
    }

    public function test_los_filtros_se_combinan(): void
    {
        $this->noticia('Obra publicada', ['activo' => true]);
        $this->noticia('Obra en borrador', ['activo' => false]);
        $this->noticia('Cultura publicada', ['activo' => true]);

        $this->assertSame(['Obra en borrador'], $this->titulos($this->listar(['q' => 'obra', 'estado' => 'borrador'])));
    }

    public function test_las_eliminadas_no_aparecen_en_ningun_filtro(): void
    {
        $this->noticia('Eliminada', ['deleted_at' => now()]);
        $this->noticia('Viva');

        $this->assertSame(['Viva'], $this->titulos($this->listar()));
        $this->assertSame([], $this->listar(['q' => 'Eliminada']));
    }

    public function test_el_listado_no_arrastra_el_cuerpo_pero_el_detalle_si(): void
    {
        $uuid = $this->noticia('Con cuerpo', ['contenido' => 'Cuerpo que solo el detalle debe traer.']);

        $item = $this->listar()[0];
        $this->assertArrayNotHasKey('contenido', $item, 'El listado de una tabla no necesita el cuerpo de cada noticia.');
        foreach (['uuid', 'estado', 'activo', 'completa', 'problemas', 'titulo', 'fecha_publicacion', 'imagen'] as $campo) {
            $this->assertArrayHasKey($campo, $item, "Falta {$campo}");
        }

        $this->actingAs($this->editor(), 'web')->getJson("/api/v1/panel/noticias/{$uuid}")
            ->assertOk()
            ->assertJsonPath('data.contenido', '<p>Cuerpo que solo el detalle debe traer.</p>')
            ->assertJsonPath('data.estado', 'publicada');
    }

    public function test_crear_y_editar_devuelven_el_estado(): void
    {
        $editor = $this->editor();
        $creada = $this->actingAs($editor, 'web')->postJson('/api/v1/panel/noticias', [
            'titulo' => 'Nueva',
            'descripcion_corta' => 'Resumen',
            'contenido' => 'Texto.',
            'fecha_publicacion' => now()->addDay()->toIso8601String(),
        ])->assertCreated();

        $creada->assertJsonPath('data.estado', 'borrador');
    }

    /**
     * @return array<string, array{array<string, scalar>}>
     */
    public static function consultasInvalidas(): array
    {
        return [
            'estado desconocido' => [['estado' => 'archivada']],
            'orden por una columna que no se ofrece' => [['ordenar_por' => 'contenido']],
            'orden con inyección' => [['ordenar_por' => 'titulo; drop table noticias']],
            'dirección desconocida' => [['direccion' => 'sideways']],
            'más de 100 por página' => [['per_page' => 101]],
            'cero por página' => [['per_page' => 0]],
            'página cero' => [['page' => 0]],
            'búsqueda demasiado larga' => [['q' => str_repeat('a', 101)]],
        ];
    }

    /**
     * @param  array<string, scalar>  $consulta
     */
    #[DataProvider('consultasInvalidas')]
    public function test_una_consulta_invalida_se_rechaza_con_422(array $consulta): void
    {
        $this->actingAs($this->editor(), 'web')
            ->getJson('/api/v1/panel/noticias?'.http_build_query($consulta))
            ->assertStatus(422);
    }

    public function test_los_filtros_no_cambian_quien_puede_listar(): void
    {
        $this->getJson('/api/v1/panel/noticias?q=algo')->assertUnauthorized();
        $this->actingAs(User::factory()->create(), 'web')->getJson('/api/v1/panel/noticias?q=algo')->assertForbidden();
    }
}
