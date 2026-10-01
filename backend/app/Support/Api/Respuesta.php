<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * El sobre plano de la API.
 *
 * Todas las respuestas del contrato tienen la misma forma
 * —`{success, message, data, errors}`— y las colecciones añaden `meta` y
 * `links` en el nivel superior. Se construye en un solo sitio para que la forma
 * no dependa de quién escriba el controlador: un `error` que viaje sin `data` es
 * un cliente que no puede leer la respuesta, y el contrato lo prohíbe.
 *
 * `message` viaja siempre, aunque sea nulo: el contrato lo declara y un cliente
 * que compruebe su presencia no debe encontrarse la clave ausente.
 */
final class Respuesta
{
    /**
     * Un recurso individual.
     *
     * @param  array<string, mixed>|JsonResource|null  $dato
     */
    public static function objeto(array|JsonResource|null $dato, ?string $mensaje = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $dato instanceof JsonResource ? $dato->resolve() : $dato,
            'errors' => null,
        ]);
    }

    /**
     * Una colección paginada.
     *
     * `meta` lleva las siete claves del paginador y `links` las cuatro del
     * contrato. Se escriben a mano en vez de volcar el paginador entero porque el
     * paginador de Eloquent trae claves de más —y enlaces con nombres que el
     * contrato no declara—, y el contrato fija lo que viaja.
     *
     * @param  LengthAwarePaginator<int, mixed>  $pagina
     */
    public static function coleccion(ResourceCollection $coleccion, LengthAwarePaginator $pagina): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => null,
            'data' => $coleccion->resolve(),
            'meta' => [
                'current_page' => $pagina->currentPage(),
                'from' => $pagina->firstItem(),
                'last_page' => $pagina->lastPage(),
                'path' => $pagina->path(),
                'per_page' => $pagina->perPage(),
                'to' => $pagina->lastItem(),
                'total' => $pagina->total(),
            ],
            'links' => [
                'first' => $pagina->url(1),
                'last' => $pagina->url($pagina->lastPage()),
                'prev' => $pagina->previousPageUrl(),
                'next' => $pagina->nextPageUrl(),
            ],
            'errors' => null,
        ]);
    }

    /**
     * Un error.
     *
     * @param  array<string, mixed>|null  $errores
     */
    public static function error(string $mensaje, int $estado, ?array $errores = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
            'data' => null,
            'errors' => $errores,
        ], $estado);
    }
}
