<?php

declare(strict_types=1);

namespace App\Services\GovCo;

use App\Exceptions\FuenteNoDisponible;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

/**
 * El acceso HTTP a la API de contenido de GOV.CO.
 *
 * Es la única clase que sabe cómo se habla con la fuente: sus cabeceras, su
 * formato de ruta, su forma de decir «no existe» y su forma de decir «basta». Todo
 * lo demás de la aplicación le pide una ruta y recibe datos ya decodificados.
 *
 * # Las tres cabeceras no son opcionales
 *
 * La fuente responde **HTTP 403** sin `User-Agent` y sin `Referer:
 * https://www.gov.co/`. Comprobado con `curl`. Se declaran aquí y no en la llamada
 * para que no puedan olvidarse desde un sitio nuevo.
 *
 * # Por qué hay espera exponencial si no se pudo reproducir el límite de tasa
 *
 * La recolección anterior dejó anotado que la fuente empieza a devolver **404 con
 * el cuerpo vacío** tras unas treinta peticiones seguidas y se recupera esperando.
 * En la comprobación previa a escribir esto —**300 peticiones seguidas, todas
 * 200**— ese límite **no se reprodujo**, y conviene decirlo en vez de dar por
 * bueno un dato que no se pudo confirmar.
 *
 * Aun así la espera exponencial se implementa, por dos razones que no dependen de
 * que el umbral sea 30: primero, porque una recolección completa son más de mil
 * peticiones y un corte a mitad de camino cuesta más que cualquier espera;
 * segundo, porque el `404` vacío es indistinguible de un trámite inexistente si no
 * se mira el cuerpo, y tratarlo como inexistente borraría trámites buenos del
 * catálogo. El umbral puede ser otro; la defensa es la misma.
 *
 * El tope de reintentos es una constante pública y **cada espera se escribe en el
 * log** con su número de intento y el tope: un reintento que no se ve es un
 * reintento que nadie puede distinguir de una lentitud.
 */
final class ClienteFichaGovCo
{
    /** La base del servicio. Es pública y no se configura: no hay otro entorno. */
    public const BASE = 'https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/';

    /**
     * El tope de reintentos por petición, declarado y publicado.
     *
     * Se para en cinco y no se reintenta sin fin porque una fuente que lleva cinco
     * esperas sin contestar no está limitando la tasa: está caída, y contra eso no
     * sirve insistir.
     */
    public const MAXIMO_REINTENTOS = 5;

    /** La primera espera, en milisegundos. Cada reintento la duplica. */
    private const ESPERA_INICIAL_MS = 800;

    /**
     * El techo de la espera, en milisegundos.
     *
     * Sin techo, la quinta espera de una serie larga pasaría del minuto y el
     * comando parecería colgado. Treinta segundos es el mismo orden que el
     * `timeout` de la petición, así que una espera nunca dura más que la petición
     * que la sigue.
     */
    private const ESPERA_MAXIMA_MS = 30_000;

    /**
     * La fuente rechaza a quien no se anuncia como navegador.
     *
     * No se imita a un navegador concreto ni se finge una versión: se dice lo que
     * es —un cliente de la sede electrónica— con la misma forma que usa un
     * navegador, que es lo que la fuente comprueba.
     */
    private const AGENTE = 'Mozilla/5.0 (compatible; SedeElectronicaSantaMarta/1.0; +https://www.santamarta.gov.co)';

    private const REFERENTE = 'https://www.gov.co/';

    /**
     * Cuántas veces se esperó por un `404` de cuerpo vacío en toda la ejecución.
     *
     * Lo cuenta el cliente y lo publica el informe: si la fuente empezara a
     * limitar de verdad, este número es lo primero que lo diría.
     */
    private int $esperasPorTasa = 0;

    /**
     * @param  int  $maximoReintentos  El tope por petición. Se puede bajar para probar.
     * @param  (Closure(int): void)|null  $dormir  Cómo se espera. Inyectable para que una
     *                                             prueba no duerma de verdad: probar la
     *                                             espera exponencial durmiendo haría que
     *                                             la suite tardara minutos.
     */
    public function __construct(
        private readonly int $maximoReintentos = self::MAXIMO_REINTENTOS,
        private readonly ?Closure $dormir = null,
    ) {}

    /** Cuántas esperas por límite de tasa acumuló esta instancia. */
    public function esperasPorTasa(): int
    {
        return $this->esperasPorTasa;
    }

    /**
     * Pide una ruta de la fuente y devuelve el JSON ya decodificado.
     *
     * Devuelve **nulo** cuando la fuente dice que el trámite no existe —un `404`
     * con cuerpo—, porque eso no es un fallo: es una respuesta. Lanza cuando la
     * fuente no está o cuando se agotaron los reintentos.
     *
     *
     * @throws FuenteNoDisponible
     */
    public function pedir(string $ruta, string $codigo): mixed
    {
        $intentos = 0;

        for ($intento = 1; $intento <= $this->maximoReintentos; $intento++) {
            $intentos = $intento;

            try {
                $respuesta = Http::withHeaders([
                    'User-Agent' => self::AGENTE,
                    'Referer' => self::REFERENTE,
                    // La fuente devuelve `text/html` en algún error; aceptar JSON
                    // explícitamente evita que el framework decida por nosotros.
                    'Accept' => 'application/json, text/plain, */*',
                ])
                    ->timeout(20)
                    ->get(self::BASE.$ruta);
            } catch (ConnectionException $fallo) {
                if ($intento === $this->maximoReintentos) {
                    throw FuenteNoDisponible::porConexion($codigo, $ruta, $fallo->getMessage(), $intentos);
                }

                $this->esperar($intento, $ruta, $codigo, 'no hubo conexión');

                continue;
            }

            if ($respuesta->successful()) {
                try {
                    return json_decode($respuesta->body(), true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $fallo) {
                    // Un JSON ilegible no mejora reintentando: la fuente contestó
                    // algo y ese algo no es lo que promete.
                    throw FuenteNoDisponible::porCuerpoIlegible($codigo, $ruta);
                }
            }

            // El `404` de la fuente tiene dos significados y se distinguen por el
            // cuerpo. Con cuerpo, el trámite no existe: se devuelve nulo y se
            // sigue. Vacío, es el límite de tasa: se espera y se reintenta. Sin
            // esta distinción, una tanda de `404` vacíos borraría del catálogo
            // trámites que sí existen.
            if ($respuesta->status() === 404) {
                if (trim($respuesta->body()) === '') {
                    if ($intento < $this->maximoReintentos) {
                        $this->esperar($intento, $ruta, $codigo, 'la fuente devolvió 404 sin cuerpo (límite de tasa)');

                        continue;
                    }

                    throw FuenteNoDisponible::porRespuesta($codigo, $ruta, 404, $intentos);
                }

                return null;
            }

            // Un 5xx o un 429 son «vuelve luego»; un 4xx que no sea 404 es «has
            // pedido mal» y no mejora insistiendo.
            if ($respuesta->serverError() || $respuesta->status() === 429) {
                if ($intento === $this->maximoReintentos) {
                    throw FuenteNoDisponible::porRespuesta($codigo, $ruta, $respuesta->status(), $intentos);
                }

                $this->esperar($intento, $ruta, $codigo, sprintf('la fuente respondió %d', $respuesta->status()));

                continue;
            }

            throw FuenteNoDisponible::porRespuesta($codigo, $ruta, $respuesta->status(), $intentos);
        }

        // Inalcanzable con `maximoReintentos >= 1`, pero el análisis estático no
        // puede saberlo y una excepción es mejor que un nulo silencioso.
        throw FuenteNoDisponible::porRespuesta($codigo, $ruta, 0, $intentos);
    }

    /**
     * Espera antes del siguiente intento, duplicando el tiempo cada vez.
     *
     * El número de intento y el tope viajan en el log a propósito: un reintento
     * invisible es indistinguible de una fuente lenta, y quien lee el log necesita
     * saber si el comando va a terminar o va a rendirse.
     */
    private function esperar(int $intento, string $ruta, string $codigo, string $motivo): void
    {
        $espera = min(self::ESPERA_MAXIMA_MS, self::ESPERA_INICIAL_MS * (2 ** ($intento - 1)));

        $this->esperasPorTasa++;

        Log::warning('Ingesta de trámites: se espera antes de reintentar.', [
            'tramite' => $codigo,
            'ruta' => $ruta,
            'motivo' => $motivo,
            'intento' => $intento,
            'tope_de_reintentos' => $this->maximoReintentos,
            'espera_ms' => $espera,
            'esperas_acumuladas' => $this->esperasPorTasa,
        ]);

        $dormir = $this->dormir;

        if ($dormir instanceof Closure) {
            $dormir($espera);

            return;
        }

        usleep($espera * 1000);
    }

    /**
     * El número del trámite tal como lo espera la fuente.
     *
     * El catálogo llama a los trámites `T2621`; la API sólo entiende `2621`. Con
     * el prefijo devuelve `{"data":null}` o un `404` que dice «Tramite no existe»,
     * así que el error parece del trámite y es de la forma del identificador.
     *
     * Se acepta cualquiera de las dos formas porque las dos circulan por el
     * proyecto —el JSON versionado usa `T#####` y las rutas de la API usan el
     * número— y convertir en cada llamada es cómo se olvida una.
     */
    public static function numero(string $codigo): string
    {
        return str_starts_with(mb_strtoupper($codigo), 'T')
            ? mb_substr($codigo, 1)
            : $codigo;
    }

    /**
     * El cuerpo de una respuesta que puede ser nula.
     *
     * La fuente responde `null` —no una lista vacía— cuando no tiene el dato, y
     * el `data: null` de algunos endpoints hace lo mismo. Tratar los dos como
     * «lista vacía» evita repetir la comprobación en cada llamada.
     *
     * @return list<array<string, mixed>>
     */
    public static function lista(mixed $respuesta): array
    {
        if (! is_array($respuesta)) {
            return [];
        }

        /** @var list<array<string, mixed>> $filas */
        $filas = array_values(array_filter($respuesta, 'is_array'));

        return $filas;
    }

    /**
     * Un objeto de una respuesta que puede ser nula.
     *
     * @return array<string, mixed>
     */
    public static function objeto(mixed $respuesta): array
    {
        if (! is_array($respuesta)) {
            return [];
        }

        /** @var array<string, mixed> $datos */
        $datos = array_filter($respuesta, static fn (mixed $valor): bool => ! is_array($valor) || $valor !== []);

        return $datos;
    }

    /**
     * Un texto de un bloque crudo, ya recortado, o nulo si viene vacío.
     *
     * La fuente usa la cadena vacía para decir «no hay dato» —`Correo: ""`— y el
     * blanco suelto en los términos de tiempo. Tratar el vacío como ausencia es
     * parte de la lectura y no un adorno: es lo que impide publicar un horario en
     * blanco como si fuera un horario.
     *
     * @param  array<string, mixed>  $bloque
     */
    public static function texto(array $bloque, string $clave): ?string
    {
        $valor = $bloque[$clave] ?? null;

        if (is_int($valor) || is_float($valor)) {
            $valor = (string) $valor;
        }

        if (! is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return $valor === '' ? null : $valor;
    }

    /**
     * Un número decimal de un bloque crudo, o nulo.
     *
     * Acepta la coma decimal porque la fuente no es constante en eso entre fichas,
     * y devuelve `float` sólo para coordenadas: un importe nunca pasa por aquí, que
     * para el dinero está la columna decimal.
     *
     * @param  array<string, mixed>  $bloque
     */
    public static function numeroDecimal(array $bloque, string $clave): ?float
    {
        $valor = $bloque[$clave] ?? null;

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        if (! is_string($valor)) {
            return null;
        }

        $valor = str_replace(',', '.', trim($valor));

        return is_numeric($valor) ? (float) $valor : null;
    }

    /**
     * Un entero de un bloque crudo, o nulo.
     *
     * La fuente entrega números como `1.0` —la cantidad de un documento— y como
     * texto —el año de una norma—, así que se aceptan las dos formas.
     *
     * @param  array<string, mixed>  $bloque
     */
    public static function entero(array $bloque, string $clave): ?int
    {
        $valor = $bloque[$clave] ?? null;

        if (is_int($valor)) {
            return $valor;
        }

        if (is_float($valor)) {
            return (int) $valor;
        }

        if (is_string($valor) && is_numeric(trim($valor))) {
            return (int) (float) trim($valor);
        }

        return null;
    }

    /**
     * Si un endpoint falló sin tumbar la recolección del trámite.
     *
     * El cliente lanza cuando la fuente no está, y la fuente del Estado no está a
     * menudo para un endpoint concreto: `GetDataFichaResult` responde **HTTP 500**
     * para algunos trámites y `GetPagosByMomentoIdAudiencia` responde 500 para
     * todos los que se probaron. Perder la ficha entera por eso sería peor que
     * publicarla sin ese dato, así que la ingesta envuelve cada llamada opcional y
     * anota el bloque como ausente.
     *
     * @param  callable(): mixed  $llamada
     * @return array{valor: mixed, fallo: string|null}
     */
    public static function opcional(callable $llamada): array
    {
        try {
            return ['valor' => $llamada(), 'fallo' => null];
        } catch (FuenteNoDisponible $fallo) {
            return ['valor' => null, 'fallo' => $fallo->getMessage()];
        } catch (Throwable $fallo) {
            return ['valor' => null, 'fallo' => $fallo->getMessage()];
        }
    }
}
