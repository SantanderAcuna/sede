<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Politica;
use App\Services\Politicas\PoliticaDocumentos;
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
    public function __invoke(string $slug, string $formato, PoliticaDocumentos $documentos): Response
    {
        $politica = Politica::query()->where('slug', $slug)->firstOrFail();

        [$contenido, $tipo] = $formato === 'odt'
            ? [$documentos->odt($politica), PoliticaDocumentos::TIPO_ODT]
            : [$documentos->pdf($politica), PoliticaDocumentos::TIPO_PDF];

        return response($contenido, 200, [
            'Content-Type' => $tipo,
            'Content-Disposition' => sprintf('attachment; filename="%s-v%d.%s"', $politica->slug, $politica->version, $formato),
            'Cache-Control' => 'public, max-age=300',
            'Last-Modified' => $politica->updated_at->toRfc7231String(),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
