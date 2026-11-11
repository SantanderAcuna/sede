<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Politica;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PoliticaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Lo que RF-01-019 y RF-01-020 exigen del CONTENIDO de las políticas, como regla
 * que el sistema hace cumplir (M01-5).
 *
 * El texto oficial lo aporta la Entidad —con su acto administrativo—; lo que se
 * prueba aquí es que cuando llegue no pueda publicarse incompleto:
 *
 *  - RF-01-019: los términos tienen sus seis partes (a–f), incluida la jurisdicción.
 *  - RF-01-020: la política de protección de datos cita la Ley 1581 de 2012 y fija
 *    el plazo de respuesta a los reclamos (15 días hábiles, art. 15).
 *
 * Ninguna de estas pruebas inventa contenido institucional: lo que sólo la Entidad
 * puede decidir sigue siendo un marcador «[por definir: …]».
 */
final class PoliticasRequisitosTest extends TestCase
{
    use RefreshDatabase;

    private const TERMINOS = 'terminos-y-condiciones-de-uso';

    private const DATOS = 'proteccion-y-tratamiento-de-datos-personales';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, PoliticaSeeder::class]);
    }

    private function editor(): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo('politicas.gestionar');

        return $usuario;
    }

    /** @return list<array{id: string, titulo: string, parrafos: list<string>}> */
    private function secciones(string $slug): array
    {
        return Politica::query()->where('slug', $slug)->firstOrFail()->secciones;
    }

    /**
     * @param  list<array{id: string, titulo: string, parrafos: list<string>}>  $secciones
     * @return list<array{id: string, titulo: string, parrafos: list<string>}>
     */
    private function sin(array $secciones, string $id): array
    {
        return array_values(array_filter($secciones, static fn (array $s): bool => $s['id'] !== $id));
    }

    /**
     * @param  list<array{id: string, titulo: string, parrafos: list<string>}>  $secciones
     * @param  list<string>  $parrafos
     * @return list<array{id: string, titulo: string, parrafos: list<string>}>
     */
    private function con(array $secciones, string $id, array $parrafos): array
    {
        foreach ($secciones as $i => $seccion) {
            if ($seccion['id'] === $id) {
                $secciones[$i]['parrafos'] = $parrafos;
            }
        }

        return $secciones;
    }

    // ------------------------------------------------- RF-01-019: los términos

    public function test_los_terminos_exigen_las_seis_partes_del_requisito_incluida_la_jurisdiccion(): void
    {
        $ids = array_keys(Politica::COMPONENTES[self::TERMINOS]);

        // (a) condiciones, alcance y límites; (b) derechos y deberes; (c) responsabilidad;
        // (d) contacto; (e) privacidad y seguridad; (f) jurisdicción.
        foreach ([
            'condiciones-y-alcance', 'limites-de-uso', 'derechos-del-usuario', 'deberes-del-usuario',
            'responsabilidad-de-la-entidad', 'contacto', 'privacidad-y-seguridad', 'jurisdiccion',
        ] as $esperado) {
            $this->assertContains($esperado, $ids, "Falta exigir el apartado «{$esperado}».");
        }
    }

    public function test_los_terminos_sembrados_traen_la_jurisdiccion_sin_inventar_la_autoridad(): void
    {
        $jurisdiccion = collect($this->secciones(self::TERMINOS))->firstWhere('id', 'jurisdiccion');

        $this->assertNotNull($jurisdiccion, 'Los términos sembrados no traen el apartado de jurisdicción.');
        $texto = implode(' ', $jurisdiccion['parrafos']);
        $this->assertNotSame('', trim($texto));
        // Qué autoridad resuelve las controversias lo decide la Entidad: queda como marcador.
        $this->assertMatchesRegularExpression(Politica::MARCADOR, $texto);
        $this->assertSame([], Politica::query()->where('slug', self::TERMINOS)->firstOrFail()->problemas());
    }

    public function test_el_panel_no_deja_guardar_los_terminos_sin_jurisdiccion(): void
    {
        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/'.self::TERMINOS, [
                'secciones' => $this->sin($this->secciones(self::TERMINOS), 'jurisdiccion'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['politica'], 'errors');
    }

    public function test_un_texto_oficial_completo_de_los_terminos_se_publica_con_su_acto(): void
    {
        $secciones = $this->con($this->secciones(self::TERMINOS), 'jurisdiccion', [
            'Texto de jurisdicción aportado por la Entidad para esta prueba.',
        ]);

        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/'.self::TERMINOS, [
                'secciones' => $secciones,
                'es_ejemplo' => false,
                'vigente_desde' => '2026-12-01',
                'acto_tipo' => 'Resolución',
                'acto_numero' => '999',
                'acto_fecha' => '2026-11-20',
            ])
            ->assertOk()
            ->assertJsonPath('data.es_ejemplo', false);
    }

    public function test_los_terminos_con_jurisdiccion_pendiente_no_pierden_la_marca_de_ejemplo(): void
    {
        // El marcador «[por definir: …]» de la jurisdicción impide publicarlos como definitivos.
        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/'.self::TERMINOS, [
                'es_ejemplo' => false,
                'vigente_desde' => '2026-12-01',
                'acto_tipo' => 'Resolución',
                'acto_numero' => '999',
                'acto_fecha' => '2026-11-20',
            ])
            ->assertStatus(422);

        $this->assertTrue(Politica::query()->where('slug', self::TERMINOS)->firstOrFail()->es_ejemplo);
    }

    // ---------------------------------------- RF-01-020: la política de datos

    public function test_la_politica_de_datos_sembrada_fija_el_plazo_de_reclamos_y_cita_la_ley(): void
    {
        $reclamos = collect($this->secciones(self::DATOS))->firstWhere('id', 'reclamos');
        $texto = implode(' ', $reclamos['parrafos']);

        // Ley 1581 de 2012, art. 15: 15 días hábiles, prorrogables hasta 8 más. Art. 14: consultas, 10 y 5.
        $this->assertMatchesRegularExpression('/quince \(15\) días hábiles/u', $texto);
        $this->assertMatchesRegularExpression('/ocho \(8\) días hábiles/u', $texto);
        $this->assertMatchesRegularExpression('/diez \(10\) días hábiles/u', $texto);
        $this->assertMatchesRegularExpression('/cinco \(5\) días hábiles/u', $texto);
        $this->assertStringContainsString('Ley 1581 de 2012', $texto);
        // El canal y el formulario son de la Entidad: siguen pendientes.
        $this->assertStringContainsString('[por definir: canal y formulario oficiales]', $texto);
        $this->assertSame([], Politica::query()->where('slug', self::DATOS)->firstOrFail()->problemas());
    }

    public function test_el_panel_no_deja_guardar_la_politica_de_datos_sin_el_plazo_de_reclamos(): void
    {
        $secciones = $this->con($this->secciones(self::DATOS), 'reclamos', [
            'Los reclamos se presentan por escrito ante el responsable [por definir: canal y formulario oficiales].',
        ]);

        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/'.self::DATOS, ['secciones' => $secciones])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['politica'], 'errors');
    }

    /** @return iterable<string, array{string}> */
    public static function redaccionesDelPlazo(): iterable
    {
        yield 'con letras y cifra' => ['El reclamo se atiende en quince (15) días hábiles.'];
        yield 'solo cifra' => ['El reclamo se atiende en 15 días hábiles.'];
        yield 'solo letras' => ['El reclamo se atiende en quince días hábiles.'];
    }

    #[DataProvider('redaccionesDelPlazo')]
    public function test_el_plazo_se_acepta_en_las_redacciones_habituales(string $frase): void
    {
        $secciones = $this->con($this->secciones(self::DATOS), 'reclamos', [
            $frase.' Conforme a la Ley 1581 de 2012 [por definir: canal y formulario oficiales].',
        ]);

        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/'.self::DATOS, ['secciones' => $secciones])
            ->assertOk();
    }

    public function test_un_plazo_distinto_de_15_dias_habiles_no_cuenta(): void
    {
        foreach (['El reclamo se atiende en diez (10) días hábiles.', 'El reclamo se atiende en 15 días calendario.', 'El reclamo se atiende en 150 días hábiles.'] as $frase) {
            $secciones = $this->con($this->secciones(self::DATOS), 'reclamos', [$frase.' Ley 1581 de 2012.']);

            $this->actingAs($this->editor(), 'web')
                ->patchJson('/api/v1/panel/politicas/'.self::DATOS, ['secciones' => $secciones])
                ->assertStatus(422);
        }
    }

    public function test_el_cuatro_digitos_de_la_ley_no_se_confunde_con_el_plazo(): void
    {
        // Error real del análisis previo: «1581» contiene «15». Un texto que sólo cita la
        // ley, sin plazo, no puede pasar por tener ese número.
        $secciones = $this->con($this->secciones(self::DATOS), 'reclamos', [
            'Se atiende conforme a la Ley 1581 de 2012 [por definir: canal y formulario oficiales].',
        ]);

        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/'.self::DATOS, ['secciones' => $secciones])
            ->assertStatus(422);
    }

    public function test_la_politica_de_datos_debe_citar_la_ley_1581(): void
    {
        $secciones = $this->con($this->secciones(self::DATOS), 'reclamos', [
            'Los reclamos se atienden en quince (15) días hábiles [por definir: canal y formulario oficiales].',
        ]);
        // La ley sólo se citaba en este apartado.
        $this->assertStringNotContainsString('Ley 1581', json_encode($secciones, JSON_THROW_ON_ERROR));

        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/'.self::DATOS, ['secciones' => $secciones])
            ->assertStatus(422);
    }

    public function test_las_otras_politicas_no_heredan_esas_exigencias(): void
    {
        // El plazo y la ley son de la política de datos personales; seguridad y privacidad,
        // cookies y derechos de autor no los exigen (no se inventa un requisito).
        foreach (['seguridad-y-privacidad', 'uso-de-cookies', 'derechos-de-autor-y-uso-sobre-contenidos'] as $slug) {
            $this->assertSame([], Politica::query()->where('slug', $slug)->firstOrFail()->problemas(), $slug);
        }
        $this->assertStringNotContainsString('quince', json_encode($this->secciones('uso-de-cookies'), JSON_THROW_ON_ERROR));
    }

    // ----------------------------------- Las filas que ya existen en staging

    private function migracion(): object
    {
        $archivos = glob(database_path('migrations/*_completar_requisitos_de_politicas.php')) ?: [];
        $this->assertCount(1, $archivos, 'Falta la migración que completa las políticas ya sembradas.');

        return require $archivos[0];
    }

    /** Deja las filas como las dejó el sembrador antes de este cambio. */
    private function comoAntes(): void
    {
        $terminos = Politica::query()->where('slug', self::TERMINOS)->firstOrFail();
        $terminos->secciones = $this->sin($terminos->secciones, 'jurisdiccion');
        $terminos->save();

        $datos = Politica::query()->where('slug', self::DATOS)->firstOrFail();
        $datos->secciones = $this->con($datos->secciones, 'reclamos', [
            'Las consultas y reclamos se presentan por escrito ante el responsable, con la identificación del titular, la descripción de los hechos y los datos de contacto. La Entidad los atiende en los términos de la Ley 1581 de 2012 [por definir: canal y formulario oficiales].',
        ]);
        $datos->save();
    }

    public function test_la_migracion_completa_las_filas_ya_sembradas_sin_perder_nada(): void
    {
        $this->comoAntes();
        $this->assertNotSame([], Politica::query()->where('slug', self::TERMINOS)->firstOrFail()->problemas());
        $this->assertNotSame([], Politica::query()->where('slug', self::DATOS)->firstOrFail()->problemas());

        $this->migracion()->up();

        foreach ([self::TERMINOS, self::DATOS] as $slug) {
            $politica = Politica::query()->where('slug', $slug)->firstOrFail();
            $this->assertSame([], $politica->problemas(), $slug);
            $this->assertTrue($politica->es_ejemplo, $slug.' sigue siendo un ejemplo.');
        }

        $reclamos = implode(' ', collect($this->secciones(self::DATOS))->firstWhere('id', 'reclamos')['parrafos']);
        // Lo que ya había se conserva, incluido el marcador del canal.
        $this->assertStringContainsString('[por definir: canal y formulario oficiales]', $reclamos);
        $this->assertStringContainsString('quince (15) días hábiles', $reclamos);
    }

    public function test_la_migracion_recalcula_el_indice_de_busqueda(): void
    {
        $this->comoAntes();
        $this->assertStringNotContainsString('jurisdiccion', (string) Politica::query()->where('slug', self::TERMINOS)->value('busqueda'));

        $this->migracion()->up();

        $this->assertStringContainsString('jurisdiccion', (string) Politica::query()->where('slug', self::TERMINOS)->value('busqueda'));
    }

    public function test_la_migracion_es_idempotente(): void
    {
        $this->comoAntes();

        $this->migracion()->up();
        $una = Politica::query()->orderBy('id')->get()->map->secciones->toJson();
        $this->migracion()->up();

        $this->assertSame($una, Politica::query()->orderBy('id')->get()->map->secciones->toJson());
        $this->assertCount(1, array_filter($this->secciones(self::TERMINOS), static fn (array $s): bool => $s['id'] === 'jurisdiccion'));
    }

    public function test_la_migracion_no_toca_una_politica_que_la_entidad_ya_adopto(): void
    {
        $this->comoAntes();
        Politica::query()->where('slug', self::TERMINOS)->update(['es_ejemplo' => false]);
        $antes = Politica::query()->where('slug', self::TERMINOS)->firstOrFail()->secciones;

        $this->migracion()->up();

        $this->assertSame($antes, Politica::query()->where('slug', self::TERMINOS)->firstOrFail()->secciones);
    }

    public function test_la_migracion_no_pisa_el_texto_que_la_entidad_ya_escribio_en_los_reclamos(): void
    {
        $this->comoAntes();
        $propio = 'Texto propio de la Entidad sobre el plazo: quince (15) días hábiles. Ley 1581 de 2012.';
        $datos = Politica::query()->where('slug', self::DATOS)->firstOrFail();
        $datos->secciones = $this->con($datos->secciones, 'reclamos', [$propio]);
        $datos->save();

        $this->migracion()->up();

        $reclamos = collect($this->secciones(self::DATOS))->firstWhere('id', 'reclamos');
        $this->assertSame([$propio], $reclamos['parrafos']);
    }
}
