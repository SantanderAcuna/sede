<?php

declare(strict_types=1);

/**
 * Paso 2 de la ingesta: normaliza la respuesta cruda del visor a la copia que
 * el sembrador entiende.
 *
 * No toca la red: lee la foto que dejó el paso 01 y le da forma. Así un cambio
 * en el mapeo se puede probar sin volver a golpear el portal.
 *
 * **Qué conserva del origen.** Todo lo que el visor declara, con los nombres de
 * la fuente traducidos a los de la Sede. En particular se conserva
 * `archivo_id` en cada norma: es la llave del archivo PDF en SUIT y lo que
 * permite que la ficha ofrezca su descarga (el paso 03 lo convierte en
 * `url_descarga`). Antes se guardaba solo el nombre del archivo y el enlace se
 * perdía para los 123 trámites.
 *
 * Uso:
 *   php 02-normalizar-visor.php [entrada.json] [salida.json]
 */
$entrada = $argv[1] ?? sys_get_temp_dir().'/visores.json';
$salida = $argv[2] ?? __DIR__.'/../tramites-0043-suit-visor.json';

if (! is_file($entrada)) {
    fwrite(STDERR, "No está la respuesta cruda en {$entrada}. Corra antes el paso 01.\n");
    exit(1);
}

$crudo = json_decode((string) file_get_contents($entrada), true, 512, JSON_THROW_ON_ERROR);

// El paso 01 envuelve las fichas en `visores`; una foto suelta puede ser una
// lista pelada. Se aceptan las dos para poder normalizar una captura vieja.
$fichas = $crudo['visores'] ?? $crudo;

if (! is_array($fichas)) {
    fwrite(STDERR, "La entrada no tiene la forma esperada.\n");
    exit(1);
}

/** El tipo de norma, con el nombre que la Sede publica. */
function tipoDeNorma(array $norma): ?string
{
    $mapa = [
        1 => 'Acuerdo',
        2 => 'Acto administrativo',
        3 => 'Circular',
        4 => 'Decreto',
        5 => 'Directiva',
        6 => 'Documento',
        7 => 'Ley',
        8 => 'Ley',
        9 => 'Orden',
        10 => 'Resolución',
        11 => 'Sentencia',
        12 => 'Circular',
        13 => 'Acta',
        14 => 'Decreto Ley',
        15 => 'Resolución',
        16 => 'Constitución política de Colombia',
        17 => 'Decreto',
        18 => 'Auto',
    ];

    $id = $norma['tipoNorma']['id'] ?? null;

    return $mapa[$id] ?? ($norma['tipoNorma']['nombre'] ?? null);
}

$salidaDocumento = [
    '_origen' => [
        'fuente' => 'SUIT — Visor (Función Pública, entidad 0043 — ALCALDIA DISTRITAL DE SANTA MARTA)',
        'url' => 'https://visorsuit.funcionpublica.gov.co/auth/visor?fi={fi}',
        'catalogo' => 'GET https://visorsuit.funcionpublica.gov.co/api/tramite?fi={fi}',
        'obtenido_en' => $crudo['_origen']['obtenido_en'] ?? date('Y-m-d'),
        'normalizado_en' => date('Y-m-d'),
        'total_tramites' => count($fichas),
        'nota' => 'Copia congelada del visor, normalizada desde la respuesta cruda. Los nombres de los campos son los de la Sede; los del origen están en el paso 01.',
    ],
    'tramites' => [],
];

foreach ($fichas as $ficha) {
    $d = $ficha['data']['json'] ?? null;

    if (! is_array($d)) {
        continue;
    }

    // Término de obtención, con su nota de origen si la trae.
    $tiempo = null;

    if (! empty($d['tiempoObtencion'])) {
        $tiempo = [
            'cantidad' => (int) $d['tiempoObtencion'],
            'unidad' => $d['tiempoObtencionTipo'] ?? 'DIA',
            'nota' => $d['tiempoObtencionObservaciones'] ?? null,
        ];
    }

    // Normativa. `archivo_id` es la llave de descarga en SUIT.
    $normativa = [];

    foreach ($d['fundamentoLegalList'] ?? [] as $n) {
        $normativa[] = [
            'tipo' => tipoDeNorma($n),
            'numero' => $n['numero'] ?? null,
            'anio' => $n['ano'] ?? null,
            'articulos' => $n['articulos'] ?? null,
            'url' => $n['urlNorma'] ?? null,
            'archivo' => $n['archivo']['nombre'] ?? null,
            'archivo_id' => $n['archivo']['id'] ?? null,
        ];
    }

    // Momentos (pasos) con sus requisitos y las audiencias de cada uno.
    $momentos = [];

    foreach ($d['momentos'] ?? [] as $m) {
        $requisitos = [];

        foreach ($m['requisitos'] ?? [] as $r) {
            $requisito = [
                'orden' => $r['orden'] ?? null,
                'tipo' => $r['tipoRequisito'] ?? null,
                'descripcion' => $r['descripcionVerificado'] ?? $r['descripcionSolicitud'] ?? $r['anotacionAdicional'] ?? null,
                'obligatorio' => $r['tramiteExcepcion'] === null,
                'documento' => $r['nombreDocumento'] ?? null,
                'formulario_nombre' => $r['formulario']['nombre'] ?? null,
                'formulario_url' => $r['formulario']['urlFormulario'] ?? null,
                'url_pago' => $r['urlPago'] ?? null,
                // Las audiencias para las que aplica el requisito: es la base
                // del filtro «Para realizarlo necesita» del visor.
                'tipos_audiencia' => array_values(array_filter(array_map(
                    static fn ($a) => $a['nombre'] ?? null,
                    $r['tiposAudiencias'] ?? [],
                ))),
            ];

            if (! empty($r['valoresPago'])) {
                $requisito['pago_valor'] = [];

                foreach ($r['valoresPago'] as $vp) {
                    $requisito['pago_valor'][] = [
                        'valor' => $vp['valor'] ?? null,
                        'moneda' => $vp['tipoMoneda']['nombre'] ?? null,
                        'tipo_valor' => $vp['tipoValor'] ?? null,
                        'descripcion' => $vp['descripcion'] ?? null,
                    ];
                }
            }

            if (! empty($r['pagosEntidad'])) {
                $requisito['pago_cuentas'] = [];

                foreach ($r['pagosEntidad'] as $pe) {
                    $requisito['pago_cuentas'][] = [
                        'banco' => $pe['entidadRecaudadora']['nombre'] ?? null,
                        'tipo' => $pe['tipoCuenta'] ?? null,
                        'numero' => $pe['numeroCuenta'] ?? null,
                        'titular' => $pe['nombreCuenta'] ?? null,
                    ];
                }
            }

            if (! empty($r['canales'])) {
                $requisito['canales'] = [];

                foreach ($r['canales'] as $c) {
                    $requisito['canales'][] = [
                        'tipo' => $c['tipoCanal'] ?? null,
                        'email' => $c['email'] ?? null,
                    ];
                }
            }

            $requisitos[] = $requisito;
        }

        $momentos[] = [
            'orden' => $m['orden'] ?? null,
            'descripcion' => $m['descripcion'] ?? null,
            'requisitos' => $requisitos,
        ];
    }

    // Puntos de atención, deduplicados por nombre y dirección: el mismo punto
    // aparece en la ficha, en los requisitos y en el seguimiento.
    $puntos = [];
    $puntosVistos = [];
    $agregarPunto = static function (array $p) use (&$puntos, &$puntosVistos): void {
        $llave = ($p['nombre'] ?? '').'|'.($p['direccion'] ?? '');

        if (isset($puntosVistos[$llave])) {
            return;
        }

        $puntosVistos[$llave] = true;
        $puntos[] = [
            'nombre' => $p['nombre'] ?? null,
            'direccion' => $p['direccion'] ?? null,
            'telefono' => $p['telefono'] ?? null,
            'horario' => $p['horarioAtencion'] ?? null,
            'municipio' => $p['municipio']['nombre'] ?? null,
            'departamento' => $p['municipio']['nombreDepartamento'] ?? null,
            'latitud' => isset($p['geoLatitud']) ? (float) $p['geoLatitud'] : null,
            'longitud' => isset($p['geoLongitud']) ? (float) $p['geoLongitud'] : null,
        ];
    };

    foreach ($d['puntosAtencionList'] ?? [] as $p) {
        $agregarPunto($p);
    }

    foreach ($d['momentos'] ?? [] as $m) {
        foreach ($m['requisitos'] ?? [] as $r) {
            foreach ($r['puntosAtencion'] ?? [] as $p) {
                $agregarPunto($p);
            }
        }
    }

    foreach ($d['seguimientoPresencial']['puntosAtencionList'] ?? [] as $p) {
        $agregarPunto($p);
    }

    // Audiencias con su grupo (Ciudadano, Organizaciones, …).
    $audiencias = [];

    foreach ($d['tiposAudienciaList'] ?? [] as $g) {
        foreach ($g['audiencias'] ?? [] as $a) {
            $audiencias[] = [
                'grupo' => $g['nombre'] ?? null,
                'nombre' => $a['nombre'] ?? null,
                'descripcion' => $a['descripcion'] ?? null,
            ];
        }
    }

    // Cuentas de recaudo, unificadas de todos los requisitos de pago.
    $cuentas = [];
    $cuentasVistas = [];

    foreach ($d['momentos'] ?? [] as $m) {
        foreach ($m['requisitos'] ?? [] as $r) {
            if (($r['tipoRequisito'] ?? null) !== 'PAGO') {
                continue;
            }

            foreach ($r['pagosEntidad'] ?? [] as $c) {
                $llave = ($c['entidadRecaudadora']['nombre'] ?? '').'|'.($c['numeroCuenta'] ?? '');

                if (isset($cuentasVistas[$llave])) {
                    continue;
                }

                $cuentasVistas[$llave] = true;
                $cuentas[] = [
                    'banco' => $c['entidadRecaudadora']['nombre'] ?? null,
                    'tipo' => $c['tipoCuenta'] ?? null,
                    'numero' => $c['numeroCuenta'] ?? null,
                    'titular' => $c['nombreCuenta'] ?? null,
                ];
            }
        }
    }

    // Canales de seguimiento.
    $seguimiento = [
        'telefono' => [],
        'email' => [],
        'presencial' => $d['seguimientoPresencial']['puntosAtencionList'] ?? [],
        'web' => $d['seguimientoWebList'] ?? [],
    ];

    foreach ($d['seguimientoTelefonicoList'] ?? [] as $tel) {
        $seguimiento['telefono'][] = [
            'numero' => $tel['numero'] ?? null,
            'extension' => $tel['extension'] ?? null,
            'horario' => $tel['horarioAtencionTelefono'] ?? null,
        ];
    }

    foreach ($d['seguimientoEmailList'] ?? [] as $e) {
        $seguimiento['email'][] = ['email' => $e['email'] ?? null];
    }

    $salidaDocumento['tramites'][] = [
        'fi' => $ficha['fi'] ?? null,
        'nombre' => $d['nombre'] ?? null,
        'nombre_listado' => $ficha['titulo'] ?? null,
        'proposito' => $d['proposito'] ?? null,
        'modalidad' => $d['tipoMedioElectronico'] ?? null,
        'tiempo' => $tiempo,
        'productoFinal' => $d['productoFinal'] ?? null,
        'urlTramiteEnLinea' => $d['urlTramiteEnLinea'] ?? null,
        'urlManual' => $d['urlManualTramiteEnLinea'] ?? null,
        'palabrasRelacionadas' => $d['palabrasRelacionadas'] ?? null,
        'mediosResultado' => array_column($d['mediosResultadoList'] ?? [], 'nombre'),
        // «¿Cuándo se puede realizar?». El visor lo responde con tres cosas
        // distintas y no con una: casi siempre «cualquier fecha» (un booleano),
        // a veces una condición en prosa, y una vez un calendario externo.
        // Se conservan las tres por separado para no convertir un hecho en un
        // texto que nadie pueda auditar.
        'fechaCualquiera' => $d['fechaCualquiera'] ?? null,
        'cuandoSePuedeRealizar' => $d['observaFechaGeneral'] ?? null,
        'urlCalendario' => $d['urlCalendarioEjecucion'] ?? null,
        // Las «Observaciones» que el visor publica **bajo el resultado**, no
        // bajo el término: en 16 trámites aclara de qué depende el plazo.
        'observacionesResultado' => $d['tiempoObtencionObservaciones'] ?? null,
        'normativa' => $normativa,
        'momentos' => $momentos,
        'puntosAtencion' => $puntos,
        'audiencias' => $audiencias,
        'cuentas' => $cuentas,
        'seguimiento' => $seguimiento,
    ];
}

file_put_contents(
    $salida,
    json_encode($salidaDocumento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
);

fwrite(STDOUT, sprintf(
    'Escrito %s: %d trámites'.PHP_EOL,
    $salida,
    count($salidaDocumento['tramites']),
));
