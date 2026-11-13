<?php

declare(strict_types=1);

namespace App\Support\Tramites;

use App\Models\Tramite;

/**
 * Escribe la ficha de un trámite en sus tablas hijas a partir de la forma que produce
 * `ConversorFichaJson`.
 *
 * Existe para que el guardado del panel y la carga inicial del catálogo usen **exactamente la
 * misma regla**: borra la ficha anterior y reinserta pasos, requisitos (con su paso), documentos,
 * puntos de atención, normas, canales, cuentas y audiencias. Separarla aquí evita dos copias que
 * terminen divergiendo.
 */
final class EscritorFichaRelacional
{
    /**
     * @param  array{
     *     pasos: list<array{orden: int, titulo: string, descripcion: string|null}>,
     *     requisitos: list<array{orden: int, tipo: string, descripcion: string, obligatorio: bool, nota: string|null, url: string|null, paso: int|null}>,
     *     documentos: list<array<string, mixed>>,
     *     puntos_atencion: list<array<string, mixed>>,
     *     normativa: list<array<string, mixed>>,
     *     canales_consulta_estado: list<array<string, mixed>>,
     *     cuentas: list<array<string, mixed>>,
     *     audiencias: list<string>
     * }  $ficha
     */
    public static function escribir(Tramite $tramite, array $ficha): void
    {
        $tramite->pasos()->delete();
        $tramite->requisitos()->delete();
        $tramite->documentos()->delete();
        $tramite->puntosAtencion()->delete();
        $tramite->normas()->delete();
        $tramite->canales()->delete();
        $tramite->audiencias()->delete();
        $tramite->cuentas()->delete();

        $idPorOrden = [];
        foreach ($ficha['pasos'] as $paso) {
            $fila = $tramite->pasos()->create($paso);
            $idPorOrden[$paso['orden']] = $fila->id;
        }
        foreach ($ficha['requisitos'] as $requisito) {
            $paso = $requisito['paso'];
            unset($requisito['paso']);
            $requisito['paso_id'] = $paso === null ? null : ($idPorOrden[$paso] ?? null);
            $tramite->requisitos()->create($requisito);
        }
        foreach (array_values($ficha['documentos']) as $i => $documento) {
            $tramite->documentos()->create(['orden' => $i + 1] + $documento);
        }
        foreach (array_values($ficha['puntos_atencion']) as $i => $punto) {
            $tramite->puntosAtencion()->create(['orden' => $i + 1] + $punto);
        }
        foreach (array_values($ficha['normativa']) as $i => $norma) {
            $tramite->normas()->create(['orden' => $i + 1] + $norma);
        }
        foreach (array_values($ficha['canales_consulta_estado']) as $i => $canal) {
            $tramite->canales()->create(['orden' => $i + 1] + $canal);
        }
        foreach (array_values($ficha['cuentas']) as $i => $cuenta) {
            $tramite->cuentas()->create(['orden' => $i + 1] + $cuenta);
        }
        foreach ($ficha['audiencias'] as $grupo) {
            $tramite->audiencias()->create(['grupo' => $grupo]);
        }
    }
}
