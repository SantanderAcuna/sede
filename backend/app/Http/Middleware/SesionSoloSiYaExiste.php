<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpFoundation\Response;

/**
 * Carga la sesión del panel SOLO si la petición ya trae su cookie de sesión.
 *
 * Las lecturas públicas quitan a propósito el middleware de «frontend con estado» de Sanctum: una visita
 * anónima no puede abrir sesión ni recibir cookies (AC-26.2). Pero algunas rutas públicas muestran más a
 * quien edita contenido —la imagen de una noticia sin publicar, para su vista previa en el editor—. Esta barrera
 * deja intacto lo anónimo (sin cookie de sesión no se hace nada) y, con cookie, aplica el mismo mecanismo que el panel.
 */
final class SesionSoloSiYaExiste
{
    public function __construct(private readonly EnsureFrontendRequestsAreStateful $conEstado) {}

    public function handle(Request $peticion, Closure $siguiente): Response
    {
        if (! $peticion->cookies->has((string) config('session.cookie'))) {
            return $siguiente($peticion);
        }

        return $this->conEstado->handle($peticion, $siguiente);
    }
}
