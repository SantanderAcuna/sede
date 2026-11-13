<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\TramitePanelRepositoryInterface;
use App\Contracts\Services\TramitePanelServiceInterface;
use App\Exceptions\TramiteIncompleto;
use App\Models\Tramite;
use App\Models\TramiteCategoria;
use App\Support\Tramites\FiltrosTramitePanel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Alta, edición, publicación y borrado de trámites, OPA y consultas desde el panel.
 *
 * Reglas que viven aquí y no en el controlador:
 *  - Un elemento puede existir incompleto (borrador); **publicarlo** exige el contenido propio de
 *    una ficha (modalidad, costo, tiempo, canal, cómo se consulta el estado y al menos un requisito)
 *    y, para trámites y OPA, la ficha de GOV.CO (la integración nacional, RF-B1-021).
 *  - Todo elemento se **crea, edita y borra** (papelera): no hay candado por origen.
 *  - El código y la dirección (`slug`) no cambian al editar.
 *  - La ficha se recibe en la **misma forma relacional** con la que el panel la lee (pasos,
 *    requisitos con su paso, documentos, puntos, normas, canales, solicitantes, cuentas del costo),
 *    y se escribe con `EscritorFichaRelacional`: abrir un trámite y guardarlo sin tocarlo no pierde
 *    ni reordena nada.
 */
final class TramitePanelService implements TramitePanelServiceInterface
{
    /** Los campos escalares que se guardan tal cual en su columna. */
    private const DIRECTOS = [
        'nombre', 'resumen', 'type', 'url_ficha_gov_co', 'modalidad', 'tiene_costo',
        'tiempo_solucion_dias', 'canal_inicio', 'url_inicio', 'url_manual_tramite_en_linea',
        'consulta_estado', 'resultado', 'observaciones_resultado', 'fecha_cualquiera',
        'cuando_se_puede_realizar', 'url_calendario', 'medios_resultado', 'palabras_relacionadas',
        'dependencia_id',
    ];

    /** Las claves de la ficha relacional que el formulario envía. */
    private const CLAVES_FICHA = [
        'pasos', 'requisitos', 'documentos', 'puntos_atencion', 'normativa',
        'canales_consulta_estado', 'solicitantes',
    ];

    public function __construct(private readonly TramitePanelRepositoryInterface $tramites) {}

    public function listar(int $porPagina, FiltrosTramitePanel $filtros): LengthAwarePaginator
    {
        return $this->tramites->paginar($porPagina, $filtros);
    }

    public function porUuid(string $uuid): ?Tramite
    {
        return $this->tramites->porUuid($uuid);
    }

    public function crear(array $datos): Tramite
    {
        $tramite = new Tramite;
        $tipo = is_string($datos['type'] ?? null) ? $datos['type'] : 'tramites';
        $tramite->type = $tipo;
        $tramite->codigo = $this->tramites->siguienteCodigo($tipo);
        $tramite->slug = $this->slugUnico((string) $datos['nombre']);

        return $this->guardar($tramite, $datos);
    }

    public function actualizar(Tramite $tramite, array $datos): Tramite
    {
        return $this->guardar($tramite, $datos);
    }

    public function eliminar(Tramite $tramite): void
    {
        // Todo elemento es borrable: va a la papelera (soft delete). No hay excepción por origen.
        $this->tramites->eliminar($tramite);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function guardar(Tramite $tramite, array $datos): Tramite
    {
        foreach (self::DIRECTOS as $campo) {
            if (array_key_exists($campo, $datos)) {
                $tramite->{$campo} = $datos[$campo];
            }
        }

        if (array_key_exists('costo', $datos)) {
            $this->aplicarCosto($tramite, is_array($datos['costo']) ? $datos['costo'] : null);
        }
        if (array_key_exists('categoria', $datos)) {
            $tramite->categoria_id = $this->resolverCategoria(is_array($datos['categoria']) ? $datos['categoria'] : null);
        }

        // La ficha se reescribe sólo cuando el formulario la trae (el formulario envía la ficha
        // entera): así una edición que no la toque no la borra.
        $ficha = $this->traeFicha($datos) ? $this->fichaDesdeSubmit($datos) : null;

        $this->resolverPublicacion($tramite, $datos, $ficha);

        return $this->tramites->guardar($tramite, $ficha)->refresh()->load(['categoria', 'dependencia', ...Tramite::RELACIONES_FICHA]);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array{requisitos: list<mixed>, ...}|null  $ficha
     */
    private function resolverPublicacion(Tramite $tramite, array $datos, ?array $ficha): void
    {
        $pide = array_key_exists('publicado', $datos) ? ($datos['publicado'] === true) : null;

        if ($pide === false) {
            $tramite->publicado_en = null;

            return;
        }
        if ($pide === null && $tramite->publicado_en === null) {
            return;
        }

        $problemas = $this->problemasConFicha($tramite, $ficha);
        if ($problemas !== []) {
            throw new TramiteIncompleto($problemas);
        }
        $tramite->publicado_en ??= Carbon::now();
    }

    /**
     * Los problemas de publicación, contando los requisitos de la ficha recién enviada (que aún no
     * están en la base en una creación).
     *
     * @param  array{requisitos: list<mixed>, ...}|null  $ficha
     * @return list<string>
     */
    private function problemasConFicha(Tramite $tramite, ?array $ficha): array
    {
        $problemas = array_values(array_filter(
            $tramite->problemasParaPublicar(),
            static fn (string $p): bool => $p !== 'Falta al menos un requisito.',
        ));

        $tieneRequisitos = $ficha !== null ? $ficha['requisitos'] !== [] : $tramite->requisitos()->exists();
        if (! $tieneRequisitos) {
            $problemas[] = 'Falta al menos un requisito.';
        }

        return $problemas;
    }

    /**
     * @param  array<string, mixed>|null  $costo
     */
    private function aplicarCosto(Tramite $tramite, ?array $costo): void
    {
        $tramite->costo = $costo['valor'] ?? null;
        $tramite->costo_tipo_valor = $costo['tipo_valor'] ?? null;
        $tramite->costo_moneda = $costo['moneda'] ?? null;
        $tramite->costo_url_pago = $costo['url_pago'] ?? null;
        $tramite->costo_descripcion = $costo['descripcion'] ?? null;
        if (isset($costo['tiene_costo'])) {
            $tramite->tiene_costo = $costo['tiene_costo'];
        }
    }

    /**
     * El id del tema, buscándolo por su slug o creándolo si es nuevo. Nulo cuando no se declara.
     *
     * @param  array<string, mixed>|null  $categoria
     */
    private function resolverCategoria(?array $categoria): ?int
    {
        $slug = isset($categoria['slug']) && is_string($categoria['slug']) ? $categoria['slug'] : null;
        if ($slug === null || $slug === '') {
            return null;
        }
        $nombre = isset($categoria['nombre']) && is_string($categoria['nombre']) ? $categoria['nombre'] : $slug;

        return TramiteCategoria::query()->firstOrCreate(['slug' => $slug], ['nombre' => $nombre])->id;
    }

    /** @param array<string, mixed> $datos */
    private function traeFicha(array $datos): bool
    {
        foreach (self::CLAVES_FICHA as $clave) {
            if (array_key_exists($clave, $datos)) {
                return true;
            }
        }

        return is_array($datos['costo'] ?? null) && array_key_exists('cuentas', $datos['costo']);
    }

    /**
     * Convierte el envío relacional del formulario en la forma que `EscritorFichaRelacional` espera.
     *
     * @param  array<string, mixed>  $datos
     * @return array{
     *     pasos: list<array{orden: int, titulo: string, descripcion: string|null}>,
     *     requisitos: list<array{orden: int, tipo: string, descripcion: string, obligatorio: bool, nota: string|null, url: string|null, paso: int|null}>,
     *     documentos: list<array<string, mixed>>,
     *     puntos_atencion: list<array<string, mixed>>,
     *     normativa: list<array<string, mixed>>,
     *     canales_consulta_estado: list<array<string, mixed>>,
     *     cuentas: list<array<string, mixed>>,
     *     audiencias: list<string>
     * }
     */
    private function fichaDesdeSubmit(array $datos): array
    {
        $pasos = [];
        foreach (array_values(is_array($datos['pasos'] ?? null) ? $datos['pasos'] : []) as $i => $p) {
            if (! is_array($p)) {
                continue;
            }
            $pasos[] = [
                'orden' => (int) ($p['orden'] ?? $i + 1),
                'titulo' => (string) ($p['titulo'] ?? ''),
                'descripcion' => isset($p['descripcion']) ? (string) $p['descripcion'] : null,
            ];
        }

        $requisitos = [];
        foreach (array_values(is_array($datos['requisitos'] ?? null) ? $datos['requisitos'] : []) as $i => $r) {
            if (! is_array($r)) {
                continue;
            }
            $requisitos[] = [
                'orden' => (int) ($r['orden'] ?? $i + 1),
                'tipo' => (string) ($r['tipo'] ?? 'documento'),
                'descripcion' => (string) ($r['descripcion'] ?? ''),
                'obligatorio' => ! array_key_exists('obligatorio', $r) || $r['obligatorio'] === null ? true : (bool) $r['obligatorio'],
                'nota' => isset($r['nota']) ? (string) $r['nota'] : null,
                'url' => isset($r['url']) ? (string) $r['url'] : null,
                'paso' => isset($r['paso']) ? (int) $r['paso'] : null,
            ];
        }

        /** @var array<string, mixed> $costo */
        $costo = is_array($datos['costo'] ?? null) ? $datos['costo'] : [];

        return [
            'pasos' => $pasos,
            'requisitos' => $requisitos,
            'documentos' => $this->filas($datos['documentos'] ?? null, ['nombre', 'url', 'formato']),
            'puntos_atencion' => $this->filas($datos['puntos_atencion'] ?? null, ['nombre', 'direccion', 'telefono', 'horario', 'municipio', 'departamento', 'latitud', 'longitud']),
            'normativa' => $this->filas($datos['normativa'] ?? null, ['tipo', 'numero', 'anio', 'articulos', 'url']),
            'canales_consulta_estado' => $this->filas($datos['canales_consulta_estado'] ?? null, ['canal', 'habilitado', 'nombre', 'url', 'correo', 'telefono', 'horario']),
            'cuentas' => $this->filas($costo['cuentas'] ?? null, ['banco', 'tipo', 'numero', 'titular']),
            'audiencias' => array_values(array_filter(
                is_array($datos['solicitantes'] ?? null) ? $datos['solicitantes'] : [],
                'is_string',
            )),
        ];
    }

    /**
     * Toma de cada fila sólo las claves permitidas, en el orden en que llegan.
     *
     * @param  list<string>  $claves
     * @return list<array<string, mixed>>
     */
    private function filas(mixed $filas, array $claves): array
    {
        if (! is_array($filas)) {
            return [];
        }
        $salida = [];
        foreach (array_values($filas) as $fila) {
            if (! is_array($fila)) {
                continue;
            }
            $limpia = [];
            foreach ($claves as $clave) {
                if (array_key_exists($clave, $fila)) {
                    $limpia[$clave] = $fila[$clave];
                }
            }
            $salida[] = $limpia;
        }

        return $salida;
    }

    private function slugUnico(string $nombre): string
    {
        $base = Str::limit(Str::slug($nombre), 150, '') ?: 'tramite';
        $slug = $base;

        for ($n = 2; $this->tramites->existeSlug($slug); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
