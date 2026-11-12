<?php

declare(strict_types=1);

namespace App\Support\Noticias;

use App\Support\Html\SanitizadorHtml;

/**
 * El cuerpo de una noticia: HTML saneado.
 *
 * Las noticias anteriores al editor son texto plano —párrafos separados por una línea
 * en blanco— y se leen como HTML sin perderse. Aquí vive todo lo que sabe pasar de un
 * formato al otro y decidir si hay texto; sanear (quitar lo peligroso) es de
 * `SanitizadorHtml`, y `limpio()` junta las dos cosas.
 */
final class ContenidoNoticia
{
    /** ¿Ya es HTML? Lo es si empieza por una etiqueta (lo que produce siempre el editor). */
    public static function esHtml(string $contenido): bool
    {
        return preg_match('/^\s*<[a-z!\/]/i', $contenido) === 1;
    }

    /** Texto plano → párrafos HTML escapados; el HTML se devuelve igual (aún sin sanear). */
    public static function aHtml(string $contenido): string
    {
        $contenido = trim(str_replace("\r\n", "\n", $contenido));
        if ($contenido === '') {
            return '';
        }
        if (self::esHtml($contenido)) {
            return $contenido;
        }

        $parrafos = [];
        foreach (preg_split('/\n{2,}/', $contenido) ?: [] as $bloque) {
            $bloque = trim($bloque);
            if ($bloque === '') {
                continue;
            }
            $parrafos[] = '<p>'.str_replace("\n", '<br>', htmlspecialchars($bloque, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')).'</p>';
        }

        return implode('', $parrafos);
    }

    /** Lo que se guarda y lo que se entrega: HTML convertido y saneado. */
    public static function limpio(string $contenido, ?SanitizadorHtml $sanitizador = null): string
    {
        return ($sanitizador ?? new SanitizadorHtml)->limpiar(self::aHtml($contenido));
    }

    /** El texto sin etiquetas: párrafos separados por una línea en blanco. */
    public static function textoPlano(string $contenido): string
    {
        $html = self::aHtml($contenido);
        $html = (string) preg_replace('#<br\s*/?>#i', "\n", $html);
        $html = (string) preg_replace('#</(p|h[1-6]|li|ul|ol|blockquote)>#i', "\n\n", $html);
        $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = str_replace("\u{00A0}", ' ', $texto);
        $texto = (string) preg_replace("/[ \t]+\n/", "\n", $texto);
        $texto = (string) preg_replace("/\n{3,}/", "\n\n", $texto);

        return trim($texto);
    }

    /** ¿No hay ni una letra que leer? (`<p></p>`, solo saltos, solo imágenes). */
    public static function sinTexto(string $contenido): bool
    {
        return self::textoPlano($contenido) === '';
    }
}
