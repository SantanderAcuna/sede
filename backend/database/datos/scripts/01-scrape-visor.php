<?php

declare(strict_types=1);

/**
 * Paso 1 de la ingesta: trae el listado de la entidad 0043 y la ficha completa
 * de cada uno de sus trámites desde SUIT.
 *
 * **De dónde sale.** Dos servicios públicos del Estado, sin autenticación:
 *
 *  - El buscador de SUIT (`dafpIndexerBT/tramite/index`) da los identificadores
 *    internos (`fi`) de la entidad, que es la llave con la que el visor sirve
 *    cada ficha.
 *  - El visor (`visorsuit.funcionpublica.gov.co/api/tramite?fi=…`) da la ficha
 *    completa: propósito, producto final, normativa con su archivo, requisitos
 *    por tipo con sus audiencias, momentos, puntos de atención, cuentas de
 *    recaudo y canales de seguimiento.
 *
 * **Por qué se guarda la respuesta cruda y no el resultado normalizado.** Este
 * paso no interpreta: copia. La normalización es del paso 2, de modo que si un
 * campo se mapea mal se puede volver a normalizar sin volver a golpear el
 * portal. También es lo que permite que un cambio quirúrgico del mapeo no
 * arrastre datos nuevos del origen.
 *
 * **Dos cosas que no son evidentes.**
 *
 *  - **El certificado del DAFP no valida con el almacén de CA local**, así que
 *    la verificación TLS va desactivada. Es una concesión del raspado y se
 *    declara aquí para que nadie la confunda con una decisión de diseño.
 *  - **La pausa de 200 ms entre peticiones es deliberada.** Son 124 peticiones
 *    a un servicio público del Estado: no hay prisa y no se golpea.
 *
 * Uso:
 *   php 01-scrape-visor.php [salida.json]
 */
const ENTIDAD = '0043';
const BUSCADOR = 'https://www.funcionpublica.gov.co/dafpIndexerBT/tramite/index';
const VISOR = 'https://visorsuit.funcionpublica.gov.co/api/tramite';

const PAUSA_MICROSEGUNDOS = 200_000;
const PAGINA = 10;

$salida = $argv[1] ?? sys_get_temp_dir().'/visores.json';

/**
 * Petición GET con el tiempo de espera y la cabecera de un navegador.
 *
 * `ignore_errors` deja leer el cuerpo de una respuesta 4xx/5xx en vez de
 * devolver `false` sin explicación, que es lo que hace falta para saber por qué
 * falló un identificador.
 */
function traer(string $url): ?string
{
    $contexto = stream_context_create([
        'http' => [
            'ignore_errors' => true,
            'timeout' => 30,
            'header' => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36\r\n",
        ],
        'ssl' => [
            // El almacén local no puede validar la cadena del DAFP.
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $cuerpo = @file_get_contents($url, false, $contexto);

    return $cuerpo === false ? null : $cuerpo;
}

/**
 * El listado de trámites de la entidad, paginado.
 *
 * @return list<array{fi: int, titulo: string, proposito: string}>
 */
function listar(): array
{
    $tramites = [];
    $vistos = [];

    for ($offset = 0; $offset <= 200; $offset += PAGINA) {
        $url = BUSCADOR.'?'.http_build_query([
            'find' => 'FindNext',
            'query' => '',
            'filtroEntidad' => ENTIDAD,
            'filtroSector' => '',
            'filtroDepartamento' => '',
            'filtroMunicipio' => '',
            'bloquearFiltroEntidad' => '',
            'bloquearFiltroSector' => '',
            'bloquearFiltroDepartamento' => '',
            'bloquearFiltroMunicipio' => '',
            'offset' => $offset,
            'max' => PAGINA,
        ]);

        $html = traer($url);

        if ($html === null) {
            fwrite(STDERR, "No se pudo leer el listado en offset={$offset}.\n");

            continue;
        }

        // Cada resultado es un enlace al visor con su `<h2>` y el propósito en
        // el `<p>` siguiente. Se leen juntos para que el par no se desalinee.
        if (preg_match_all(
            '/<a href="https:\/\/visorsuit\.funcionpublica\.gov\.co\/auth\/visor\?fi=(\d+)"[^>]*><h2[^>]*>([^<]+)<\/h2><\/a>\s*<p>([^<]+)<\/p>/s',
            $html,
            $coincidencias,
            PREG_SET_ORDER,
        ) === false) {
            continue;
        }

        foreach ($coincidencias as $m) {
            $fi = (int) $m[1];

            if (isset($vistos[$fi])) {
                continue;
            }

            $vistos[$fi] = true;
            $tramites[] = [
                'fi' => $fi,
                'titulo' => trim(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8')),
                'proposito' => trim(html_entity_decode($m[3], ENT_QUOTES, 'UTF-8')),
            ];
        }

        usleep(PAUSA_MICROSEGUNDOS);
    }

    return $tramites;
}

$listado = listar();

fwrite(STDOUT, 'Trámites en el listado: '.count($listado).PHP_EOL);

$visores = [];

foreach ($listado as $indice => $tramite) {
    $cuerpo = traer(VISOR.'?fi='.$tramite['fi']);
    $datos = $cuerpo === null ? null : json_decode($cuerpo, true);

    if (! is_array($datos) || ! isset($datos['tramite'])) {
        fwrite(STDERR, sprintf(
            '[%d] sin ficha: fi=%d (%s)'.PHP_EOL,
            $indice,
            $tramite['fi'],
            $tramite['titulo'],
        ));

        continue;
    }

    $visores[] = [
        'fi' => $tramite['fi'],
        'titulo' => $tramite['titulo'],
        'proposito_listado' => $tramite['proposito'],
        'data' => $datos['tramite'],
    ];

    if (($indice + 1) % 20 === 0) {
        fwrite(STDOUT, sprintf('  %d/%d'.PHP_EOL, $indice + 1, count($listado)));
    }

    usleep(PAUSA_MICROSEGUNDOS);
}

$documento = [
    '_origen' => [
        'buscador' => BUSCADOR.'?filtroEntidad='.ENTIDAD,
        'visor' => VISOR.'?fi={fi}',
        'obtenido_en' => date('Y-m-d'),
        'entidad' => ENTIDAD,
        'tramites_listados' => count($listado),
        'fichas_obtenidas' => count($visores),
        'nota' => 'Respuesta cruda. La normalización es del paso 02.',
    ],
    'visores' => $visores,
];

file_put_contents(
    $salida,
    json_encode($documento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
);

fwrite(STDOUT, sprintf('Escrito %s (%d fichas de %d listadas)'.PHP_EOL, $salida, count($visores), count($listado)));
