<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Noticias;

use App\Support\Noticias\ContenidoNoticia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * El cuerpo de una noticia es HTML saneado. Las noticias anteriores al editor son texto
 * plano (párrafos separados por una línea en blanco) y se leen como HTML sin perderse.
 */
final class ContenidoNoticiaTest extends TestCase
{
    public function test_el_texto_plano_pasa_a_parrafos_y_se_escapa(): void
    {
        $this->assertSame(
            '<p>Uno &amp; dos.</p><p>Tres &lt; cuatro.</p>',
            ContenidoNoticia::aHtml("Uno & dos.\n\nTres < cuatro."),
        );
    }

    public function test_los_saltos_simples_dentro_de_un_parrafo_se_conservan(): void
    {
        $this->assertSame('<p>Línea 1<br>Línea 2</p>', ContenidoNoticia::aHtml("Línea 1\nLínea 2"));
    }

    public function test_el_texto_plano_con_etiquetas_escritas_a_mano_no_se_interpreta_como_html_si_no_es_html(): void
    {
        // «a < b > c» no es una etiqueta.
        $this->assertSame('<p>si a &lt; b &gt; c entonces</p>', ContenidoNoticia::aHtml('si a < b > c entonces'));
    }

    public function test_el_html_se_deja_pasar(): void
    {
        $html = '<h2>Título</h2><p>Texto <strong>fuerte</strong></p>';

        $this->assertSame($html, ContenidoNoticia::aHtml($html));
    }

    public function test_vacio(): void
    {
        $this->assertSame('', ContenidoNoticia::aHtml(''));
        $this->assertSame('', ContenidoNoticia::aHtml("  \n\n  "));
    }

    /** @return array<string, array{string, bool}> */
    public static function vacios(): array
    {
        return [
            'cadena vacía' => ['', true],
            'solo espacios' => ["  \n ", true],
            'párrafo vacío' => ['<p></p>', true],
            'párrafo con espacios' => ['<p> &nbsp; </p>', true],
            'solo saltos' => ['<p><br></p><p><br></p>', true],
            'texto' => ['<p>Hola</p>', false],
            'texto plano' => ['Hola', false],
            'solo una imagen no es texto' => ['<p><img src="/api/v1/contenido/imagenes/0b9e0a5e-3a52-4c43-9f0e-5a0f4a4e2c11" alt="x"></p>', true],
        ];
    }

    #[DataProvider('vacios')]
    public function test_detecta_el_contenido_sin_texto(string $entrada, bool $vacio): void
    {
        $this->assertSame($vacio, ContenidoNoticia::sinTexto($entrada));
    }

    public function test_texto_plano_de_un_html(): void
    {
        $this->assertSame("Uno\n\nDos & tres", ContenidoNoticia::textoPlano('<h2>Uno</h2><p>Dos &amp; tres</p>'));
    }

    public function test_ida_y_vuelta_del_texto_plano(): void
    {
        $original = "Primer párrafo.\n\nSegundo & último.";

        $this->assertSame($original, ContenidoNoticia::textoPlano(ContenidoNoticia::aHtml($original)));
    }
}
