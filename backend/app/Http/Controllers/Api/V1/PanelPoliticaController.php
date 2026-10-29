<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPoliticaRequest;
use App\Models\Politica;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Edición de las políticas desde el panel (T-20). Requiere `politicas.gestionar`.
 *
 * @tags Panel — Políticas
 */
final class PanelPoliticaController extends Controller
{
    /**
     * Las políticas con su estado: si están completas y qué les falta.
     *
     * @operationId listarPoliticasPanel
     */
    public function index(): JsonResponse
    {
        $orden = array_keys(Politica::COMPONENTES);

        $politicas = Politica::query()->get()
            ->sortBy(static fn (Politica $p): int => (int) array_search($p->slug, $orden, true))
            ->values()
            ->map(static fn (Politica $p): array => self::elemento($p))
            ->all();

        return ApiResponse::ok($politicas);
    }

    /**
     * Guarda cambios en una política.
     *
     * Si cambia el texto y no se sube la versión, la versión sube sola: un texto
     * distinto es una versión distinta (y, en cookies, vuelve a pedir el
     * consentimiento). Si el resultado no queda completo —un apartado vacío, o un
     * acto administrativo incompleto al quitar la marca de ejemplo— no se guarda.
     *
     * @operationId actualizarPolitica
     */
    public function update(ActualizarPoliticaRequest $peticion, string $slug): JsonResponse
    {
        $politica = Politica::query()->where('slug', $slug)->first();
        if ($politica === null) {
            return ApiResponse::notFound('La política no existe.');
        }

        $datos = $peticion->validated();
        $textoAntes = json_encode($politica->secciones, JSON_THROW_ON_ERROR);

        $politica->fill($datos);

        $textoCambio = $textoAntes !== json_encode($politica->secciones, JSON_THROW_ON_ERROR);
        if ($textoCambio && ! array_key_exists('version', $datos)) {
            $politica->version++;
        }

        $problemas = $politica->problemas();
        if ($problemas !== []) {
            return ApiResponse::validationError(['politica' => $problemas]);
        }

        $politica->save();

        return ApiResponse::ok(self::elemento($politica->refresh()), 'Política actualizada correctamente.');
    }

    /**
     * La política tal como la ve el panel: el documento público más lo que hace
     * falta para editarla (el `uuid` con que se le adjunta el documento oficial,
     * los apartados exigidos y qué le falta).
     *
     * @return array<string, mixed>
     */
    private static function elemento(Politica $politica): array
    {
        $problemas = $politica->problemas();

        return $politica->comoArreglo() + [
            'uuid' => $politica->uuid,
            'componentes' => Politica::COMPONENTES[$politica->slug] ?? [],
            'completa' => $problemas === [],
            'problemas' => $problemas,
        ];
    }
}
