<?php

declare(strict_types=1);

namespace App\Support\Tramites;

use App\Support\Busqueda\TextoBuscable;

/**
 * La dependencia de la Alcaldía a la que se asigna de partida un trámite, según los puntos de
 * atención que el SUIT le declara.
 *
 * El SUIT no trae la dependencia como dato: solo nombra puntos de atención. Por eso la regla se
 * limita a la **evidencia directa**: un punto cuenta únicamente si su nombre es el de una de las
 * dependencias que la Sede ya tiene. No se deduce que la Dirección de Impuestos sea parte de
 * Hacienda ni que ESITTS lo sea de Movilidad (la Entidad no lo ha confirmado), y una curaduría
 * urbana, que es un particular, no es una dependencia.
 *
 * Se asigna únicamente cuando todos los puntos reconocidos coinciden en una sola dependencia.
 * Si dos puntos apuntan a dependencias distintas, si ninguno se reconoce o si el trámite declara
 * una lista desmedida de puntos (un volcado del catálogo completo, no un dato del trámite), se
 * devuelve `null` y la decide la Entidad desde el panel.
 */
final class DependenciaDeTramite
{
    /** A partir de cuántos puntos distintos la lista deja de ser un dato del trámite. */
    private const MAXIMO_DE_PUNTOS = 10;

    /**
     * Fragmento del nombre del punto (sin tildes ni mayúsculas) → nombre de la dependencia.
     *
     * @var array<string, string>
     */
    private const REGLAS = [
        'secretaria de educacion' => 'Educación',
        'secretaria de planeacion' => 'Planeación',
        'secretaria de gobierno' => 'Gobierno',
        'secretaria de hacienda' => 'Hacienda',
        'secretaria de salud' => 'Salud',
        'movilidad multimodal' => 'Movilidad',
        'catastro' => 'Catastro Multipropósito',
        'dadsa' => 'DADSA',
        'oficina juridica' => 'Dirección Jurídica',
    ];

    /** @return list<string> Las dependencias que esta regla sabe asignar. */
    public static function nombres(): array
    {
        return array_values(array_unique(self::REGLAS));
    }

    /**
     * @param  list<string>  $puntos  Los nombres de los puntos de atención del trámite.
     */
    public static function para(array $puntos): ?string
    {
        $distintos = array_unique(array_map(static fn (string $p): string => TextoBuscable::normalizar($p), $puntos));
        if ($distintos === [] || count($distintos) > self::MAXIMO_DE_PUNTOS) {
            return null;
        }

        $encontradas = [];
        foreach ($distintos as $punto) {
            foreach (self::REGLAS as $fragmento => $dependencia) {
                if (str_contains($punto, $fragmento)) {
                    $encontradas[$dependencia] = true;
                    break;
                }
            }
        }

        return count($encontradas) === 1 ? (string) array_key_first($encontradas) : null;
    }
}
