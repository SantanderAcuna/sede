<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Tramite;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * No hay ningún trámite publicado con el slug pedido.
 *
 * Extiende `ModelNotFoundException` en vez de inventar una excepción nueva porque
 * el significado es exactamente ese —el recurso no está— y así el manejador de
 * errores la trata como cualquier otro recurso ausente: el contrato declara una
 * sola respuesta `404` para todos.
 *
 * El slug sí viaja en el mensaje de la excepción, y no en la respuesta: quien
 * depura necesita saber qué se pidió; quien recibe el `404` no debe poder
 * distinguir «no existe» de «no está publicado», porque esa diferencia revelaría
 * qué trámites tiene la Entidad sin publicar.
 *
 * @extends ModelNotFoundException<Tramite>
 */
final class TramiteNoEncontrado extends ModelNotFoundException
{
    public static function porSlug(string $slug): self
    {
        $excepcion = new self(sprintf('No hay ningún trámite publicado con el slug «%s».', $slug));
        $excepcion->setModel(Tramite::class, [$slug]);

        return $excepcion;
    }
}
