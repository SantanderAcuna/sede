<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las cabeceras de seguridad de todas las respuestas.
 *
 * Van en un middleware y no en cada controlador porque una cabecera que se
 * olvida en una ruta es una ruta sin la protección, y el olvido no se ve: la
 * respuesta sigue siendo correcta, sólo que el navegador deja de defender al
 * ciudadano. Aquí se aplican de una vez, incluidas las respuestas de error que
 * Laravel produce antes de llegar a ningún controlador.
 *
 * El porqué de cada una:
 *
 * - `X-Content-Type-Options: nosniff` — impide que el navegador adivine el tipo
 *   de un cuerpo que declaramos `application/json`; sin esto, un JSON con forma
 *   de HTML podría ejecutarse como tal.
 * - `X-Frame-Options: DENY` — la API no se embebe en ningún marco, y un iframe
 *   sobre una respuesta con sesión es la base del clickjacking.
 * - `Referrer-Policy: strict-origin-when-cross-origin` — al salir a otro origen
 *   sólo viaja el origen, nunca la ruta completa con parámetros que podrían
 *   llevar un radicado o un documento.
 * - `Content-Security-Policy: default-src 'self'` — una API no carga recursos de
 *   terceros; si alguna vez devuelve HTML por error, esto limita lo que ese HTML
 *   puede traer.
 */
final class CabecerasDeSeguridad
{
    /**
     * @param  Closure(Request): Response  $siguiente
     */
    public function handle(Request $peticion, Closure $siguiente): Response
    {
        $respuesta = $siguiente($peticion);

        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('X-Frame-Options', 'DENY');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Content-Security-Policy', "default-src 'self'");

        return $respuesta;
    }
}
