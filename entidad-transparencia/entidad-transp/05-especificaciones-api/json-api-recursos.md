# JSON:API Resources — Detalle de Implementación

> **Marco:** JSON:API 1.0 (ADR-004) implementado con `App\Http\Resources\JsonResource` de Laravel 13.
> **Trazabilidad:** cada Resource referencia los RF y la tabla que modela.

---

## 1. Clase base `JsonApiResource`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class JsonApiResource extends JsonResource
{
    /** Tipo del recurso (singular, kebab-case) */
    abstract protected function type(): string;

    /** ID del recurso */
    public function id(): string
    {
        return (string) $this->resource->getKey();
    }

    /** Atributos expuestos (sobrescribir en cada subclase) */
    abstract protected function attributes(): array;

    /** Relaciones (sobrescribir si aplica) */
    protected function relationships(): array
    {
        return [];
    }

    /** Links del recurso individual */
    protected function links(): array
    {
        return [];
    }

    /** Meta del recurso individual */
    protected function meta(): array
    {
        return [];
    }

    final public function toArray($request): array
    {
        $data = [
            'type' => $this->type(),
            'id' => $this->id(),
            'attributes' => $this->attributes(),
        ];

        if ($rels = $this->relationships()) {
            $data['relationships'] = $rels;
        }

        if ($links = $this->links()) {
            $data['links'] = $links;
        }

        if ($meta = $this->meta()) {
            $data['meta'] = $meta;
        }

        return $data;
    }

    /** Para incluir en respuesta top-level cuando hay include */
    public function with($request): array
    {
        return [];
    }
}
```

---

## 2. `TopBarResource`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Identidad;

use App\Http\Resources\JsonApiResource;

class TopBarResource extends JsonApiResource
{
    protected function type(): string { return 'top-bar'; }

    protected function attributes(): array
    {
        return [
            'logo_path' => $this->resource->logo_path,
            'url_govco' => $this->resource->url_govco,
            'altura_px' => (int) $this->resource->altura_px,
            'idioma_default' => $this->resource->idioma_default,
        ];
    }

    protected function relationships(): array
    {
        return [
            'items' => [
                'data' => $this->whenLoaded('items', fn() =>
                    $this->resource->items->map(fn($i) =>
                        ['type' => 'top-bar-item', 'id' => (string) $i->id]
                    )->toArray()
                ),
            ],
        ];
    }
}
```

---

## 3. `DocumentoResource`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Transparencia;

use App\Http\Resources\JsonApiResource;

class DocumentoResource extends JsonApiResource
{
    protected function type(): string { return 'documento'; }

    protected function attributes(): array
    {
        return [
            'slug' => $this->resource->slug,
            'titulo' => $this->resource->titulo,
            'descripcion' => $this->resource->descripcion,
            'fecha_publicacion' => $this->resource->fecha_publicacion?->toDateString(),
            'fecha_documento' => $this->resource->fecha_documento->toDateString(),
            'periodicidad' => $this->resource->periodicidad,
            'estado' => $this->resource->estado,
            'destacado' => (bool) $this->resource->destacado,
            'indice_lecturabilidad' => $this->resource->indice_lecturabilidad !== null
                ? (float) $this->resource->indice_lecturabilidad
                : null,
            'hash_sha256' => $this->when(!$this->resource->relationLoaded('archivo')
                || $this->resource->archivo !== null,
                fn() => $this->resource->archivo?->hash_sha256
            ),
            'tamano_bytes' => $this->when($this->resource->relationLoaded('archivo'),
                fn() => $this->resource->archivo?->tamano_bytes
            ),
            'formato_abierto' => $this->whenLoaded('tipoDocumento',
                fn() => (bool) $this->resource->tipoDocumento->formato_abierto
            ),
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }

    protected function relationships(): array
    {
        return [
            'subseccion' => [
                'data' => ['type' => 'subseccion-transparencia', 'id' => (string) $this->resource->subseccion_id],
            ],
            'categoria' => [
                'data' => $this->resource->categoria_id
                    ? ['type' => 'categoria-documento', 'id' => (string) $this->resource->categoria_id]
                    : null,
            ],
            'tipoDocumento' => [
                'data' => ['type' => 'tipo-documento', 'id' => (string) $this->resource->tipo_documento_id],
            ],
            'archivo' => [
                'links' => [
                    'self' => route('api.archivo.show', $this->resource->archivo_id),
                    'related' => route('api.archivo.download', $this->resource->archivo_id),
                ],
                'data' => ['type' => 'archivo-storage', 'id' => (string) $this->resource->archivo_id],
            ],
            'autor' => [
                'data' => ['type' => 'usuario', 'id' => (string) $this->resource->autor_id],
            ],
            'metadatos' => [
                'data' => $this->whenLoaded('metadatos', fn() =>
                    $this->resource->metadatos->map(fn($m) =>
                        ['type' => 'metadato-documento', 'id' => (string) $m->id]
                    )->toArray()
                ),
            ],
            'versiones' => [
                'data' => $this->whenLoaded('versiones', fn() =>
                    $this->resource->versiones->map(fn($v) =>
                        ['type' => 'documento-version', 'id' => (string) $v->id]
                    )->toArray()
                ),
            ],
            'dependencias' => [
                'data' => $this->whenLoaded('dependencias', fn() =>
                    $this->resource->dependencias->map(fn($d) =>
                        ['type' => 'dependencia', 'id' => (string) $d->id]
                    )->toArray()
                ),
            ],
        ];
    }

    protected function links(): array
    {
        return [
            'self' => route('api.transparencia.documentos.show', $this->resource->slug),
            'canonical' => "https://www.santamarta.gov.co/transparencia/{$this->resource->slug}",
        ];
    }
}
```

---

## 4. `ServidorPublicoResource` (vista pública)

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Directorio;

use App\Http\Resources\JsonApiResource;

class ServidorPublicoResource extends JsonApiResource
{
    protected function type(): string { return 'servidor-publico'; }

    protected function attributes(): array
    {
        return [
            'codigo_sigep' => $this->resource->codigo_sigep,
            'nombres' => $this->resource->nombres,
            'apellidos' => $this->resource->apellidos,
            'cargo' => $this->resource->cargo,
            'correo_institucional' => $this->resource->correo_institucional,
            'extension' => $this->resource->extension,
            // IMPORTANTE: numero_identificacion NO se expone (RN-07)
        ];
    }

    protected function relationships(): array
    {
        return [
            'dependencia' => [
                'data' => ['type' => 'dependencia', 'id' => (string) $this->resource->dependencia_id],
            ],
        ];
    }
}
```

---

## 5. `MenuItemResource` (jerárquico)

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Identidad;

use App\Http\Resources\JsonApiResource;

class MenuItemResource extends JsonApiResource
{
    protected function type(): string { return 'menu-item'; }

    protected function attributes(): array
    {
        return [
            'slug' => $this->resource->slug,
            'etiqueta' => $this->resource->etiqueta,
            'ruta' => $this->resource->ruta,
            'descripcion' => $this->resource->descripcion,
            'orden' => (int) $this->resource->orden,
            'visible' => (bool) $this->resource->visible,
            'tipo' => $this->resource->tipo,
        ];
    }

    protected function relationships(): array
    {
        return [
            'padre' => [
                'data' => $this->resource->padre_id
                    ? ['type' => 'menu-item', 'id' => (string) $this->resource->padre_id]
                    : null,
            ],
            'hijos' => [
                'data' => $this->whenLoaded('hijos', fn() =>
                    $this->resource->hijos->map(fn($h) =>
                        ['type' => 'menu-item', 'id' => (string) $h->id]
                    )->toArray()
                ),
            ],
        ];
    }
}
```

---

## 6. Colecciones JSON:API

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class JsonApiCollection extends ResourceCollection
{
    /** Tipo del recurso contenido */
    protected string $resourceType = 'item';

    public $collects = JsonApiResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map->toArray($request)->all(),
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
            'meta' => [
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
            ],
        ];
    }
}
```

Uso:
```php
public function listar(ListarDocumentosRequest $request): JsonApiCollection
{
    $documentos = Documento::query()
        ->filter($request->filters())
        ->sort($request->sort())
        ->with($this->includesFromRequest())
        ->paginate($request->pageSize());

    return new JsonApiCollection($documentos, DocumentoResource::class);
}
```

---

## 7. Controlador de muestra

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Transparencia;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListarDocumentosRequest;
use App\Http\Resources\JsonApiCollection;
use App\Http\Resources\Transparencia\DocumentoResource;
use App\Models\Transparencia\Documento;
use Illuminate\Http\JsonResponse;

class DocumentoController extends Controller
{
    public function listar(ListarDocumentosRequest $request): JsonResponse
    {
        $query = Documento::query()
            ->publicado()
            ->noEliminado();

        // Filtros
        if ($subseccion = $request->filter('subseccion')) {
            $query->deSubseccion($subseccion);
        }
        if ($categoria = $request->filter('categoria')) {
            $query->deCategoria($categoria);
        }
        if ($vigencia = $request->filter('vigencia')) {
            $query->deVigencia((int) $vigencia);
        }
        if ($request->has('filter.formato_abierto')) {
            $query->conFormatoAbierto();
        }

        // Sort (whitelist)
        $sort = $request->sort('fecha_publicacion');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $query->orderBy($column, $direction);

        // Includes (whitelist)
        if ($includes = $request->includes(['metadatos', 'categoria', 'archivo', 'versiones', 'dependencias'])) {
            $query->with($includes);
        }

        // Sparse fieldsets
        if ($fields = $request->sparseFields('documento')) {
            $query->select($fields);
        }

        $documentos = $query->paginate($request->pageSize());

        return (new JsonApiCollection($documentos, DocumentoResource::class))
            ->response()
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function mostrar(string $slug, ListarDocumentosRequest $request): JsonResponse
    {
        $documento = Documento::where('slug', $slug)
            ->publicado()
            ->noEliminado()
            ->with($this->includesFromRequest($request))
            ->firstOr(fn() => throw new RecursoNoEncontradoException('documento'));

        return (new DocumentoResource($documento))
            ->response()
            ->header('ETag', '"' . md5($documento->updated_at) . '"');
    }
}
```

---

## 8. Manejo automático de includes, fields, sort

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

abstract class JsonApiRequest extends FormRequest
{
    protected array $allowedIncludes = [];
    protected array $allowedSorts = ['created_at', 'updated_at'];
    protected array $allowedFields = ['*'];

    public function includes(array $additional = []): array
    {
        $requested = explode(',', $this->query('include', ''));
        $allowed = array_merge($this->allowedIncludes, $additional);
        return array_intersect($requested, $allowed);
    }

    public function sort(string $default): string
    {
        $requested = $this->query('sort', $default);
        // Validación contra whitelist
        $columns = explode(',', $requested);
        $valid = [];
        foreach ($columns as $col) {
            $col = trim($col);
            $dir = '';
            if (str_starts_with($col, '-')) {
                $dir = '-';
                $col = substr($col, 1);
            }
            if (in_array($col, $this->allowedSorts, true)) {
                $valid[] = $dir . $col;
            }
        }
        return implode(',', $valid) ?: $default;
    }

    public function filter(string $key): mixed
    {
        return $this->query("filter.{$key}");
    }

    public function sparseFields(string $resourceType): ?array
    {
        $fields = $this->query("fields.{$resourceType}");
        if (!$fields) {
            return null;
        }
        $arr = explode(',', $fields);
        return array_merge(['id'], array_intersect($arr, $this->allowedFields));
    }

    public function pageSize(): int
    {
        $size = (int) $this->query('page.size', 20);
        return min(max($size, 1), 100);
    }

    public function pageNumber(): int
    {
        return max((int) $this->query('page.number', 1), 1);
    }
}
```

---

## 9. Errores JSON:API consistentes

```php
<?php

declare(strict_types=1);

namespace App\Exceptions\JsonApi;

use App\Exceptions\JsonApi\ErrorResponse as ErrorResponseBuilder;

trait ResponderErroresJsonApi
{
    protected function renderJsonApiError(\Throwable $e, int $status, string $code, string $title): JsonResponse
    {
        return (new ErrorResponseBuilder())
            ->addError($status, $code, $title, $e->getMessage(), request())
            ->build();
    }
}
```

Uso en un Controller:
```php
public function mostrar(string $slug): JsonResponse
{
    $documento = Documento::publicado()->where('slug', $slug)->first();
    if (!$documento) {
        return $this->renderJsonApiError(
            new \Exception("Documento '{$slug}' no encontrado"),
            404, 'NOT_FOUND', 'Recurso no encontrado'
        );
    }
    // ...
}
```

---

## 10. Buenas prácticas aplicadas

- **Sparse fieldsets** (`fields[type]=...`) reducen payload.
- **Includes** validados contra whitelist (evita N+1 accidentales).
- **Always include `type` y `id`** en cada recurso.
- **ResourceIdentifier** (solo `type`+`id`) en relaciones para reducir payload; full objects solo en `included`.
- **Cache-Control + ETag** en GET para mejorar rendimiento.
- **Versionado por URL** (`/v1/`) sin romper contrato.
