<?php

declare(strict_types=1);

namespace App\Support\Tramites;

/**
 * La fuente oficial que el catálogo declara en su procedencia.
 *
 * La Sede publica los datos con la marca de **GOV.CO + SUIT — Visor**, porque
 * el catálogo se compone de dos orígenes que **no se pueden mezclar en uno**:
 *
 *  - **GOV.CO** (vía `api-interno.www.gov.co/api/ficha-tramites-y-servicios/`)
 *    publica el **nombre estandarizado** y el **propósito** actualizados. Es lo
 *    que el ciudadano ve en `https://www.gov.co/ficha-tramites-y-servicios/Txxxx`.
 *  - **SUIT — Visor** (vía `visorsuit.funcionpublica.gov.co/api/tramite?fi=XXXX`)
 *    publica el **resto de los atributos**: requisitos por tipo, normativa,
 *    puntos de atención, cuentas de recaudo, audiencias, canales de
 *    seguimiento, momentos y resultado. Es lo que la Entidad siembra a través
 *    del `TramiteSeeder`.
 *
 * El listado crudo de SUIT (`dafpIndexerBT/tramite/index`) tiene los T-códigos
 * pero su nombre estandarizado es **anterior** al de GOV.CO. Verificado el
 * 2026-10-02 con los 124 trámites de la Entidad: los 124 nombres del listado
 * SUIT y los 124 de GOV.CO difieren. La Sede publica la versión GOV.CO por ser
 * la oficial vigente; la versión SUIT queda en el JSON como referencia
 * histórica y para que el visor siga siendo la fuente del resto de los campos.
 *
 * Existe como clase y no como constante suelta porque **la usan dos caminos que
 * tienen que coincidir**: la siembra desde la copia congelada y la ingesta de
 * la ficha oficial. La marca de procedencia es también la marca con la que cada
 * uno reconoce lo que escribió, así que si las dos cadenas no fueran exactamente
 * la misma, la ingesta vería como «corregido a mano» todo lo que sembró el
 * sembrador y no lo tocaría —o al revés—, y el catálogo se quedaría a medias
 * sin que nada fallara.
 */
final class FuenteSuit
{
    public const NOMBRE = 'GOV.CO + SUIT — Visor (entidad 0043)';

    public const URL = 'https://www.gov.co/ficha-tramites-y-servicios/';

    /**
     * La copia congelada del catálogo, dentro del proyecto.
     *
     * El catálogo se recogió en un directorio que no se versiona, así que sin una
     * copia propia ni la integración continua ni un despliegue nuevo podrían
     * sembrar ni ingerir nada. La ruta se declara aquí y no en cada uno de los dos
     * —el sembrador y el comando de ingesta— porque tienen que leer exactamente el
     * mismo archivo: dos rutas divergentes darían dos catálogos distintos según por
     * dónde se entrara.
     */
    public const COPIA_CATALOGO = 'datos/tramites-0043-suit.json';

    /**
     * El servicio del que se copió el nombre estandarizado y el propósito.
     *
     * GOV.CO expone este endpoint sin documentarlo como servicio público: la
     * Sede lo usa como **fuente del nombre y del propósito** del trámite, no
     * como contrato. Lo que la Alcaldía está obligada a sostener es el SUIT de
     * Función Pública (entidad 0043), cuyo §9.1 de la Guía hace de su
     * actualización requisito para integrarse a GOV.CO.
     */
    public const API_FICHA = 'https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/';
}
