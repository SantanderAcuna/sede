<?php

declare(strict_types=1);

namespace App\Support\Html;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Saneador de HTML por lista blanca. Control principal contra XSS almacenado:
 * se aplica al ESCRIBIR; el sitio vuelve a sanear al mostrar (defensa en profundidad).
 */
final class SanitizadorHtml
{
    /** Resto de la URL de una imagen propia: lo que sube el panel a `file_media` (colección «contenido»). */
    private const RESTO_IMAGEN = 'file_media/[a-z0-9_]+/contenido/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.(?:png|jpg|webp)';

    private string $patronImagen;

    private HtmlSanitizer $saneador;

    /**
     * @param  list<string>  $prefijosImagen  Inicios de URL admitidos para imágenes propias
     *                                        (p. ej. «/storage/» o la URL base del disco remoto).
     */
    public function __construct(array $prefijosImagen = ['/storage/'])
    {
        $alternativas = array_map(static fn (string $p): string => preg_quote($p, '#'), $prefijosImagen);
        $this->patronImagen = '#^(?:'.implode('|', $alternativas).')'.self::RESTO_IMAGEN.'$#';

        $config = (new HtmlSanitizerConfig)
            ->allowElement('h2')->allowElement('h3')
            ->allowElement('p')->allowElement('br')
            ->allowElement('strong')->allowElement('em')
            ->allowElement('ul')->allowElement('ol')->allowElement('li')
            ->allowElement('blockquote')
            ->allowElement('a', ['href', 'target', 'rel'])
            ->allowElement('img', ['src', 'alt'])
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowRelativeLinks(false)
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->forceHttpsUrls(false)
            ->allowMediaSchemes(['https'])
            ->allowRelativeMedias(true)
            ->withMaxInputLength(500_000);

        $this->saneador = new HtmlSanitizer($config);
    }

    public function limpiar(string $html): string
    {
        $html = str_replace("\0", '', $html);
        $html = (string) preg_replace('/[\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200E}\x{200F}]/u', '', $html);

        $limpio = $this->saneador->sanitize($html);

        return $this->soloImagenesPropias($limpio);
    }

    /** Quita toda <img> que no sea propia o no tenga alt no vacío. */
    private function soloImagenesPropias(string $html): string
    {
        return (string) preg_replace_callback('#<img\b[^>]*>#i', function (array $m): string {
            $src = preg_match('#\ssrc="([^"]*)"#i', $m[0], $s) ? html_entity_decode($s[1]) : '';
            $alt = preg_match('#\salt="([^"]*)"#i', $m[0], $a) ? trim($a[1]) : '';

            return preg_match($this->patronImagen, $src) === 1 && $alt !== '' ? $m[0] : '';
        }, $html);
    }
}
