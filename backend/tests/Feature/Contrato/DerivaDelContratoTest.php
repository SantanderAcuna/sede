<?php

declare(strict_types=1);

namespace Tests\Feature\Contrato;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * La deriva entre el contrato y las rutas.
 *
 * El contrato es la fuente de verdad del intercambio HTTP, pero eso sólo se
 * sostiene si algo comprueba que las dos listas siguen coincidiendo. Esta prueba
 * es ese algo: compara las operaciones declaradas en `contract/openapi.yaml` con
 * las rutas que Laravel tiene registradas.
 *
 * Falla en los dos sentidos, y los dos importan:
 *
 * - Una operación marcada `x-status: implemented` sin ruta es una promesa que el
 *   contrato hace y el servidor no cumple; el cliente que se genere desde él
 *   llamará a un `404`.
 * - Una ruta sin operación declarada es un intercambio que existe fuera del
 *   contrato; nadie lo revisó y ningún cliente generado lo conoce.
 *
 * Las operaciones `x-status: pending` se ignoran a propósito: están declaradas
 * —eso ya las vuelve parte del contrato— pero todavía no se construyen, y
 * exigirles ruta obligaría a implementarlas para poder pasar la puerta.
 *
 * ## Cómo se lee el contrato
 *
 * Sin dependencias: el lector de aquí abajo entiende sólo el bloque `paths:` en
 * su forma de bloque, que es la que el contrato usa. No se añadió un analizador
 * de YAML al proyecto porque una dependencia nueva la decide el titular, no un
 * agente (ver `AGENTS.md`). El precio es que un cambio de forma en el contrato
 * —pasar los caminos a estilo de flujo, por ejemplo— dejaría al lector sin ver
 * nada; por eso `test_el_contrato_se_puede_leer` exige que encuentre operaciones
 * y al menos una implementada. Una prueba de deriva que no lee nada pasa
 * siempre, y ese es el fallo que hay que impedir.
 */
final class DerivaDelContratoTest extends TestCase
{
    /** Una operación que ya debe existir como ruta. */
    private const IMPLEMENTADA = 'implemented';

    /**
     * El lector no puede quedarse en blanco.
     *
     * Sin esta prueba, un contrato que el lector no entienda —o un `x-status`
     * escrito de otra forma— haría pasar las dos pruebas de deriva sin comparar
     * nada. Es la prueba de la prueba.
     */
    public function test_el_contrato_se_puede_leer(): void
    {
        $contrato = $this->leerContrato();

        $this->assertNotSame(
            '',
            $contrato['prefijo'],
            'No se pudo deducir el prefijo de la API desde `servers:` en el contrato: sin él no hay con qué comparar las rutas.'
        );

        $this->assertNotEmpty(
            $contrato['operaciones'],
            'El lector no encontró ninguna operación bajo `paths:`. Si el contrato cambió de forma, hay que ajustar el lector antes de fiarse de las pruebas de deriva.'
        );

        $this->assertContains(
            self::IMPLEMENTADA,
            $contrato['operaciones'],
            'Ninguna operación del contrato está marcada `x-status: implemented`. O el contrato cambió de convención, o el lector dejó de entender `x-status`.'
        );
    }

    /**
     * Lo que el contrato promete como construido, existe.
     */
    public function test_toda_operacion_implementada_del_contrato_tiene_su_ruta(): void
    {
        $operaciones = $this->leerContrato()['operaciones'];
        $rutas = $this->rutasDeLaApi();

        $faltantes = [];

        foreach ($operaciones as $operacion => $estado) {
            if ($estado === self::IMPLEMENTADA && ! array_key_exists($operacion, $rutas)) {
                $faltantes[] = $operacion;
            }
        }

        $this->assertSame(
            [],
            $faltantes,
            "El contrato declara como implementadas operaciones que no existen como ruta:\n  ".implode("\n  ", $faltantes)
        );
    }

    /**
     * Lo que la API expone, está declarado.
     */
    public function test_ninguna_ruta_de_la_api_queda_fuera_del_contrato(): void
    {
        $operaciones = $this->leerContrato()['operaciones'];
        $rutas = $this->rutasDeLaApi();

        $sobrantes = [];

        foreach ($rutas as $operacion => $accion) {
            if (! array_key_exists($operacion, $operaciones)) {
                $sobrantes[] = $operacion.'  →  '.$accion;
            }
        }

        $this->assertSame(
            [],
            $sobrantes,
            "Hay rutas de la API que el contrato no declara. Ninguna ruta existe sin su operación en `contract/openapi.yaml`:\n  ".implode("\n  ", $sobrantes)
        );
    }

    /**
     * El contrato, ya leído: su prefijo de servidor y sus operaciones.
     *
     * @return array{prefijo: string, operaciones: array<string, string|null>}
     */
    private function leerContrato(): array
    {
        $archivo = dirname(base_path()).'/contract/openapi.yaml';
        $lineas = file($archivo, FILE_IGNORE_NEW_LINES);

        if ($lineas === false) {
            $this->fail(sprintf('No se pudo leer el contrato en %s.', $archivo));
        }

        $prefijo = $this->prefijoDeLaApi($lineas);

        return [
            'prefijo' => $prefijo,
            'operaciones' => $this->leerOperaciones($lineas, $prefijo),
        ];
    }

    /**
     * Las rutas que Laravel tiene registradas bajo `api/`, como «MÉTODO /ruta».
     *
     * Se filtra por el prefijo `api/` y no por el nombre del archivo de rutas
     * porque lo que el contrato cubre es la API pública, no un archivo: una ruta
     * de la API declarada en otro sitio también tiene que estar en el contrato.
     *
     * @return array<string, string>
     */
    private function rutasDeLaApi(): array
    {
        // `Route::getRoutes()` devuelve la colección, no la lista: se pide su
        // contenido con `getRoutes()`, que es el método que declara el tipo de
        // los elementos. Recorrer la colección directamente funciona en tiempo
        // de ejecución, pero su interfaz no promete ser recorrible.
        $coleccion = Route::getRoutes();
        $rutas = [];

        foreach ($coleccion->getRoutes() as $ruta) {
            $uri = $ruta->uri();

            if (! str_starts_with($uri, 'api/')) {
                continue;
            }

            foreach ($ruta->methods() as $metodo) {
                // Laravel añade `HEAD` a cada `GET`. El contrato declara la
                // operación una vez y `HEAD` no es una operación distinta: es la
                // misma sin cuerpo.
                if ($metodo === 'HEAD') {
                    continue;
                }

                $rutas[$metodo.' /'.$uri] = $ruta->getActionName();
            }
        }

        return $rutas;
    }

    /**
     * El prefijo que comparten los servidores del contrato —`/api/v1`—.
     *
     * Los caminos del contrato son relativos a la `url` del servidor; las rutas
     * de Laravel son absolutas. El prefijo sale del propio contrato, y no de una
     * constante escrita aquí, para que mover la versión de la API sea un cambio
     * en el contrato y no en dos sitios.
     *
     * @param  list<string>  $lineas
     */
    private function prefijoDeLaApi(array $lineas): string
    {
        $enServidores = false;

        foreach ($lineas as $linea) {
            if (preg_match('/^servers:\s*$/', $linea) === 1) {
                $enServidores = true;

                continue;
            }

            if (! $enServidores) {
                continue;
            }

            if (preg_match('/^\s*-\s*url:\s*(\S+)\s*$/', $linea, $coincidencias) === 1) {
                $camino = parse_url($coincidencias[1], PHP_URL_PATH);

                return is_string($camino) ? rtrim($camino, '/') : '';
            }
        }

        return '';
    }

    /**
     * Las operaciones del bloque `paths:` del contrato.
     *
     * Entiende la forma de bloque: dos espacios para el camino, cuatro para el
     * método y seis para `x-status`. El estado se guarda tal cual —incluido
     * `null`, cuando la operación no lo declara— porque quien decide qué se exige
     * es la prueba, no el lector.
     *
     * @param  list<string>  $lineas
     * @return array<string, string|null>
     */
    private function leerOperaciones(array $lineas, string $prefijo): array
    {
        $operaciones = [];
        $enCaminos = false;
        $rutaActual = null;
        $metodoActual = null;

        foreach ($lineas as $linea) {
            if (preg_match('/^paths:\s*$/', $linea) === 1) {
                $enCaminos = true;

                continue;
            }

            if (! $enCaminos) {
                continue;
            }

            // Un nombre de primer nivel sin sangría —`components:`, por
            // ejemplo— cierra el bloque: lo que venga después ya no es un camino.
            if ($linea !== '' && ! str_starts_with($linea, ' ') && ! str_starts_with($linea, '#')) {
                break;
            }

            if (preg_match('#^ {2}(/\S*):\s*$#', $linea, $coincidencias) === 1) {
                $rutaActual = $coincidencias[1];
                $metodoActual = null;

                continue;
            }

            if (preg_match('/^ {4}(get|post|put|patch|delete|head|options):\s*$/', $linea, $coincidencias) === 1) {
                if ($rutaActual === null) {
                    continue;
                }

                $metodoActual = strtoupper($coincidencias[1]);
                $operaciones[$metodoActual.' '.$prefijo.$rutaActual] = null;

                continue;
            }

            if (preg_match('/^ {6}x-status:\s*[\'"]?([a-z-]+)[\'"]?\s*$/', $linea, $coincidencias) === 1) {
                if ($rutaActual === null || $metodoActual === null) {
                    continue;
                }

                $operaciones[$metodoActual.' '.$prefijo.$rutaActual] = $coincidencias[1];
            }
        }

        return $operaciones;
    }
}
