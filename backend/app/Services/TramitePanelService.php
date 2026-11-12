<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\TramitePanelRepositoryInterface;
use App\Contracts\Services\TramitePanelServiceInterface;
use App\Exceptions\TramiteIncompleto;
use App\Exceptions\TramiteNoSePuedeEliminar;
use App\Models\Tramite;
use App\Support\Tramites\FiltrosTramitePanel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Alta, edición y publicación de trámites desde el panel.
 *
 * Reglas que viven aquí y no en el controlador:
 *  - Un trámite puede existir incompleto (borrador); publicarlo exige la ficha GOV.CO y los
 *    seis atributos obligatorios.
 *  - Lo creado o editado en el panel declara su propia fuente: la ingesta automática solo
 *    reescribe lo que declara la fuente oficial, así que no pisa lo corregido a mano.
 *  - El código y la dirección (slug) no cambian al editar: el slug es el enlace de la ficha.
 */
final class TramitePanelService implements TramitePanelServiceInterface
{
    /** Los campos que se guardan tal cual en su columna. */
    private const DIRECTOS = [
        'nombre', 'resumen', 'url_ficha_gov_co', 'modalidad', 'tiene_costo', 'tiempo_solucion_dias',
        'canal_inicio', 'url_inicio', 'url_manual_tramite_en_linea', 'consulta_estado', 'requisitos',
        'documentos', 'momentos', 'resultado', 'producto_final', 'observaciones_resultado',
        'fecha_cualquiera', 'cuando_se_puede_realizar', 'url_calendario', 'medios_resultado',
        'palabras_relacionadas', 'audiencias', 'perfiles', 'puntos_atencion', 'normativa', 'cuentas',
        'canales_consulta_estado', 'seguimiento',
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
        $tramite->codigo = $this->tramites->siguienteCodigo();
        $tramite->slug = $this->slugUnico((string) $datos['nombre']);

        return $this->guardar($tramite, $datos);
    }

    public function actualizar(Tramite $tramite, array $datos): Tramite
    {
        return $this->guardar($tramite, $datos);
    }

    public function eliminar(Tramite $tramite): void
    {
        if (! $tramite->esDelPanel()) {
            throw new TramiteNoSePuedeEliminar;
        }

        $this->tramites->eliminar($tramite);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function guardar(Tramite $tramite, array $datos): Tramite
    {
        $cambiados = [];
        foreach (self::DIRECTOS as $campo) {
            if (array_key_exists($campo, $datos)) {
                $tramite->{$campo} = $datos[$campo];
                $cambiados[] = $campo;
            }
        }

        if (array_key_exists('costo', $datos)) {
            $this->aplicarCosto($tramite, is_array($datos['costo']) ? $datos['costo'] : null);
            $cambiados[] = 'costo';
        }
        if (array_key_exists('categoria', $datos)) {
            $categoria = is_array($datos['categoria']) ? $datos['categoria'] : null;
            $tramite->categoria_slug = $categoria['slug'] ?? null;
            $tramite->categoria_nombre = $categoria['nombre'] ?? null;
            $cambiados[] = 'categoria';
        }

        $this->marcarProcedencia($tramite, $cambiados);

        if (array_key_exists('publicado', $datos)) {
            if ($datos['publicado'] === true) {
                $problemas = $tramite->problemasParaPublicar();
                if ($problemas !== []) {
                    throw new TramiteIncompleto($problemas);
                }
                // Una vez publicado se conserva la fecha original.
                $tramite->publicado_en ??= Carbon::now();
            } else {
                $tramite->publicado_en = null;
            }
        } elseif ($tramite->publicado_en !== null) {
            // Editar algo publicado no puede dejarlo publicado e incompleto.
            $problemas = $tramite->problemasParaPublicar();
            if ($problemas !== []) {
                throw new TramiteIncompleto($problemas);
            }
        }

        return $this->tramites->guardar($tramite)->refresh();
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
        $tramite->costo_cuentas = $costo['cuentas'] ?? [];
        if (isset($costo['tiene_costo'])) {
            $tramite->tiene_costo = $costo['tiene_costo'];
        }
    }

    /**
     * Declara el panel como fuente y anota, campo por campo, qué cambió la Entidad. Se
     * conserva el resto de la procedencia (url, api, derivados) como referencia histórica.
     *
     * @param  list<string>  $cambiados
     */
    private function marcarProcedencia(Tramite $tramite, array $cambiados): void
    {
        $tramite->procedencia_fuente = Tramite::FUENTE_PANEL;
        $tramite->procedencia_obtenido_en = Carbon::today();

        $origen = $tramite->procedencia_origen_por_campo ?? [];
        foreach ($cambiados as $campo) {
            $origen[$campo] = 'entidad';
        }
        $tramite->procedencia_origen_por_campo = $origen;
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
