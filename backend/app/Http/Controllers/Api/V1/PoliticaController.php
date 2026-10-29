<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Politica;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Las políticas de la sede, públicas (T-20).
 *
 * @tags Políticas
 */
final class PoliticaController extends Controller
{
    /**
     * Lista las cinco políticas, en el orden del pie de página, sin el texto.
     *
     * @operationId listarPoliticas
     */
    public function index(): JsonResponse
    {
        $orden = array_keys(Politica::COMPONENTES);

        $politicas = Politica::query()->get()
            ->sortBy(static fn (Politica $p): int => (int) array_search($p->slug, $orden, true))
            ->values()
            ->map(static function (Politica $p): array {
                $doc = $p->comoArreglo();
                unset($doc['secciones']);

                return $doc;
            })
            ->all();

        return ApiResponse::ok($politicas);
    }

    /**
     * Una política completa: versión, vigencia, acto y apartados.
     *
     * @operationId obtenerPolitica
     */
    public function show(string $slug): JsonResponse
    {
        $politica = Politica::query()->where('slug', $slug)->first();

        return $politica === null
            ? ApiResponse::notFound('La política no existe.')
            : ApiResponse::ok($politica->comoArreglo());
    }
}
