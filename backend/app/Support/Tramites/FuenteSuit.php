<?php

declare(strict_types=1);

namespace App\Support\Tramites;

/**
 * La fuente oficial que el catálogo declara en su procedencia.
 *
 * Existe como clase y no como constante suelta porque **la usan dos caminos que
 * tienen que coincidir**: la siembra desde la copia congelada del catálogo de
 * SUIT y la ingesta de la ficha oficial. La marca de procedencia es también la
 * marca con la que cada uno reconoce lo que escribió, así que si las dos cadenas
 * no fueran exactamente la misma, la ingesta vería como «corregido a mano» todo lo
 * que sembró el sembrador y no lo tocaría —o al revés—, y el catálogo se quedaría
 * a medias sin que nada fallara.
 *
 * Los dos apuntan al mismo origen ratificado: el SUIT de Función Pública para la
 * entidad `0043`. Lo que cambia entre ellos no es la fuente sino **qué se leyó**:
 * el sembrador lee la copia del catálogo —nombre, propósito, si está en línea, si
 * tiene costo, el plazo y el enlace—, y la ingesta lee además la ficha del
 * trámite, que es donde están los documentos, las condiciones, los puntos de
 * atención y la normativa. Por eso la ingesta añade `procedencia_api`, que dice de
 * qué servicio salieron esos datos de más.
 */
final class FuenteSuit
{
    public const NOMBRE = 'SUIT — Función Pública (entidad 0043)';

    public const URL = 'https://www.funcionpublica.gov.co/es/suit/buscador-de-tramites';

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
     * El servicio del que se copió la forma de la ficha.
     *
     * Se declara en la procedencia para que ésta se pueda **repetir**. Y se
     * declara con una advertencia que importa jurídicamente: es un servicio
     * público de contenido que GOV.CO **no documenta ni ofrece como servicio**.
     * De él se copia la forma de los datos y **nada más**; no es un contrato que
     * gov.co imponga a la Entidad ni de él se hereda ninguna obligación. Lo que
     * la Alcaldía está obligada a sostener es el SUIT —cuyo §9.1 de la Guía hace
     * de su actualización requisito para integrarse a Gov.co—, no esta API.
     */
    public const API_FICHA = 'https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/';
}
