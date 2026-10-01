<?php

declare(strict_types=1);

namespace App\Support\Tramites;

use Illuminate\Support\Str;

/**
 * El slug de un trámite: su nombre, en minúsculas y sin tildes.
 *
 * Cuando dos trámites darían el mismo slug —los títulos de la fuente no están
 * normalizados—, el segundo lleva su código pegado. La decisión se toma mirando
 * también lo que ya hay en la base, para que una segunda ejecución no renombre lo
 * publicado: un slug es la dirección de una ficha, y cambiarlo rompe los enlaces
 * que el ciudadano ya tenga guardados.
 *
 * Está compartido entre el sembrador y la ingesta por la misma razón que la
 * conversión del término: los dos escriben en la misma columna del mismo catálogo,
 * y dos reglas distintas darían dos direcciones para el mismo trámite según por
 * dónde hubiera entrado.
 */
final class SlugCatalogo
{
    /**
     * @param  array<string, string>  $usados  Slug => código ya ocupado. Se actualiza.
     */
    public static function para(?string $nombre, string $codigo, array &$usados): string
    {
        $slug = mb_substr(rtrim(Str::slug((string) $nombre), '-'), 0, 160);

        if ($slug === '') {
            $slug = mb_strtolower($codigo);
        }

        if (($usados[$slug] ?? $codigo) !== $codigo) {
            $slug = mb_substr($slug, 0, 140).'-'.mb_strtolower($codigo);
        }

        $usados[$slug] = $codigo;

        return $slug;
    }
}
