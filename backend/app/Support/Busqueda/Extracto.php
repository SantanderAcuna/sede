<?php

declare(strict_types=1);

namespace App\Support\Busqueda;

/**
 * El extracto de un resultado: el principio del texto, sin partir palabras.
 *
 * El contrato lo describe así («principio del texto del contenido, recortado sin
 * partir palabras») y es lo único que promete: no es el fragmento donde está la
 * coincidencia, porque marcarlo exigiría devolver HTML o posiciones y el contrato
 * declara texto plano.
 */
final class Extracto
{
    /**
     * @return string|null Nulo si no hay texto: el contrato lo admite y es más
     *                     honesto que un extracto inventado.
     */
    public static function recortar(?string $texto, int $maximo): ?string
    {
        if ($texto === null) {
            return null;
        }

        // Los textos de la fuente oficial pueden traer etiquetas y saltos de línea;
        // el extracto es una línea de texto plano.
        $plano = trim((string) preg_replace('/\s+/u', ' ', strip_tags($texto)));

        if ($plano === '') {
            return null;
        }

        if (mb_strlen($plano) <= $maximo) {
            return $plano;
        }

        $corte = mb_substr($plano, 0, max(1, $maximo - 1));
        $espacio = mb_strrpos($corte, ' ');

        return rtrim($espacio === false ? $corte : mb_substr($corte, 0, $espacio), " \t,;:.").'…';
    }
}
