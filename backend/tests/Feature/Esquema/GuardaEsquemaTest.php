<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InspectorEsquema;
use Tests\TestCase;

/**
 * Guarda del esquema con línea base y trinquete (0D, D1; ADR 0008).
 *
 * 1. Un defecto que no está en la línea base rompe la CI.
 * 2. Una entrada de la línea base que ya no existe en el esquema también la
 *    rompe: la lista solo puede bajar, y quien corrige algo la edita.
 * 3. Las excepciones `permanente: true` llevan su razón y se mantienen a propósito.
 */
final class GuardaEsquemaTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{permanente: bool, razon: string|null}> */
    private function lineaBase(): array
    {
        $json = json_decode(
            (string) file_get_contents(__DIR__.'/esquema.linea-base.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $entradas = [];
        foreach ($json['defectos'] as $d) {
            $entradas[$d['id']] = ['permanente' => (bool) ($d['permanente'] ?? false), 'razon' => $d['razon'] ?? null];
        }

        return $entradas;
    }

    public function test_no_aparece_ningun_defecto_nuevo_en_el_esquema(): void
    {
        $nuevos = array_values(array_diff(InspectorEsquema::hallazgos(), array_keys($this->lineaBase())));

        $this->assertSame(
            [],
            $nuevos,
            "Defectos de esquema que no están en la línea base (corrígelos o, si es una excepción permanente, documéntala en esquema.linea-base.json con su razón):\n".implode("\n", $nuevos),
        );
    }

    public function test_la_linea_base_solo_puede_bajar(): void
    {
        $obsoletos = array_values(array_diff(array_keys($this->lineaBase()), InspectorEsquema::hallazgos()));

        $this->assertSame(
            [],
            $obsoletos,
            "Entradas de la línea base que ya no existen en el esquema (quítalas del archivo: la lista solo baja):\n".implode("\n", $obsoletos),
        );
    }

    public function test_toda_excepcion_permanente_lleva_su_razon(): void
    {
        foreach ($this->lineaBase() as $id => $e) {
            if ($e['permanente']) {
                $this->assertNotEmpty($e['razon'], "La excepción permanente {$id} no tiene razón.");
            }
        }
    }
}
