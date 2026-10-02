<?php

declare(strict_types=1);

/**
 * Paso 3 de la ingesta: cruza la ficha del visor con el listado por T-código y
 * deja la copia que el `TramiteSeeder` lee.
 *
 * **El cruce es por nombre, y por eso prueba varios.** El visor de SUIT no
 * publica el T-código de GOV.CO en la ficha: se cruzan por nombre normalizado.
 * Y los nombres **no coinciden**: los 124 del listado de SUIT y los 124 de
 * GOV.CO difieren. Por eso se prueban, en orden, el nombre oficial de GOV.CO,
 * el del listado de SUIT y el título, y el primero que exista en el visor manda.
 *
 * **Lo que este paso no toca.** Todo lo que ya esté en el listado se conserva,
 * incluido el bloque de GOV.CO (`nombreEstandarizado_govco`, `proposito_govco`)
 * del que sale el nombre que la Sede publica. Este paso **añade** los campos del
 * visor y **deriva** los que la Sede calcula; no reescribe el nombre ni el
 * propósito.
 *
 * **Derivaciones que quedan declaradas.** `url_descarga` no lo publica la
 * fuente: se arma con la llave del archivo (`archivo_id`) y la plantilla de
 * descarga de SUIT. La regla se escribe en `_origen.derivados` para que el
 * lector no la confunda con un dato declarado.
 *
 * Uso:
 *   php 03-cruzar-con-listado.php [visor.json] [listado.json]
 */
const PLANTILLA_DESCARGA = 'https://tramites1.suit.gov.co/registro-web/suit_descargar_archivo?A=%d';

$rutaVisor = $argv[1] ?? __DIR__.'/../tramites-0043-suit-visor.json';
$rutaListado = $argv[2] ?? __DIR__.'/../tramites-0043-suit.json';

foreach ([$rutaVisor, $rutaListado] as $ruta) {
    if (! is_file($ruta)) {
        fwrite(STDERR, "No está el archivo {$ruta}.\n");
        exit(1);
    }
}

$visor = json_decode((string) file_get_contents($rutaVisor), true, 512, JSON_THROW_ON_ERROR);
$listado = json_decode((string) file_get_contents($rutaListado), true, 512, JSON_THROW_ON_ERROR);

/** El nombre comparable: minúsculas, sin tildes y sin puntuación. */
function normalizar(string $texto): string
{
    $texto = mb_strtolower(trim($texto));
    $texto = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'à', 'è', 'ì', 'ò', 'ù'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u'],
        $texto,
    );
    $texto = (string) preg_replace('/[^a-z0-9 ]+/u', ' ', $texto);

    return trim((string) preg_replace('/\s+/', ' ', $texto));
}

/** La URL de descarga del archivo de una norma, o nula si no tiene llave. */
function urlDescarga(array $norma): ?string
{
    $id = $norma['archivo_id'] ?? null;

    if (! is_int($id) && ! (is_string($id) && ctype_digit($id))) {
        return null;
    }

    return sprintf(PLANTILLA_DESCARGA, (int) $id);
}

// Índice del visor por nombre normalizado (el de la ficha y el del listado).
$indice = [];

foreach ($visor['tramites'] as $v) {
    foreach ([$v['nombre'] ?? null, $v['nombre_listado'] ?? null] as $nombre) {
        if (is_string($nombre) && $nombre !== '') {
            $indice[normalizar($nombre)] = $v;
        }
    }
}

$cruzados = 0;
$sinCruce = [];
$normasConDescarga = 0;
$normasSinLlave = 0;

foreach ($listado['tramites'] as $posicion => $t) {
    $candidatos = [
        $t['nombreEstandarizado_govco'] ?? null,
        $t['nombreEstandarizado_suit'] ?? null,
        $t['nombreEstandarizado'] ?? null,
        $t['titulo'] ?? null,
    ];

    $ficha = null;

    foreach ($candidatos as $candidato) {
        if (! is_string($candidato) || $candidato === '') {
            continue;
        }

        $llave = normalizar($candidato);

        if (isset($indice[$llave])) {
            $ficha = $indice[$llave];

            break;
        }
    }

    if ($ficha === null) {
        $sinCruce[] = $t['nombreEstandarizado'] ?? $t['titulo'] ?? '(sin nombre)';
    } else {
        $cruzados++;

        $normativa = [];

        foreach ($ficha['normativa'] ?? [] as $norma) {
            $url = urlDescarga($norma);

            if ($url === null) {
                $normasSinLlave++;
            } else {
                $normasConDescarga++;
            }

            $normativa[] = [
                'tipo' => $norma['tipo'] ?? null,
                'numero' => $norma['numero'] ?? null,
                'anio' => $norma['anio'] ?? null,
                'articulos' => $norma['articulos'] ?? null,
                'url' => $norma['url'] ?? null,
                'url_descarga' => $url,
                'archivo' => $norma['archivo'] ?? null,
            ];
        }

        // Se añade al listado sin tocar el nombre ni el propósito, que son de
        // GOV.CO, ni ningún otro campo que ya estuviera.
        $listado['tramites'][$posicion] = array_merge($t, [
            'fi' => $ficha['fi'] ?? null,
            'productoFinal' => $ficha['productoFinal'] ?? null,
            'palabrasRelacionadas' => $ficha['palabrasRelacionadas'] ?? null,
            'mediosResultado' => $ficha['mediosResultado'] ?? [],
            'urlManualTramiteEnLinea' => $ficha['urlManual'] ?? null,
            'modalidad_visor' => $ficha['modalidad'] ?? null,
            'tiempo_visor' => $ficha['tiempo'] ?? null,
            'fechaCualquiera' => $ficha['fechaCualquiera'] ?? null,
            'cuandoSePuedeRealizar' => $ficha['cuandoSePuedeRealizar'] ?? null,
            'urlCalendario' => $ficha['urlCalendario'] ?? null,
            'observacionesResultado' => $ficha['observacionesResultado'] ?? null,
            'normativa' => $normativa,
            'momentos' => $ficha['momentos'] ?? [],
            'puntosAtencion' => $ficha['puntosAtencion'] ?? [],
            'audiencias' => $ficha['audiencias'] ?? [],
            'cuentas' => $ficha['cuentas'] ?? [],
            'seguimiento' => $ficha['seguimiento'] ?? [],
        ]);
    }
}

$listado['_origen']['visor_cruzado'] = [
    'total_listado' => count($listado['tramites']),
    'total_visor' => count($visor['tramites']),
    'cruzados' => $cruzados,
    'sin_cruce' => count($sinCruce),
    'tramites_sin_cruce' => $sinCruce,
];

$listado['_origen']['derivados'] = [
    'url_descarga' => 'No lo declara la fuente. Se arma con la llave del archivo que publica el visor (`archivo.id`) y la plantilla de descarga de SUIT: '.PLANTILLA_DESCARGA,
    'normas_con_descarga' => $normasConDescarga,
    'normas_sin_llave_de_archivo' => $normasSinLlave,
    'cuando_se_puede_realizar' => 'El visor no publica una frase: publica tres hechos (`fechaCualquiera`, `observaFechaGeneral` y `urlCalendarioEjecucion`). Los tres viajan por separado y la ficha los compone. **No se publica `periodosEjecucionList`**: el único trámite que lo trae declara ventanas de descuento de 2013, y unas fechas vencidas leídas como vigentes son peor que su ausencia.',
];

file_put_contents(
    $rutaListado,
    json_encode($listado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
);

fwrite(STDOUT, sprintf('Cruzados: %d de %d'.PHP_EOL, $cruzados, count($listado['tramites'])));
fwrite(STDOUT, sprintf('Normas con descarga: %d (sin llave: %d)'.PHP_EOL, $normasConDescarga, $normasSinLlave));

if ($sinCruce !== []) {
    fwrite(STDERR, 'Sin cruce: '.count($sinCruce).PHP_EOL);

    foreach (array_slice($sinCruce, 0, 5) as $nombre) {
        fwrite(STDERR, "  - {$nombre}".PHP_EOL);
    }
}
