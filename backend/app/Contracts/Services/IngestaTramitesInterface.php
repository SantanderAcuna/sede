<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Exceptions\FuenteNoDisponible;

/**
 * La ingesta de la ficha oficial de los trámites.
 *
 * El servicio existe para que el comando no decida nada: recorre los códigos, pide
 * cada ficha, la mapea y deja el catálogo actualizado, devolviendo lo que pasó para
 * que el comando lo imprima. La regla de qué se publica vive en `MapeoFicha` y la
 * de qué se puede reescribir, en el repositorio del punto de control.
 */
interface IngestaTramitesInterface
{
    /**
     * Trae la ficha oficial de cada código y actualiza el catálogo.
     *
     * **Es reanudable.** Un código cuyo punto de control esté en `completo` no se
     * vuelve a pedir: es lo que permite cortar la ejecución por el límite de tasa
     * de la fuente y retomarla después sin repetir lo que ya costó.
     *
     * **Es idempotente y no destructiva.** Se escribe con `updateOrCreate` por
     * código, y un trámite cuya procedencia no sea la de la fuente —porque lo
     * corrigió una persona— no se toca.
     *
     * @param  list<string>  $codigos  Los códigos del catálogo, en la forma `T#####`.
     * @param  (callable(string): void)|null  $aviso  Cómo contar el avance. Inyectable para
     *                                                que el comando imprima y una prueba no.
     * @return array{
     *     pedidos: int,
     *     reanudados: int,
     *     traidos: int,
     *     creados: int,
     *     actualizados: int,
     *     protegidos: int,
     *     completos: int,
     *     incompletos: list<array{codigo: string, faltan: list<string>}>,
     *     faltantes_por_campo: array<string, int>,
     *     fallidos: list<array{codigo: string, motivo: string}>,
     *     esperas_por_tasa: int,
     * }
     *
     * @throws FuenteNoDisponible
     */
    public function ejecutar(array $codigos, ?callable $aviso = null): array;

    /**
     * Olvida el avance para volver a traer el catálogo entero.
     *
     * @return int Cuántos puntos de control se borraron.
     */
    public function olvidarAvance(): int;
}
