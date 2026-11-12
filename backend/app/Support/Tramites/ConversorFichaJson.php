<?php

declare(strict_types=1);

namespace App\Support\Tramites;

/**
 * Convierte la ficha que antes se guardaba como bloques JSON dentro de `tramites` en las filas
 * de las tablas propias (pasos, requisitos, documentos, puntos de atención, normas, canales y
 * cuentas).
 *
 * Existe para una sola cosa: que la migración a la base relacional **no pierda contenido**. Es
 * una función pura —sin base de datos— para poder probarla con las formas irregulares que el
 * catálogo real trae (requisitos sin descripción, municipios que son objetos, cuentas con dos
 * vocabularios) y para que la migración y la carga inicial usen la misma regla.
 *
 * Reglas que no se leen en el código:
 *  - Los requisitos se conservan por paso cuando los pasos los traen; los requisitos generales
 *    solo se añaden si no repiten uno que ya está en un paso (misma clase y mismo texto).
 *  - Un requisito sin texto toma el nombre del documento o del formulario; si tampoco lo tiene
 *    se descarta, porque una fila vacía no le dice nada al ciudadano.
 *  - Lo que no cabe en una columna propia (cantidad, pagos anidados) pasa a la nota del
 *    requisito en palabras, en vez de perderse.
 */
final class ConversorFichaJson
{
    /** Los vocabularios de tipo de requisito que acepta el panel. */
    private const TIPOS = ['documento', 'verificacion_institucional', 'solicitud', 'formulario', 'pago'];

    /**
     * @param  array<string, mixed>  $ficha  Los bloques JSON de un trámite, ya decodificados.
     * @return array{
     *     pasos: list<array{orden: int, titulo: string, descripcion: string|null}>,
     *     requisitos: list<array{orden: int, tipo: string, descripcion: string, obligatorio: bool, nota: string|null, url: string|null, paso: int|null}>,
     *     documentos: list<array{nombre: string, url: string|null, formato: string|null}>,
     *     puntos_atencion: list<array<string, mixed>>,
     *     normativa: list<array<string, mixed>>,
     *     canales_consulta_estado: list<array<string, mixed>>,
     *     cuentas: list<array{banco: string|null, tipo: string|null, numero: string|null, titular: string|null}>,
     *     resultado: string|null,
     *     audiencias: list<string>
     * }
     */
    public static function convertir(array $ficha): array
    {
        [$pasos, $requisitos] = self::pasosYRequisitos(
            self::lista($ficha['momentos'] ?? null),
            self::lista($ficha['requisitos'] ?? null),
        );

        return [
            'pasos' => $pasos,
            'requisitos' => $requisitos,
            'documentos' => self::documentos(self::lista($ficha['documentos'] ?? null)),
            'puntos_atencion' => self::puntos(self::lista($ficha['puntos_atencion'] ?? null), $ficha['seguimiento'] ?? null),
            'normativa' => self::normativa(self::lista($ficha['normativa'] ?? null)),
            'canales_consulta_estado' => self::canales(self::lista($ficha['canales_consulta_estado'] ?? null)),
            'cuentas' => self::cuentas(
                self::lista($ficha['costo_cuentas'] ?? null),
                self::lista($ficha['cuentas'] ?? null),
            ),
            'resultado' => self::texto($ficha['resultado'] ?? null, 5000) ?? self::texto($ficha['producto_final'] ?? null, 5000),
            'audiencias' => self::audiencias(self::lista($ficha['audiencias'] ?? null), self::lista($ficha['requisitos'] ?? null), self::lista($ficha['momentos'] ?? null)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $momentos
     * @param  list<array<string, mixed>>  $generales
     * @return array{0: list<array{orden: int, titulo: string, descripcion: string|null}>, 1: list<array{orden: int, tipo: string, descripcion: string, obligatorio: bool, nota: string|null, url: string|null, paso: int|null}>}
     */
    private static function pasosYRequisitos(array $momentos, array $generales): array
    {
        $pasos = [];
        $requisitos = [];
        $vistos = [];
        $orden = 0;

        foreach ($momentos as $indice => $momento) {
            $titulo = self::texto($momento['titulo'] ?? null, 500);
            if ($titulo === null) {
                continue;
            }
            $numero = count($pasos) + 1;
            $pasos[] = [
                'orden' => $numero,
                'titulo' => $titulo,
                'descripcion' => self::texto($momento['descripcion'] ?? null, 5000),
            ];
            unset($indice);

            foreach (self::lista($momento['requisitos'] ?? null) as $requisito) {
                $fila = self::requisito($requisito, ++$orden, $numero);
                if ($fila === null || isset($vistos[self::clave($fila)])) {
                    --$orden;

                    continue;
                }
                $vistos[self::clave($fila)] = true;
                $requisitos[] = $fila;
            }
        }

        foreach ($generales as $requisito) {
            $fila = self::requisito($requisito, $orden + 1, null);
            if ($fila === null || isset($vistos[self::clave($fila)])) {
                continue;
            }
            ++$orden;
            $vistos[self::clave($fila)] = true;
            $requisitos[] = $fila;
        }

        return [$pasos, $requisitos];
    }

    /**
     * @param  array<string, mixed>  $r
     * @return array{orden: int, tipo: string, descripcion: string, obligatorio: bool, nota: string|null, url: string|null, paso: int|null}|null
     */
    private static function requisito(array $r, int $orden, ?int $paso): ?array
    {
        $descripcion = self::texto($r['descripcion'] ?? null, 2000)
            ?? self::texto($r['documento'] ?? null, 2000)
            ?? self::texto($r['formulario'] ?? null, 2000);

        $tipo = is_string($r['tipo'] ?? null) && in_array($r['tipo'], self::TIPOS, true) ? $r['tipo'] : 'documento';

        // La «solicitud» sin texto es un requisito real (presentar la solicitud) que la fuente
        // no redacta; los demás tipos sin texto no dicen nada y se descartan.
        if ($descripcion === null && $tipo === 'solicitud') {
            $descripcion = 'Presentar la solicitud';
        }
        if ($descripcion === null) {
            return null;
        }

        $notas = [];
        if (($nota = self::texto($r['nota'] ?? null, 2000)) !== null) {
            $notas[] = $nota;
        }
        if (isset($r['cantidad']) && is_numeric($r['cantidad'])) {
            $unidad = self::texto($r['unidad_cantidad'] ?? null, 40);
            $notas[] = 'Cantidad: '.$r['cantidad'].($unidad !== null ? ' '.$unidad : '').'.';
        }
        foreach (self::lista($r['pago'] ?? null) as $pago) {
            $partes = array_filter([
                isset($pago['valor']) && is_scalar($pago['valor']) ? 'Valor: '.$pago['valor'] : null,
                self::texto($pago['moneda'] ?? null, 20),
                self::texto($pago['descripcion'] ?? null, 300),
            ]);
            if ($partes !== []) {
                $notas[] = implode(' ', $partes).'.';
            }
        }
        foreach (self::lista($r['cuentas'] ?? null) as $cuenta) {
            $partes = array_filter([
                self::texto($cuenta['banco'] ?? null, 120),
                self::texto($cuenta['tipo'] ?? null, 40),
                self::texto($cuenta['numero'] ?? null, 40),
            ]);
            if ($partes !== []) {
                $notas[] = 'Cuenta: '.implode(' · ', $partes).'.';
            }
        }

        return [
            'orden' => $orden,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'obligatorio' => ! array_key_exists('obligatorio', $r) || $r['obligatorio'] === null ? true : (bool) $r['obligatorio'],
            'nota' => $notas === [] ? null : mb_substr(implode(' ', $notas), 0, 2000),
            'url' => self::url($r['formulario_url'] ?? null) ?? self::url($r['url_pago'] ?? null) ?? self::url($r['url'] ?? null),
            'paso' => $paso,
        ];
    }

    /** @param array{tipo: string, descripcion: string} $fila */
    private static function clave(array $fila): string
    {
        return $fila['tipo'].'|'.mb_strtolower(trim(preg_replace('/\s+/u', ' ', $fila['descripcion']) ?? $fila['descripcion']));
    }

    /**
     * @param  list<array<string, mixed>>  $filas
     * @return list<array{nombre: string, url: string|null, formato: string|null}>
     */
    private static function documentos(array $filas): array
    {
        $salida = [];
        foreach ($filas as $f) {
            $nombre = self::texto($f['nombre'] ?? null, 300);
            if ($nombre !== null) {
                $salida[] = ['nombre' => $nombre, 'url' => self::url($f['url'] ?? null), 'formato' => self::texto($f['formato'] ?? null, 30)];
            }
        }

        return $salida;
    }

    /**
     * @param  list<array<string, mixed>>  $filas
     * @return list<array<string, mixed>>
     */
    private static function puntos(array $filas, mixed $seguimiento): array
    {
        if ($filas === [] && is_array($seguimiento)) {
            $filas = self::lista($seguimiento['presencial'] ?? null);
        }

        $salida = [];
        foreach ($filas as $f) {
            $nombre = self::texto($f['nombre'] ?? null, 160);
            $direccion = self::texto($f['direccion'] ?? null, 500);
            if ($nombre === null || $direccion === null) {
                continue;
            }
            $salida[] = [
                'nombre' => $nombre,
                'direccion' => $direccion,
                'telefono' => self::texto($f['telefono'] ?? null, 40),
                'horario' => self::texto($f['horario'] ?? null, 1000),
                'municipio' => self::textoOMapa($f['municipio'] ?? null, 120),
                'departamento' => self::textoOMapa($f['departamento'] ?? null, 120),
                'latitud' => is_numeric($f['latitud'] ?? null) ? (float) $f['latitud'] : null,
                'longitud' => is_numeric($f['longitud'] ?? null) ? (float) $f['longitud'] : null,
            ];
        }

        return $salida;
    }

    /**
     * @param  list<array<string, mixed>>  $filas
     * @return list<array<string, mixed>>
     */
    private static function normativa(array $filas): array
    {
        $salida = [];
        foreach ($filas as $f) {
            $tipo = self::texto($f['tipo'] ?? null, 200);
            if ($tipo === null) {
                continue;
            }
            $salida[] = [
                'tipo' => $tipo,
                'numero' => self::texto($f['numero'] ?? null, 200),
                'anio' => is_numeric($f['anio'] ?? null) ? (int) $f['anio'] : null,
                'articulos' => self::texto($f['articulos'] ?? null, 1000),
                'url' => self::url($f['url'] ?? null) ?? self::url($f['url_descarga'] ?? null),
            ];
        }

        return $salida;
    }

    /**
     * @param  list<array<string, mixed>>  $filas
     * @return list<array<string, mixed>>
     */
    private static function canales(array $filas): array
    {
        $salida = [];
        foreach ($filas as $f) {
            $canal = self::texto($f['canal'] ?? null, 30);
            if ($canal === null) {
                continue;
            }
            $salida[] = [
                'canal' => $canal,
                'habilitado' => ! array_key_exists('habilitado', $f) || (bool) $f['habilitado'],
                'nombre' => self::texto($f['nombre'] ?? null, 160),
                'url' => self::url($f['url'] ?? null),
                'correo' => self::texto($f['correo'] ?? null, 240),
                'telefono' => self::texto($f['telefono'] ?? null, 40),
                'horario' => self::texto($f['horario'] ?? null, 1000),
            ];
        }

        return $salida;
    }

    /**
     * Las cuentas de recaudo vienen con dos vocabularios (`entidad`/`numero_cuenta` y
     * `banco`/`numero`) y repetidas entre el costo y el bloque general: se unifican y se
     * deduplican por banco y número.
     *
     * @param  list<array<string, mixed>>  $a
     * @param  list<array<string, mixed>>  $b
     * @return list<array{banco: string|null, tipo: string|null, numero: string|null, titular: string|null}>
     */
    private static function cuentas(array $a, array $b): array
    {
        $salida = [];
        foreach ([...$a, ...$b] as $c) {
            $fila = [
                'banco' => self::texto($c['banco'] ?? $c['entidad'] ?? null, 120),
                'tipo' => self::texto($c['tipo'] ?? $c['tipo_cuenta'] ?? null, 40),
                'numero' => self::texto($c['numero'] ?? $c['numero_cuenta'] ?? null, 40),
                'titular' => self::texto($c['titular'] ?? null, 120),
            ];
            if ($fila['banco'] === null && $fila['numero'] === null) {
                continue;
            }
            $salida[$fila['banco'].'|'.$fila['numero']] ??= $fila;
        }

        return array_values($salida);
    }

    /**
     * El «tipo de solicitante» (RF-B1-022): los grupos de la audiencia de la ficha —no el detalle
     * poblacional, repetido en casi todos los trámites— más los que nombran los requisitos.
     *
     * @param  list<array<string, mixed>>  $audiencias
     * @param  list<array<string, mixed>>  $requisitos
     * @param  list<array<string, mixed>>  $momentos
     * @return list<string>
     */
    private static function audiencias(array $audiencias, array $requisitos, array $momentos): array
    {
        $nombres = array_column($audiencias, 'grupo');
        foreach ([...$requisitos, ...array_merge([], ...array_map(static fn (array $m): array => self::lista($m['requisitos'] ?? null), $momentos))] as $r) {
            foreach (is_array($r['tipos_audiencia'] ?? null) ? $r['tipos_audiencia'] : [] as $n) {
                $nombres[] = $n;
            }
        }

        $grupos = [];
        foreach ($nombres as $n) {
            $clave = is_string($n) ? mb_strtolower(trim($n)) : '';
            $grupo = match (true) {
                str_starts_with($clave, 'ciudadan') => 'ciudadano',
                str_starts_with($clave, 'extranjer') => 'extranjeros',
                str_starts_with($clave, 'organizaci') => 'organizaciones',
                str_starts_with($clave, 'instituci') => 'instituciones',
                default => null,
            };
            if ($grupo !== null) {
                $grupos[$grupo] = true;
            }
        }

        return array_keys($grupos);
    }

    /** @return list<array<string, mixed>> */
    private static function lista(mixed $valor): array
    {
        if (! is_array($valor)) {
            return [];
        }

        return array_values(array_filter($valor, 'is_array'));
    }

    private static function texto(mixed $valor, int $max): ?string
    {
        if (! is_scalar($valor)) {
            return null;
        }
        $texto = trim((string) $valor);

        return $texto === '' ? null : mb_substr($texto, 0, $max);
    }

    /** El municipio llega como texto o como objeto con el nombre dentro. */
    private static function textoOMapa(mixed $valor, int $max): ?string
    {
        if (is_array($valor)) {
            foreach (['nombre', 'name', 'municipio', 'descripcion'] as $clave) {
                if (isset($valor[$clave])) {
                    return self::texto($valor[$clave], $max);
                }
            }

            return null;
        }

        return self::texto($valor, $max);
    }

    private static function url(mixed $valor): ?string
    {
        $texto = self::texto($valor, 500);

        return $texto !== null && preg_match('#^https?://\S+$#i', $texto) === 1 ? $texto : null;
    }
}
