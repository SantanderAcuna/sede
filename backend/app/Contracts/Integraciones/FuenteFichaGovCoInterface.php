<?php

declare(strict_types=1);

namespace App\Contracts\Integraciones;

use App\Exceptions\FuenteNoDisponible;
use App\Support\Ingesta\FichaFuente;

/**
 * La ficha oficial de un trámite, en la fuente del Estado.
 *
 * # Qué es esta fuente, y qué no es
 *
 * Es la **API de contenido de GOV.CO**, un servicio público que GOV.CO **no
 * documenta ni ofrece como servicio**. De ella se copia la **forma** de los datos
 * —qué campos tiene una ficha y qué significan— y **nada más**: no es un contrato
 * que gov.co imponga a la Entidad ni de ella se hereda ninguna obligación. Lo que
 * la Alcaldía está obligada a sostener es la Guía de la Resolución 2893 —cuyo
 * §5.1.3 fija los seis atributos y cuyo §9.1 hace de la actualización en el SUIT
 * requisito para integrarse a Gov.co—, no las condiciones de este servicio.
 *
 * Esa distinción importa para operar: si mañana la API cambia de forma o deja de
 * responder, la Entidad no ha incumplido nada frente a GOV.CO, pero sí tiene que
 * arreglar su ingesta. Por eso todo lo que sabe de la fuente vive detrás de esta
 * interfaz y no repartido por la aplicación.
 *
 * # Tres restricciones de la fuente, verificadas con `curl`
 *
 * 1. **Exige cabeceras de navegador.** Sin `User-Agent` y `Referer:
 *    https://www.gov.co/` responde **HTTP 403**. No es un detalle de cortesía: la
 *    fuente rechaza a quien no se anuncia como navegador.
 * 2. **El identificador es el número, no el código.** Los trámites se llaman
 *    `T2621` en el catálogo de SUIT, y la API sólo entiende `2621`. Con la forma
 *    `T2621` devuelve `{"data":null}` o `404 {"message":"Tramite no existe"}` sin
 *    decir que el problema es el prefijo. Es una trampa silenciosa: parece que el
 *    trámite no existe cuando lo que sobra es una letra.
 * 3. **El perfil viaja como texto, no como número.** En
 *    `GetMomentosByIdAudiencia/{id}/{detalle}` y en
 *    `GetDataFichaByIdTramiteAudienciaIdMomento/{id}/{detalle}/{momentoId}` el
 *    segundo parámetro es el **texto** del perfil —`Ciudadano`— y no su índice.
 *    Con un número la respuesta es `404 {"message":"Tramite no existe",
 *    "StatusCode":604}`, el mismo error que da un trámite inexistente. Esta trampa
 *    ya hizo fracasar una recolección anterior y por eso está escrita aquí.
 */
interface FuenteFichaGovCoInterface
{
    /**
     * Trae y reúne todo lo que la fuente declara sobre un trámite.
     *
     * @param  string  $codigo  El código del catálogo, con la forma `T#####`.
     *
     * @throws FuenteNoDisponible
     */
    public function ficha(string $codigo): FichaFuente;
}
