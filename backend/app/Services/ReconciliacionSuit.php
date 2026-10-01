<?php

declare(strict_types=1);

namespace App\Services;

use JsonException;
use RuntimeException;
use Throwable;

/**
 * La conciliación entre el catálogo de SUIT y el listado del sitio del Distrito.
 *
 * # Esta clase informa; no decide
 *
 * Encontrar que un trámite está en un catálogo y no en el otro **no dice cuál de
 * los dos tiene razón**. Que la Alcaldía tenga dos retiros de SISBEN que SUIT no
 * registra no significa que le sobren: puede que SUIT esté desactualizado, que el
 * trámite se haya suprimido sin darlo de baja, o que nunca se hubiera registrado.
 * Decidirlo es de la Entidad, y por eso aquí no hay ni una línea que añada, borre
 * o renombre un trámite: **se siembra SUIT**, que es la fuente ratificada, y lo
 * que discrepe se enumera con sus códigos y sus nombres para que alguien con
 * competencia lo resuelva.
 *
 * Un programa que eligiera por su cuenta qué trámites tiene la Alcaldía estaría
 * publicando y despublicando actos administrativos sin competencia para ello.
 *
 * # Los cinco hallazgos
 *
 * | Hallazgo | Qué significa |
 * |---|---|
 * | `solo_suit` | El trámite existe y está vigente, y el ciudadano no lo encuentra en el sitio |
 * | `solo_distrito` | El sitio publica un trámite que SUIT no registra |
 * | `duplicados` | El sitio publica el mismo código más de una vez |
 * | `dos_nombres` | El mismo código aparece con dos nombres distintos |
 * | `nombres_diferentes` | El mismo código se llama de una manera en SUIT y de otra en el sitio |
 */
final class ReconciliacionSuit
{
    /**
     * La copia congelada del listado del Distrito.
     *
     * Vive dentro del proyecto y no se lee de `investigacion/` —el directorio donde
     * se recogió— porque ese directorio no se versiona: sin copia propia, ni la
     * integración continua ni un despliegue nuevo podrían conciliar nada. Es el
     * mismo motivo por el que la siembra tiene su copia del catálogo de SUIT.
     */
    public const ARCHIVO = 'datos/tramites-sitio-alcaldia.json';

    /** Dónde se publicó el listado del Distrito del que salió la copia. */
    public const URL_DISTRITO = 'https://www.santamarta.gov.co/tramites-y-servicios';

    /**
     * Compara los dos catálogos.
     *
     * @param  array<string, string>  $titulosSuit  Código => nombre, según SUIT.
     * @return array{
     *     solo_suit: list<array{codigo: string, nombre: string}>,
     *     solo_distrito: list<array{codigo: string, nombre: string}>,
     *     duplicados: list<array{codigo: string, nombres: list<string>}>,
     *     dos_nombres: list<array{codigo: string, nombres: list<string>}>,
     *     nombres_diferentes: list<array{codigo: string, suit: string, distrito: list<string>}>,
     *     entradas_distrito: int,
     *     codigos_distrito: int,
     *     codigos_suit: int,
     * }
     */
    public function comparar(array $titulosSuit): array
    {
        $entradas = $this->entradas();

        /** @var array<string, list<string>> $nombresPorCodigo */
        $nombresPorCodigo = [];

        foreach ($entradas as $entrada) {
            $nombresPorCodigo[$entrada['codigo']][] = $entrada['nombre'];
        }

        $codigosDistrito = array_keys($nombresPorCodigo);

        $soloSuit = [];
        foreach ($titulosSuit as $codigo => $nombre) {
            if (! array_key_exists($codigo, $nombresPorCodigo)) {
                $soloSuit[] = ['codigo' => $codigo, 'nombre' => $nombre];
            }
        }

        $soloDistrito = [];
        $duplicados = [];
        $dosNombres = [];
        $nombresDiferentes = [];

        foreach ($nombresPorCodigo as $codigo => $nombres) {
            $unicos = array_values(array_unique($nombres));

            if (count($nombres) > 1) {
                $duplicados[] = ['codigo' => $codigo, 'nombres' => $unicos];
            }

            // Sólo cuenta como «el mismo trámite con dos nombres» si los dos
            // nombres son distintos. Repetir el mismo trámite dos veces con el
            // mismo nombre es una duplicación, no un problema de nomenclatura, y
            // mezclarlas haría que el informe no dijera qué hay que arreglar.
            if (count($unicos) > 1) {
                $dosNombres[] = ['codigo' => $codigo, 'nombres' => $unicos];
            }

            if (! array_key_exists($codigo, $titulosSuit)) {
                $soloDistrito[] = ['codigo' => $codigo, 'nombre' => $unicos[0] ?? ''];

                continue;
            }

            // Un nombre que difiere es el que no coincide con **ninguno** de los
            // que el Distrito publica para ese código. Comparar sólo contra el
            // primero daría por discrepante un trámite duplicado en el que una de
            // las dos entradas sí dice el nombre de SUIT, y el informe mandaría a
            // arreglar algo que ya está bien en una de ellas.
            if (! in_array($titulosSuit[$codigo], $unicos, true)) {
                $nombresDiferentes[] = [
                    'codigo' => $codigo,
                    'suit' => $titulosSuit[$codigo],
                    'distrito' => $unicos,
                ];
            }
        }

        // El informe se ordena por código en todas sus listas. No es cosmético:
        // un informe cuyo orden cambia entre ejecuciones obliga a compararlo a
        // mano cada vez para ver qué cambió, y el valor de esto es justamente que
        // se pueda leer dos veces y ver la diferencia.
        $porCodigo = static fn (array $a, array $b): int => strcmp($a['codigo'], $b['codigo']);

        usort($soloSuit, $porCodigo);
        usort($soloDistrito, $porCodigo);
        usort($duplicados, $porCodigo);
        usort($dosNombres, $porCodigo);
        usort($nombresDiferentes, $porCodigo);

        return [
            'solo_suit' => $soloSuit,
            'solo_distrito' => $soloDistrito,
            'duplicados' => $duplicados,
            'dos_nombres' => $dosNombres,
            'nombres_diferentes' => $nombresDiferentes,
            'entradas_distrito' => count($entradas),
            'codigos_distrito' => count($codigosDistrito),
            'codigos_suit' => count($titulosSuit),
        ];
    }

    /**
     * Las entradas de la copia congelada del listado del Distrito.
     *
     * @return list<array{codigo: string, nombre: string}>
     */
    private function entradas(): array
    {
        $ruta = database_path(self::ARCHIVO);

        if (! is_file($ruta)) {
            throw new RuntimeException(sprintf(
                'No está la copia del listado del Distrito en %s: sin ella no se puede conciliar el catálogo.',
                $ruta,
            ));
        }

        try {
            $contenido = json_decode((string) file_get_contents($ruta), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException(sprintf('La copia del listado del Distrito en %s no es un JSON legible.', $ruta), 0, $error);
        } catch (Throwable $error) {
            throw new RuntimeException(sprintf('No se pudo leer la copia del listado del Distrito en %s.', $ruta), 0, $error);
        }

        if (! is_array($contenido) || ! isset($contenido['entradas']) || ! is_array($contenido['entradas'])) {
            throw new RuntimeException(sprintf('La copia del listado del Distrito en %s no tiene la forma esperada.', $ruta));
        }

        $entradas = [];

        foreach ($contenido['entradas'] as $fila) {
            if (! is_array($fila)) {
                continue;
            }

            $codigo = $fila['codigo'] ?? null;
            $nombre = $fila['nombre'] ?? null;

            if (! is_string($codigo) || ! is_string($nombre) || trim($codigo) === '') {
                continue;
            }

            $entradas[] = ['codigo' => trim($codigo), 'nombre' => trim($nombre)];
        }

        return $entradas;
    }
}
