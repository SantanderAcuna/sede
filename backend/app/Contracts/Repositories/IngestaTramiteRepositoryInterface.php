<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\IngestaTramite;

/**
 * El punto de control de la ingesta del catálogo.
 *
 * La reanudación se decide aquí y en ningún otro sitio. Si la comprobación de «ya
 * está hecho» viviera en el servicio que recorre los trámites, bastaría con
 * añadir otro recorrido para que una segunda pasada volviera a pedir los 124
 * trámites a una fuente que limita la tasa, sin que nada fallara.
 */
interface IngestaTramiteRepositoryInterface
{
    /**
     * Los códigos que ya se trajeron enteros.
     *
     * @return list<string>
     */
    public function completos(): array;

    /** La fila de control de un trámite, creada si no existe. */
    public function abrir(string $codigo): IngestaTramite;

    /** Anota que el trámite se trajo entero. */
    public function cerrar(string $codigo): void;

    /** Anota un intento fallido, con su motivo. */
    public function fallar(string $codigo, string $motivo): void;

    /** Cuántos intentos lleva acumulados un trámite. */
    public function intentos(string $codigo): int;

    /** Olvida todo el avance. Sólo para volver a traer el catálogo desde cero. */
    public function olvidar(): int;
}
