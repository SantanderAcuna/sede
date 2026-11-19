<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Acceso\Huella;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Deja constancia de las ESCRITURAS del panel que se rechazaron: 403 (sin permiso), 422 (datos inválidos o regla
 * de negocio) y 409 (el contenido había cambiado).
 *
 * Mira la respuesta ya armada, así que cubre por igual lo que sale de una excepción (`can:` o `Gate::authorize`)
 * y lo que un controlador devuelve a propósito. Solo guarda los NOMBRES de los campos que fallaron, nunca lo que
 * la persona escribió. Sin sesión (401) no hay a quién atribuirlo y no se registra; una lectura sin permiso
 * tampoco, porque la interfaz puede sondear pantallas y llenaría la bitácora de ruido.
 *
 * Si guardar la constancia falla, la respuesta NO cambia: se anota en el registro de la aplicación.
 */
final class AuditarRechazosDelPanel
{
    private const ACCIONES = [
        403 => 'acceso_denegado',
        422 => 'datos_rechazados',
        409 => 'conflicto_rechazado',
    ];

    public function handle(Request $peticion, Closure $siguiente): Response
    {
        $respuesta = $siguiente($peticion);

        $estado = $respuesta->getStatusCode();
        $accion = self::ACCIONES[$estado] ?? null;
        if ($accion === null || $peticion->isMethodSafe()) {
            return $respuesta;
        }

        $actor = $peticion->user();
        if (! $actor instanceof User) {
            return $respuesta;
        }

        try {
            $ruta = $peticion->route();
            $uri = $ruta?->uri() ?? $peticion->path();
            $registro = $this->registro($peticion);

            Huella::registrar($actor, $accion, $this->recurso($uri), null, array_filter([
                'metodo' => $peticion->method(),
                'ruta' => $uri,
                'estado' => $estado,
                'registro' => $registro,
                'campos' => $estado === 422 ? $this->campos($respuesta) : null,
            ], static fn (mixed $v): bool => $v !== null));
        } catch (Throwable $e) {
            Log::warning('No se pudo guardar la huella de una petición rechazada.', ['estado' => $estado, 'exception' => $e]);
        }

        return $respuesta;
    }

    /** El módulo: el segmento que sigue a `panel/`. */
    private function recurso(string $uri): string
    {
        return preg_match('#panel/([a-z0-9-]+)#', $uri, $m) === 1 ? $m[1] : 'panel';
    }

    /** El identificador del registro de la ruta (uuid, slug o id), si lo hay. */
    private function registro(Request $peticion): ?string
    {
        foreach (['uuid', 'slug', 'id', 'modelUuid'] as $parametro) {
            $valor = $peticion->route($parametro);
            if (is_scalar($valor) && (string) $valor !== '') {
                return mb_substr((string) $valor, 0, 100);
            }
        }

        return null;
    }

    /** @return list<string> */
    private function campos(Response $respuesta): array
    {
        if (! $respuesta instanceof JsonResponse) {
            return [];
        }
        $cuerpo = $respuesta->getData(true);
        $errores = is_array($cuerpo) ? ($cuerpo['errors'] ?? null) : null;

        return is_array($errores) ? array_map('strval', array_slice(array_keys($errores), 0, 30)) : [];
    }
}
