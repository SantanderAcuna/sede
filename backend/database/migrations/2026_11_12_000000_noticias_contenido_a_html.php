<?php

declare(strict_types=1);

use App\Support\Html\SanitizadorHtml;
use App\Support\Noticias\ContenidoNoticia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El cuerpo de las noticias pasa de texto plano a HTML saneado (editor del panel).
 *
 * La migración que creó la tabla decía que guardar HTML era un XSS almacenado «y el repo
 * no tiene un saneador aprobado». Ya lo hay (`SanitizadorHtml`, lista blanca estricta,
 * probado con un corpus de ataques) y la titular pidió el editor, así que el cuerpo
 * puede ser HTML. Las filas existentes se convierten UNA vez: cada párrafo a `<p>`.
 *
 * Reversible: `down()` devuelve el texto plano (párrafos separados por una línea en
 * blanco). Si ya hay noticias escritas con formato (títulos, negritas, listas…), al
 * deshacer se pierde ese formato —no el texto—; es la única pérdida.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sanitizador = new SanitizadorHtml;

        DB::table('noticias')->orderBy('id')->chunkById(200, function ($filas) use ($sanitizador): void {
            foreach ($filas as $fila) {
                $nuevo = ContenidoNoticia::limpio((string) $fila->contenido, $sanitizador);
                if ($nuevo !== $fila->contenido) {
                    DB::table('noticias')->where('id', $fila->id)->update(['contenido' => $nuevo]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('noticias')->orderBy('id')->chunkById(200, function ($filas): void {
            foreach ($filas as $fila) {
                $plano = ContenidoNoticia::textoPlano((string) $fila->contenido);
                if ($plano !== $fila->contenido) {
                    DB::table('noticias')->where('id', $fila->id)->update(['contenido' => $plano]);
                }
            }
        });
    }
};
