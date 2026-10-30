<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\BusquedaRepositoryInterface;
use App\DTOs\Busqueda\ResultadoBusqueda;
use App\Models\Politica;
use App\Models\Tramite;
use App\Support\Busqueda\Extracto;
use Illuminate\Database\Query\Builder as ConsultaBase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use stdClass;

/**
 * La búsqueda de la sede, sobre PostgreSQL y `pg_trgm`.
 *
 * ## Cómo se decide qué es un resultado
 *
 * Un contenido entra si cumple **las tres** condiciones:
 *
 * 1. **Candidato por índice.** `consulta <% texto` —el operador de similitud de
 *    palabra— es la única forma que el índice GIN puede responder; la función
 *    `word_similarity()` recorrería la tabla. El umbral con que lo evalúa el
 *    operador se fija **por transacción** (`set_config(..., true)`): el valor global
 *    de la base (0,6) no encontraría «licensia» (0,556) y cambiarlo afectaría a
 *    toda otra consulta.
 * 2. **Pertinencia mínima.** El mejor entre la similitud con el título y el 60 % de la
 *    similitud con el texto completo alcanza `busqueda.umbral`. El operador es más
 *    laxo que esta condición (compara el texto sin el 60 %), así que se vuelve a
 *    comprobar con la fórmula exacta.
 * 3. **Cobertura por palabra.** Cada palabra de la consulta con cuatro letras o más
 *    aparece en el contenido (con tolerancia a erratas). Es lo que impide que
 *    «licencia de conducción» encuentre «licencia de exhumación»: se midió que ningún
 *    umbral global separaba ese ruido de un acierto legítimo.
 *
 * ## Cómo se ordenan
 *
 * Por pertinencia más un pequeño premio a lo que empieza por el término (0,25) o lo
 * contiene (0,15); a igualdad, por la similitud de la cadena entera —que favorece
 * al título más corto— y por último por título, para que dos páginas seguidas no
 * repitan ni se salten filas.
 *
 * ## Qué se busca
 *
 * Trámites publicados (la misma regla que el catálogo: `Tramite::scopePublicados`) y
 * políticas. Los demás contenidos entrarán cuando existan.
 *
 * ## Privacidad
 *
 * Lo que el ciudadano escribe se usa como parámetro de la consulta y no se guarda,
 * no se escribe en el registro de la aplicación y no se cuenta. El registro de
 * acceso del servidor web es otra capa: ver `docker/nginx/nginx.conf`.
 */
final class BusquedaRepository implements BusquedaRepositoryInterface
{
    private const TRAMITE = 'tramite';

    private const POLITICA = 'politica';

    private const CATEGORIAS = [
        self::TRAMITE => 'Trámite',
        self::POLITICA => 'Política',
    ];

    private const RUTAS = [
        self::TRAMITE => '/tramites/',
        self::POLITICA => '/politicas/',
    ];

    public function sugerir(string $consulta, array $palabras, int $limite): array
    {
        /** @var list<stdClass> $filas */
        $filas = $this->enTransaccion(
            fn (): array => $this->ordenada($this->candidatos($consulta, $palabras, null))
                ->limit($limite)
                ->get()
                ->all(),
        );

        return array_map($this->aResultado(...), array_values($filas));
    }

    public function buscar(string $consulta, array $palabras, ?string $tipo, int $pagina, int $porPagina): LengthAwarePaginator
    {
        /** @var array{total: int, filas: list<stdClass>} $leido */
        $leido = $this->enTransaccion(function () use ($consulta, $palabras, $tipo, $pagina, $porPagina): array {
            $candidatos = $this->candidatos($consulta, $palabras, $tipo);

            return [
                'total' => $candidatos->count(),
                'filas' => array_values($this->ordenada($candidatos)->forPage($pagina, $porPagina)->get()->all()),
            ];
        });

        /** @var LengthAwarePaginator<int, ResultadoBusqueda> $resultado */
        $resultado = new LengthAwarePaginator(
            array_map($this->aResultado(...), $leido['filas']),
            $leido['total'],
            $porPagina,
            $pagina,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );

        // Los enlaces de la página siguiente conservan `q` y `tipo`; sin esto
        // llevarían a una búsqueda vacía.
        return $resultado->withQueryString();
    }

    /**
     * Ejecuta `$accion` con el umbral de similitud de palabra fijado para esa
     * transacción y sólo para ella.
     *
     * @param  callable(): mixed  $accion
     */
    private function enTransaccion(callable $accion): mixed
    {
        return DB::transaction(function () use ($accion): mixed {
            DB::select(
                "select set_config('pg_trgm.word_similarity_threshold', ?, true)",
                [(string) config('busqueda.umbral')],
            );

            return $accion();
        });
    }

    /**
     * Los contenidos que cumplen las tres condiciones, sin orden.
     *
     * @param  list<string>  $palabras
     */
    private function candidatos(string $consulta, array $palabras, ?string $tipo): ConsultaBase
    {
        $union = match ($tipo) {
            null => $this->tramites($consulta, $palabras)->unionAll($this->politicas($consulta, $palabras)),
            self::TRAMITE => $this->tramites($consulta, $palabras),
            self::POLITICA => $this->politicas($consulta, $palabras),
            default => throw new InvalidArgumentException("Tipo de contenido desconocido: {$tipo}"),
        };

        return DB::query()
            ->fromSub($union, 'r')
            ->whereRaw('base >= cast(? as real)', [(float) config('busqueda.umbral')]);
    }

    /**
     * El orden de pertinencia (ver la descripción de la clase).
     */
    private function ordenada(ConsultaBase $consulta): ConsultaBase
    {
        return $consulta
            ->orderByRaw('(base + premio) desc')
            ->orderByDesc('similitud')
            ->orderBy('titulo');
    }

    /**
     * @param  list<string>  $palabras
     */
    private function tramites(string $consulta, array $palabras): ConsultaBase
    {
        $lado = Tramite::query()
            ->publicados()
            ->selectRaw("'tramite' as tipo_recurso")
            ->addSelect(['id', 'slug', 'nombre as titulo'])
            ->selectRaw('resumen as extracto')
            ->selectRaw('cast(publicado_en as date) as fecha');

        return $this->puntuado($lado->toBase(), $consulta, $palabras);
    }

    /**
     * @param  list<string>  $palabras
     */
    private function politicas(string $consulta, array $palabras): ConsultaBase
    {
        $lado = Politica::query()
            ->selectRaw("'politica' as tipo_recurso")
            ->addSelect(['id', 'slug', 'nombre as titulo'])
            // El primer párrafo del primer apartado: lo que la página muestra primero.
            ->selectRaw("secciones->0->'parrafos'->>0 as extracto")
            ->selectRaw('cast(vigente_desde as date) as fecha');

        return $this->puntuado($lado->toBase(), $consulta, $palabras);
    }

    /**
     * Añade a un lado de la unión sus tres puntajes y las tres condiciones.
     *
     * Las dos tablas tienen las mismas columnas de búsqueda (`busqueda_titulo` y
     * `busqueda`), así que la fórmula se escribe una sola vez: dos copias de la
     * fórmula de relevancia terminarían discrepando.
     *
     * @param  list<string>  $palabras
     */
    private function puntuado(ConsultaBase $lado, string $consulta, array $palabras): ConsultaBase
    {
        $lado
            ->selectRaw(
                'greatest(word_similarity(cast(? as text), busqueda_titulo), 0.6 * word_similarity(cast(? as text), busqueda)) as base',
                [$consulta, $consulta],
            )
            ->selectRaw(
                'case when busqueda_titulo like ? then 0.25 when busqueda_titulo like ? then 0.15 else 0 end as premio',
                [$consulta.'%', '%'.$consulta.'%'],
            )
            ->selectRaw('similarity(cast(? as text), busqueda_titulo) as similitud', [$consulta])
            ->whereRaw(
                '(cast(? as text) <% busqueda_titulo or cast(? as text) <% busqueda)',
                [$consulta, $consulta],
            );

        foreach ($palabras as $palabra) {
            $lado->whereRaw(
                'word_similarity(cast(? as text), busqueda) >= cast(? as real)',
                [$palabra, (float) config('busqueda.cobertura_palabra')],
            );
        }

        return $lado;
    }

    private function aResultado(stdClass $fila): ResultadoBusqueda
    {
        $tipo = (string) $fila->tipo_recurso;
        $extracto = is_string($fila->extracto) ? $fila->extracto : null;

        return new ResultadoBusqueda(
            tipoRecurso: $tipo,
            id: (int) $fila->id,
            titulo: (string) $fila->titulo,
            categoria: self::CATEGORIAS[$tipo],
            extracto: Extracto::recortar($extracto, (int) config('busqueda.extracto_maximo')),
            fecha: is_string($fila->fecha) ? $fila->fecha : null,
            url: self::RUTAS[$tipo].(string) $fila->slug,
            score: round((float) $fila->base + (float) $fila->premio, 3),
        );
    }
}
