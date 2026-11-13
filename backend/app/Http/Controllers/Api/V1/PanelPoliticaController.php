<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPoliticaRequest;
use App\Http\Requests\CrearPoliticaRequest;
use App\Models\FileMedia;
use App\Models\Politica;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Edición de las políticas desde el panel (T-20). Cada acción exige su permiso (ver `CatalogoPermisos`).
 *
 * @tags Panel — Políticas
 */
final class PanelPoliticaController extends Controller
{
    /**
     * Las políticas con su estado: si están completas y qué les falta.
     *
     * @operationId listarPoliticasPanel
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Politica::class);

        $orden = array_keys(Politica::COMPONENTES);

        // Las cinco de base primero, en su orden; después las propias, de la más antigua a la más nueva.
        $politicas = Politica::query()->orderBy('id')->get()
            ->sortBy(static function (Politica $p) use ($orden): int {
                $posicion = array_search($p->slug, $orden, true);

                return $posicion === false ? count($orden) + $p->id : $posicion;
            })
            ->values()
            ->map(static fn (Politica $p): array => self::elemento($p))
            ->all();

        return ApiResponse::ok($politicas);
    }

    /**
     * Crea una política propia de la Entidad (además de las cinco obligatorias).
     *
     * Nace como borrador oculto y con la marca de texto de ejemplo: se publica solo cuando
     * está adoptada (texto definitivo, acto administrativo completo) y quien lo hace tiene
     * el permiso de publicar. La dirección (slug) sale del nombre y no se repite.
     *
     * @operationId crearPolitica
     */
    public function store(CrearPoliticaRequest $peticion): JsonResponse
    {
        Gate::authorize('create', Politica::class);

        $datos = $peticion->validated();
        $publicada = (bool) ($datos['publicada'] ?? false);

        $politica = new Politica($datos);
        $politica->slug = self::slugDisponible((string) $datos['nombre']);
        $politica->version = (int) ($datos['version'] ?? 1);
        $politica->es_ejemplo = (bool) ($datos['es_ejemplo'] ?? true);
        $politica->publicada = $publicada;

        if ($publicada) {
            Gate::authorize('publish', $politica);
        }

        $problemas = $politica->problemas();
        if ($problemas !== []) {
            return ApiResponse::validationError(['politica' => $problemas]);
        }

        $politica->save();

        return ApiResponse::created(self::elemento($politica->refresh()), 'Política creada correctamente.');
    }

    /**
     * Elimina una política propia y el documento oficial que tenía adjunto.
     * Las cinco obligatorias no se eliminan.
     *
     * @operationId eliminarPolitica
     */
    public function destroy(string $slug): JsonResponse
    {
        $politica = Politica::query()->where('slug', $slug)->first();
        if ($politica === null) {
            return ApiResponse::notFound('La política no existe.');
        }
        if ($politica->esBase()) {
            Gate::authorize('view', $politica);

            return ApiResponse::validationError(['politica' => ['Las cinco políticas obligatorias no se pueden eliminar.']]);
        }
        Gate::authorize('delete', $politica);

        DB::transaction(static function () use ($politica): void {
            FileMedia::query()
                ->where('model_type', Politica::class)
                ->where('model_uuid', $politica->uuid)
                ->get()
                ->each(static fn (FileMedia $archivo) => $archivo->deleteFully());
            $politica->delete();
        });

        return ApiResponse::ok(null, 'Política eliminada correctamente.');
    }

    /**
     * Guarda cambios en una política.
     *
     * Si cambia el texto y no se sube la versión, la versión sube sola: un texto
     * distinto es una versión distinta (y, en cookies, vuelve a pedir el
     * consentimiento). Si el resultado no queda completo —un apartado vacío, o un
     * acto administrativo incompleto al quitar la marca de ejemplo— no se guarda.
     *
     * @operationId actualizarPolitica
     */
    public function update(ActualizarPoliticaRequest $peticion, string $slug): JsonResponse
    {
        $politica = Politica::query()->where('slug', $slug)->first();
        if ($politica === null) {
            return ApiResponse::notFound('La política no existe.');
        }
        Gate::authorize('update', $politica);

        $datos = $peticion->validated();
        $textoAntes = json_encode($politica->secciones, JSON_THROW_ON_ERROR);

        $publicadaAntes = $politica->publicada;
        $politica->fill($datos);

        if ($politica->publicada !== $publicadaAntes) {
            Gate::authorize('publish', $politica);
            if ($politica->esBase() && ! $politica->publicada) {
                return ApiResponse::validationError(['politica' => ['Las cinco políticas obligatorias no se pueden ocultar.']]);
            }
        }

        $textoCambio = $textoAntes !== json_encode($politica->secciones, JSON_THROW_ON_ERROR);
        if ($textoCambio && ! array_key_exists('version', $datos)) {
            $politica->version++;
        }

        $problemas = $politica->problemas();
        if ($problemas !== []) {
            return ApiResponse::validationError(['politica' => $problemas]);
        }

        $politica->save();

        return ApiResponse::ok(self::elemento($politica->refresh()), 'Política actualizada correctamente.');
    }

    private static function slugDisponible(string $nombre): string
    {
        $base = Str::limit(Str::slug($nombre), 90, '') ?: 'politica';
        $slug = $base;
        // «nueva» es la ruta del formulario de creación en el panel: ninguna política puede llamarse así.
        for ($n = 2; $slug === 'nueva' || Politica::query()->where('slug', $slug)->exists(); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }

    /**
     * La política tal como la ve el panel: el documento público más lo que hace
     * falta para editarla (el `uuid` con que se le adjunta el documento oficial,
     * los apartados exigidos y qué le falta).
     *
     * @return array<string, mixed>
     */
    private static function elemento(Politica $politica): array
    {
        $problemas = $politica->problemas();

        return $politica->comoArreglo() + [
            'uuid' => $politica->uuid,
            'componentes' => Politica::COMPONENTES[$politica->slug] ?? [],
            'completa' => $problemas === [],
            'problemas' => $problemas,
        ];
    }
}
