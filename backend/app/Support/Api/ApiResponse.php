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
 *
 * Los ocho ayudantes —`ok`, `created`, `error`, `notFound`, `unauthorized`,
 * `forbidden`, `validationError` y `tooManyRequests`— son los que la guía
 * maestra fija con nombre y estado. Existen para que el código de estado no se
 * escriba a mano en cada punto: un `404` mal tecleado en un `render` de
 * excepciones no se nota hasta que un cliente recibe un `200` con un error
 * dentro.
 */
final class ApiResponse
{
    /**
     * Una respuesta correcta con cuerpo (200).
     */
    public static function ok(mixed $data = null, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ]);
    }

    /**
     * Un recurso recién creado (201).
     *
     * El mensaje por defecto se escribe aquí y no en el controlador para que
     * todas las creaciones de la API contesten lo mismo; el controlador sólo lo
     * cambia cuando tiene algo más útil que decir.
     */
    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? 'Recurso creado.',
            'data' => $data,
            'errors' => null,
        ], 201);
    }

    /**
     * Un error.
     *
     * El estado se pide explícito y con `400` por defecto a propósito: obliga a
     * quien escribe la llamada a decidir el código, y el valor por defecto deja
     * claro que un error sin código es una petición mal formada y no un fallo del
     * servidor.
     *
     * @param  array<string, mixed>|null  $errors
     */
    public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }

    /**
     * El recurso pedido no existe (404).
     */
    public static function notFound(string $message = 'Recurso no encontrado.'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * La petición no trae una sesión válida (401).
     *
     * Se distingue de `forbidden` porque el cliente debe reaccionar distinto: un
     * `401` es «vuelve a entrar», un `403` es «entraste, pero esto no es tuyo».
     */
    public static function unauthorized(string $message = 'No autenticado.'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * La sesión es válida pero no alcanza para esta operación (403).
     */
    public static function forbidden(string $message = 'No autorizado.'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * La petición no pasó la validación (422).
     *
     * El mensaje es fijo y los detalles viajan en `errors`, una entrada por
     * campo: el cliente pinta los errores junto al campo que los produjo y no
     * tiene que interpretar una frase.
     *
     * @param  array<string, mixed>  $errors
     */
    public static function validationError(array $errors): JsonResponse
    {
        return self::error('Error de validación.', 422, $errors);
    }

    /**
     * Se superó el límite de peticiones (429).
     *
     * El estado lo fija la guía; el cliente lee `Retry-After` —expuesto en CORS—
     * para saber cuándo volver.
     */
    public static function tooManyRequests(string $message = 'Demasiadas solicitudes.'): JsonResponse
    {
        return self::error($message, 429);
    }

    /**
     * Un recurso individual.
     *
     * El `resolve()` no es decorativo: un `JsonResource` devuelto tal cual a
     * `response()->json()` arrastraría el envoltorio que Laravel aplica al
     * responder, y el sobre quedaría anidado. Se resuelve aquí para que el
     * `data` sea siempre el objeto plano que el contrato declara.
     *
     * @param  array<string, mixed>|JsonResource|null  $dato
     */
    public static function objeto(array|JsonResource|null $dato, ?string $mensaje = null): JsonResponse
    {
        return self::ok($dato instanceof JsonResource ? $dato->resolve() : $dato, $mensaje);
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
}
