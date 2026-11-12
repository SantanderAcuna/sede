<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un trámite tal como lo ve quien lo gestiona: la misma ficha que publica el sitio más lo que
 * hace falta para editarla (código, estado, origen y qué le falta para publicarse).
 *
 * @mixin Tramite
 */
final class TramitePanelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Tramite $tramite */
        $tramite = $this->resource;

        $problemas = $tramite->problemasParaPublicar();
        $publico = Tramite::query()->publicados()->whereKey($tramite->getKey())->exists();

        return (new TramiteResource($tramite))->toArray($request) + [
            'codigo' => $tramite->codigo,
            'estado' => $publico ? 'publicado' : 'borrador',
            'origen' => $tramite->esDelPanel() || $tramite->procedencia_fuente === Tramite::FUENTE_PANEL ? 'panel' : 'fuente',
            'completo' => $problemas === [],
            'problemas' => $problemas,
        ];
    }
}
