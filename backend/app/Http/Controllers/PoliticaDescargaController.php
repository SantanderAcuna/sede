<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Politica;
use App\Services\Politicas\PoliticaDocumentos;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Descarga de una política en ODT o PDF/A (T-20, AC-20.5).
 *
 * Vive fuera de `/api` a propósito: la dirección pública es
 * `/politicas/<slug>.odt` y `.pdf`, la que se enlaza desde la página de cada
 * política. nginx la envía aquí (ver `docker/nginx/conf.d/default.conf`).
 */
final class PoliticaDescargaController extends Controller
{
    public function __invoke(Request $peticion, string $slug, string $formato, PoliticaDocumentos $documentos): Response
    {
        $politica = Politica::query()->where('slug', $slug)->firstOrFail();

        $tipo = $formato === 'odt' ? PoliticaDocumentos::TIPO_ODT : PoliticaDocumentos::TIPO_PDF;

        // `private, no-cache`: el documento cambia cuando alguien edita la política
        // en el panel y no puede quedarse viejo en el borde ni en el navegador.
        // Cloudflare guarda por extensión (.pdf, .odt) y, con `public`, reescribía el
        // tiempo del navegador a 4 horas: la descarga habría seguido mostrando el
        // texto anterior. Con `no-cache` el navegador pregunta cada vez y el ETag
        // permite contestar 304 sin volver a generar el documento.
        $respuesta = response('', 200, [
            'Content-Type' => $tipo,
            'Content-Disposition' => sprintf('attachment; filename="%s-v%d.%s"', $politica->slug, $politica->version, $formato),
            'Cache-Control' => 'private, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $respuesta->setEtag(sha1(sprintf('%s|%s|%d|%d', $politica->slug, $formato, $politica->version, $politica->updated_at->getTimestamp())));
        $respuesta->setLastModified($politica->updated_at);

        if ($respuesta->isNotModified($peticion)) {
            return $respuesta;
        }

        return $respuesta->setContent(
            $formato === 'odt' ? $documentos->odt($politica) : $documentos->pdf($politica)
        );
    }
}
