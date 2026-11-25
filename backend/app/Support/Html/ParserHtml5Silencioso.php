<?php

declare(strict_types=1);

namespace App\Support\Html;

use Dom\HTMLDocument;
use Dom\Node;
use Symfony\Component\HtmlSanitizer\Parser\ParserInterface;

/**
 * El parser HTML5 nativo de PHP (`Dom\HTMLDocument`), igual que `NativeParser` de Symfony, pero sin avisos.
 *
 * Una entrada mal formada (justo lo que llega en un intento de XSS) hace que el parser HTML5 informe de sus
 * errores de análisis como `E_WARNING`. Symfony los tapa con `@`, pero el `@` no evita el aviso: PHP lo emite
 * igual con `error_reporting` en 4437 durante la llamada, y cualquier manejador de errores lo recibe. En las
 * pruebas, Collision lo pintaba como «!»; en producción, cualquier manejador que no mire `error_reporting()`
 * lo trataría como un error.
 *
 * Aquí se pide al parser que no informe (`LIBXML_NOERROR`, admitido por `Dom\HTMLDocument::createFromString`).
 * El análisis es el mismo algoritmo del estándar HTML5 y arma el mismo árbol: lo comprueba
 * `SanitizadorHtmlTest::test_el_parser_silencioso_arma_el_mismo_arbol_que_el_nativo` con todo el corpus.
 *
 * La lógica de contexto es la de `Symfony\Component\HtmlSanitizer\Parser\NativeParser` (symfony/html-sanitizer
 * 8.1): para esos elementos envolver la entrada no sirve, porque el parser ignora su etiqueta de inicio,
 * convierte «image» en «img» o lee «</plaintext>» como texto.
 */
final class ParserHtml5Silencioso implements ParserInterface
{
    private const CONTEXTOS_SIN_ENVOLTORIO = ['caption', 'col', 'colgroup', 'image', 'plaintext', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr'];

    public function parse(string $html, string $context = 'body'): ?Node
    {
        if (in_array($context, self::CONTEXTOS_SIN_ENVOLTORIO, true)) {
            $element = HTMLDocument::createEmpty()->createElement($context);
            $element->innerHTML = $html;
        } else {
            $documento = HTMLDocument::createFromString(sprintf('<!DOCTYPE html><%s>%s</%1$s>', $context, $html), LIBXML_NOERROR, 'UTF-8');
            $element = $documento->getElementsByTagName($context)->item(0);
        }

        return $element !== null && $element->hasChildNodes() ? $element : null;
    }
}
