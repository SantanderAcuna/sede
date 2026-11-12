<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Html;

use App\Support\Html\SanitizadorHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Control principal contra XSS almacenado: lista blanca estricta, al ESCRIBIR.
 */
final class SanitizadorHtmlTest extends TestCase
{
    private function limpiar(string $html): string
    {
        return (new SanitizadorHtml)->limpiar($html);
    }

    /** @return array<string, array{string}> */
    public static function ataques(): array
    {
        return [
            'script' => ['<p>a</p><script>alert(1)</script>'],
            'onerror img' => ['<img src="x" onerror="alert(1)">'],
            'onclick' => ['<p onclick="alert(1)">a</p>'],
            'javascript href' => ['<a href="javascript:alert(1)">x</a>'],
            'javascript mayusculas' => ['<a href="JaVaScRiPt:alert(1)">x</a>'],
            'javascript con tab' => ["<a href=\"java\tscript:alert(1)\">x</a>"],
            'javascript con entidad' => ['<a href="&#106;avascript:alert(1)">x</a>'],
            'javascript con espacio inicial' => ['<a href=" javascript:alert(1)">x</a>'],
            'data html' => ['<a href="data:text/html,<script>alert(1)</script>">x</a>'],
            'vbscript' => ['<a href="vbscript:msgbox(1)">x</a>'],
            'svg onload' => ['<svg onload="alert(1)"><circle/></svg>'],
            'svg script' => ['<svg><script>alert(1)</script></svg>'],
            'iframe' => ['<iframe src="https://evil.test"></iframe>'],
            'object' => ['<object data="x.swf"></object>'],
            'embed' => ['<embed src="x.swf">'],
            'form' => ['<form action="https://evil.test"><input name="p"></form>'],
            'style tag' => ['<style>body{display:none}</style><p>a</p>'],
            'style attr' => ['<p style="background:url(javascript:alert(1))">a</p>'],
            'meta refresh' => ['<meta http-equiv="refresh" content="0;url=https://evil.test">'],
            'base' => ['<base href="https://evil.test/">'],
            'link tag' => ['<link rel="stylesheet" href="https://evil.test/x.css">'],
            'mxss noscript' => ['<noscript><p title="</noscript><img src=x onerror=alert(1)>"></noscript>'],
            'mxss math' => ['<math><mtext><table><mglyph><style><img src=x onerror=alert(1)>'],
            'mxss svg style' => ['<svg></p><style><a id="</style><img src=1 onerror=alert(1)>">'],
            'img src javascript' => ['<img src="javascript:alert(1)">'],
            'img data svg' => ['<img src="data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=">'],
            'comentario condicional' => ['<!--[if IE]><script>alert(1)</script><![endif]-->'],
            'nulo en etiqueta' => ["<scr\0ipt>alert(1)</scr\0ipt>"],
            'etiqueta rota' => ['<<script>alert(1)//<</script>'],
            'data-atributo' => ['<p data-x="1" data-onload="alert(1)">a</p>'],
            'target blank sin rel' => ['<a href="https://a.test" target="_blank">x</a>'],
            'bidi override' => ["<p>\u{202E}gpj.exe</p>"],
        ];
    }

    #[DataProvider('ataques')]
    public function test_el_corpus_hostil_no_deja_nada_ejecutable(string $entrada): void
    {
        $salida = strtolower($this->limpiar($entrada));

        foreach (['<script', 'onerror', 'onload', 'onclick', 'javascript:', 'vbscript:', '<iframe', '<object',
            '<embed', '<form', '<style', '<meta', '<base', '<link', '<svg', '<math', 'style=', 'data-', 'data:text',
            'data:image/svg', "\0", "\u{202e}", '<!--'] as $prohibido) {
            $this->assertStringNotContainsString($prohibido, $salida, "Quedó «{$prohibido}» en: {$salida}");
        }
    }

    public function test_los_enlaces_externos_llevan_rel_seguro(): void
    {
        $salida = $this->limpiar('<a href="https://a.test" target="_blank">x</a>');

        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $salida);
    }

    public function test_conserva_el_formato_permitido(): void
    {
        $entrada = '<h2>Título</h2><p>Un <strong>texto</strong> con <em>énfasis</em> y <a href="https://santamarta.gov.co">enlace</a>.</p>'
            .'<ul><li>uno</li><li>dos</li></ul><ol><li>a</li></ol><blockquote><p>cita</p></blockquote>';

        $salida = $this->limpiar($entrada);

        foreach (['<h2>Título</h2>', '<strong>texto</strong>', '<em>énfasis</em>', '<ul>', '<ol>', '<blockquote>', 'href="https://santamarta.gov.co"'] as $esperado) {
            $this->assertStringContainsString($esperado, $salida);
        }
    }

    public function test_solo_deja_imagenes_propias_con_alt(): void
    {
        $uuid = '0b9e0a5e-3a52-4c43-9f0e-5a0f4a4e2c11';
        $s = new SanitizadorHtml(['/storage/', 'https://cdn.santamarta.gov.co/']);
        $ruta = "file_media/app_models_noticia/contenido/{$uuid}.webp";

        $this->assertStringContainsString('<img', $s->limpiar("<img src=\"/storage/{$ruta}\" alt=\"Alcalde\">"));
        $this->assertStringContainsString('<img', $s->limpiar("<img src=\"https://cdn.santamarta.gov.co/{$ruta}\" alt=\"Alcalde\">"));
        $this->assertStringNotContainsString('<img', $s->limpiar('<img src="https://evil.test/p.png" alt="x">'));
        $this->assertStringNotContainsString('<img', $s->limpiar("<img src=\"https://evil.test/storage/{$ruta}\" alt=\"x\">"));
        $this->assertStringNotContainsString('<img', $s->limpiar("<img src=\"/storage/{$ruta}\">"));
        $this->assertStringNotContainsString('<img', $s->limpiar("<img src=\"/storage/../../etc/{$uuid}.webp\" alt=\"x\">"));
        $this->assertStringNotContainsString('<img', $s->limpiar("<img src=\"/storage/file_media/x/contenido/{$uuid}.svg\" alt=\"x\">"));
    }

    public function test_es_idempotente(): void
    {
        $una = $this->limpiar('<p>a <a href="https://a.test" target="_blank">b</a></p><script>x</script>');

        $this->assertSame($una, $this->limpiar($una));
    }

    public function test_texto_plano_y_vacio(): void
    {
        $this->assertSame('', $this->limpiar(''));
        $this->assertSame('<p>hola &amp; adiós</p>', $this->limpiar('<p>hola &amp; adiós</p>'));
    }
}
