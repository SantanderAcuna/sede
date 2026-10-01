<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\IngestaTramiteRepositoryInterface;
use App\Models\IngestaTramite;
use Illuminate\Support\Carbon;

/**
 * El punto de control de la ingesta, sobre Eloquent.
 */
final class IngestaTramiteRepository implements IngestaTramiteRepositoryInterface
{
    public function completos(): array
    {
        /** @var list<string> $codigos */
        $codigos = IngestaTramite::query()
            ->where('estado', IngestaTramite::COMPLETO)
            ->pluck('codigo')
            ->all();

        return $codigos;
    }

    public function abrir(string $codigo): IngestaTramite
    {
        // `firstOrCreate` y no `create`: reabrir un trámite que ya falló antes no
        // debe borrar su historial de intentos, porque es lo que permite decir en
        // el informe cuántas veces se intentó y no sólo que falló.
        return IngestaTramite::query()->firstOrCreate(
            ['codigo' => $codigo],
            ['estado' => IngestaTramite::PENDIENTE, 'intentos' => 0],
        );
    }

    public function cerrar(string $codigo): void
    {
        $control = $this->abrir($codigo);

        $control->forceFill([
            'estado' => IngestaTramite::COMPLETO,
            'error' => null,
            'intentos' => $control->intentos + 1,
            'terminado_en' => Carbon::now(),
        ])->save();
    }

    public function fallar(string $codigo, string $motivo): void
    {
        $control = $this->abrir($codigo);

        $control->forceFill([
            'estado' => IngestaTramite::FALLIDO,
            'error' => $motivo,
            'intentos' => $control->intentos + 1,
            // Un fallo no deja marca de terminado: es justo lo que hace que la
            // próxima ejecución lo vuelva a intentar.
            'terminado_en' => null,
        ])->save();
    }

    public function intentos(string $codigo): int
    {
        return (int) IngestaTramite::query()->where('codigo', $codigo)->value('intentos');
    }

    public function olvidar(): int
    {
        return IngestaTramite::query()->delete();
    }
}
