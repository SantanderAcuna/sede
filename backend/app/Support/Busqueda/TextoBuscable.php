<?php

declare(strict_types=1);

namespace App\Support\Busqueda;

use Illuminate\Support\Str;

/**
 * El texto contra el que se busca, y el modo único de producirlo.
 *
 * La búsqueda compara dos textos: el que escribe el ciudadano y el que se guarda
 * en `busqueda` / `busqueda_titulo`. Sólo es justa si **los dos pasan por la
 * misma función**: «Trámite» guardado con tilde contra «tramite» escrito sin ella
 * no se encuentra, y la diferencia más pequeña entre las dos rutas de
 * normalización produce justo ese fallo, que no se ve hasta que alguien busca.
 * Por eso la regla vive aquí, en un solo sitio, y la usan los modelos al guardar,
 * la migración al rellenar lo ya guardado, el comando `busqueda:reindexar` y el
 * servicio de búsqueda al recibir el término.
 *
 * ## Por qué no `unaccent` de PostgreSQL
 *
 * Se midió y se descartó: `unaccent()` está declarada `STABLE`, no `IMMUTABLE`, así
 * que no puede usarse en un índice sin envolverla en una función propia; y normalizar
 * en PHP, al guardar, deja la base con un texto ya limpio sobre el que `pg_trgm`
 * indexa directamente. Una extensión menos que instalar, auditar y mantener.
 *
 * ## Por qué `Str::ascii` y no `Normalizer`
 *
 * La extensión `intl` no está garantizada en todos los entornos; una búsqueda que
 * funciona en un servidor y no en otro es peor que una que falla en los dos.
 *
 * **Esta clase es parte del contrato de datos de la búsqueda:** si cambia lo que
 * hace `normalizar`, el texto ya guardado queda desfasado y hay que reindexar
 * (`php artisan busqueda:reindexar`). Por eso hay una prueba que fija su salida.
 */
final class TextoBuscable
{
    /**
     * Minúsculas, sin tildes ni eñes, sin comodines de `LIKE`.
     *
     * Los comodines `%` y `_` se quitan de los dos lados: un `%` suelto devolvería
     * el catálogo entero haciéndose pasar por el resultado de una búsqueda.
     * Los espacios internos se conservan tal cual (los nombres de la fuente traen
     * dobles espacios): `pg_trgm` trata cualquier separador como una frontera de
     * palabra, así que no cambian la similitud.
     */
    public static function normalizar(string $texto): string
    {
        return str_replace(['%', '_'], '', Str::lower(Str::ascii(trim($texto))));
    }

    /**
     * Lo que el ciudadano escribió, listo para compararlo con lo guardado.
     *
     * Es `normalizar` más dos cosas que sólo importan de este lado: se quita la
     * barra invertida —en un patrón `LIKE` escapa al carácter siguiente y, al final
     * del término, hace que PostgreSQL rechace la consulta— y se reducen los
     * espacios repetidos a uno, porque el ciudadano no escribe como la fuente.
     * Lo que queda puede ser más corto que lo escrito («___» queda vacío): quien
     * llame debe volver a comprobar el largo.
     */
    public static function paraConsulta(string $texto): string
    {
        $limpio = str_replace('\\', '', self::normalizar($texto));

        return trim((string) preg_replace('/\s+/u', ' ', $limpio));
    }

    /**
     * Las palabras que se exigen a un contenido para que sea un resultado.
     *
     * Sólo cuentan las de `$minimo` letras o más —«de», «y», «la» no distinguen
     * un trámite de otro— y hasta `$maximo`, para que una consulta larguísima no
     * produzca una consulta SQL larguísima. Se conserva el orden y se descartan
     * las repetidas.
     *
     * @return list<string>
     */
    public static function palabras(string $consulta, int $minimo, int $maximo): array
    {
        $palabras = [];

        foreach (explode(' ', $consulta) as $palabra) {
            if (mb_strlen($palabra) >= $minimo && ! in_array($palabra, $palabras, true)) {
                $palabras[] = $palabra;
            }
        }

        return array_slice($palabras, 0, max(0, $maximo));
    }

    /** El texto de un trámite: su nombre y su resumen. */
    public static function deTramite(string $nombre, ?string $resumen): string
    {
        return self::normalizar($nombre.' '.($resumen ?? ''));
    }

    /**
     * El texto de una política: su nombre, los títulos de sus apartados y sus
     * párrafos.
     *
     * Mientras `es_ejemplo` sea verdadero, el texto es un ejemplo —la página lo
     * dice con un aviso—; se indexa igual, porque la página que enlaza el
     * resultado muestra exactamente ese texto.
     *
     * @param  list<array{id?: string, titulo?: string, parrafos?: list<string>}>  $secciones
     */
    public static function dePolitica(string $nombre, array $secciones): string
    {
        $partes = [$nombre];

        foreach ($secciones as $seccion) {
            $partes[] = (string) ($seccion['titulo'] ?? '');

            foreach ($seccion['parrafos'] ?? [] as $parrafo) {
                $partes[] = (string) $parrafo;
            }
        }

        return self::normalizar(implode(' ', $partes));
    }
}
