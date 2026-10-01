<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * La fuente de la ficha oficial no responde.
 *
 * Es una excepción propia y no un error de HTTP en crudo porque el comando de
 * ingesta tiene que poder distinguir tres cosas que se parecen y no son lo mismo:
 *
 * - **El trámite no existe en la fuente.** La respuesta es un `404` con cuerpo
 *   —`{"message":"Tramite no existe","StatusCode":604}`— y no hay nada que
 *   reintentar: se anota y se sigue.
 * - **La fuente está limitando la tasa.** Se comprobó que puede devolver un `404`
 *   **con el cuerpo vacío** tras muchas peticiones seguidas. Reintentar con espera
 *   exponencial sí sirve, y por eso el cliente lo hace antes de rendirse.
 * - **La fuente no está.** Un fallo de red, un `5xx` o una respuesta que no es
 *   JSON. Aquí lo correcto es parar el trámite y decirlo, porque seguir martillando
 *   una fuente caída no arregla nada y sí empeora el informe.
 *
 * El mensaje lleva el código del trámite para que el informe pueda decir **cuál**
 * falló y no sólo cuántos.
 */
final class FuenteNoDisponible extends RuntimeException
{
    public static function porRespuesta(string $codigo, string $ruta, int $estado, int $intentos): self
    {
        return new self(sprintf(
            'La fuente no respondió para el trámite %s: %s devolvió HTTP %d tras %d intentos.',
            $codigo,
            $ruta,
            $estado,
            $intentos,
        ));
    }

    public static function porConexion(string $codigo, string $ruta, string $motivo, int $intentos): self
    {
        return new self(sprintf(
            'No se pudo alcanzar la fuente para el trámite %s: %s — %s (tras %d intentos).',
            $codigo,
            $ruta,
            $motivo,
            $intentos,
        ));
    }

    public static function porCuerpoIlegible(string $codigo, string $ruta): self
    {
        return new self(sprintf(
            'La fuente respondió algo que no es JSON para el trámite %s: %s.',
            $codigo,
            $ruta,
        ));
    }
}
