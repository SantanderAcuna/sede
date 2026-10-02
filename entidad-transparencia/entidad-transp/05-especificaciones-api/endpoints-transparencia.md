# Endpoints — Módulo 02 (Transparencia)

> **Total endpoints módulo 02:** 14 públicos (sitio) + 11 autenticados (panel).

---

## API Pública (sitio) — 14 endpoints

### `GET /api/v1/transparencia/subsecciones`
- **Implementa:** RF-02-001
- **Cache:** TTL 24 horas (rara vez cambian).
- **Descripción:** retorna las 10 subsecciones en orden.

**Respuesta:**
```json
{
  "data": [
    { "type": "subseccion-transparencia", "id": "1", "attributes": { "codigo": "informacion-entidad", "nombre": "Información de la entidad", "orden": 1, "numero_ley": 1 } },
    { "type": "subseccion-transparencia", "id": "2", "attributes": { "codigo": "normativa", "nombre": "Normativa", "orden": 2, "numero_ley": 2 } }
    /* ... hasta 10 */
  ]
}
```

### `GET /api/v1/transparencia/documentos`
- **Implementa:** RF-02-002, RF-02-025, RF-02-027, RF-02-028, RF-02-030
- **Cache:** TTL 5 min, invalidado en evento `DocumentoPublicado`.
- **Descripción:** lista paginada de documentos.

**Parámetros JSON:API:**
| Parámetro | Tipo | Descripción |
|---|---|---|
| `filter[subseccion]` | string | Código de subsección |
| `filter[categoria]` | string | Código de categoría |
| `filter[vigencia]` | int | Año de publicación |
| `filter[formato_abierto]` | bool | Solo formatos abiertos |
| `filter[destacado]` | bool | Solo destacados |
| `sort` | string | Default `-fecha_publicacion` |
| `include` | string | Relaciones: `metadatos,categoria,archivo,versiones,dependencias` |
| `fields[documento]` | string | Sparse fieldsets |
| `page[number]`, `page[size]` | int | Paginación (max 100) |

**Respuesta:**
```json
{
  "data": [
    {
      "type": "documento",
      "id": "123",
      "attributes": {
        "slug": "plan-accion-2026",
        "titulo": "Plan de Acción 2026",
        "descripcion": "Plan anual institucional de la Alcaldía...",
        "fecha_publicacion": "2026-01-15",
        "fecha_documento": "2026-01-10",
        "periodicidad": "anual",
        "estado": "publicado",
        "destacado": true,
        "indice_lecturabilidad": 64.5,
        "hash_sha256": "a3f5b8c9d2e1f4a7b6c5d8e9f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0",
        "tamano_bytes": 2457600,
        "formato_abierto": true,
        "created_at": "2026-01-15T10:00:00Z",
        "updated_at": "2026-01-15T10:00:00Z"
      },
      "relationships": {
        "subseccion": { "data": { "type": "subseccion-transparencia", "id": "4" } },
        "categoria": { "data": { "type": "categoria-documento", "id": "12" } },
        "tipoDocumento": { "data": { "type": "tipo-documento", "id": "1" } },
        "archivo": { "data": { "type": "archivo-storage", "id": "789" } },
        "autor": { "data": { "type": "usuario", "id": "5" } },
        "metadatos": { "data": [
          { "type": "metadato-documento", "id": "1" },
          { "type": "metadato-documento", "id": "2" }
        ]},
        "dependencias": { "data": [
          { "type": "dependencia", "id": "3" }
        ]},
        "versiones": { "data": [
          { "type": "documento-version", "id": "456" }
        ]}
      }
    }
  ],
  "links": {
    "first": "/api/v1/transparencia/documentos?page[number]=1&page[size]=20",
    "last": "/api/v1/transparencia/documentos?page[number]=42&page[size]=20",
    "prev": null,
    "next": "/api/v1/transparencia/documentos?page[number]=2&page[size]=20"
  },
  "meta": {
    "total": 832,
    "per_page": 20,
    "current_page": 1,
    "last_page": 42
  }
}
```

### `GET /api/v1/transparencia/documentos/{slug}`
- **Implementa:** RF-02-004 (URL canónica), RF-02-024 (hash visible)
- **Descripción:** detalle de un documento con todas sus relaciones (`?include=metadatos,categoria,archivo,versiones`).

### `GET /api/v1/transparencia/buscar`
- **Implementa:** RF-02-027
- **Parámetros:**
  - `q` (requerido, ≥3 car.)
  - `filter[subseccion]` (opcional)
  - `page[number]`, `page[size]`

**Respuesta:**
```json
{
  "data": [
    { "type": "documento", "id": "1", "attributes": { /* ... */ } }
  ],
  "meta": {
    "total": 12,
    "termino": "presupuesto participativo",
    "termino_corregido": null,
    "tiempo_ms": 18
  }
}
```

### `GET /api/v1/transparencia/directorio`
- **Implementa:** RF-02-006
- **Descripción:** directorio público de servidores (sin datos sensibles).
- **Cache:** TTL 1 hora, invalidado tras sync SIGEP.

### `GET /api/v1/transparencia/calendario-tributario?vigencia={año}`
- **Implementa:** RF-02-016

### `GET /api/v1/transparencia/subsecciones/{codigo}`
- **Implementa:** RF-02-005, RF-02-007, RF-02-015, RF-02-017, RF-02-018, RF-02-019, RF-02-020
- **Descripción:** detalle de una subsección con metadatos y enlace a sus documentos.

---

## Panel Admin — 11 endpoints

### `GET /api/v1/panel/transparencia/documentos`
- **Implementa:** RF-02-021
- **Permisos:** `editor`, `aprobador`, `administrador`
- **Filtros adicionales:** `filter[estado]` (incluye borradores), `filter[autor_id]`.

### `POST /api/v1/panel/transparencia/documentos`
- **Implementa:** RF-02-021, RF-02-024, RF-02-026, RF-02-030
- **Permisos:** `editor`
- **Request:** `multipart/form-data` con archivo + metadatos.
- **Efectos:**
  1. Calcula SHA-256 del archivo.
  2. Sube a S3.
  3. Crea registro en `archivo_storage`.
  4. Crea registro en `documento` (estado='borrador').
  5. Crea registro en `documento_version` (numero_version=1).
  6. Inserta metadatos Dublin Core.
  7. Registra en `log_auditoria`.

### `PATCH /api/v1/panel/transparencia/documentos/{id}`
- **Implementa:** RF-02-021
- **Idempotency-Key:** recomendado para reemplazos.
- **Efectos al reemplazar archivo:**
  1. Marca `documento_version` anterior como no publicada.
  2. Crea nueva versión con `numero_version = anterior + 1`.
  3. Actualiza `documento.version_actual_id` y `documento.archivo_id`.
  4. URL canónica se mantiene.

### `DELETE /api/v1/panel/transparencia/documentos/{id}`
- **Implementa:** RF-02-021
- **Soft-delete:** `deleted_at = now()`; no se elimina físicamente.

### `GET /api/v1/panel/alertas`
- **Implementa:** RF-02-022

### `POST /api/v1/panel/alertas/{id}/resolver`
- **Implementa:** RF-02-022 (resolver manualmente)

### `GET /api/v1/panel/ita/tablero`
- **Implementa:** RF-02-029

### `POST /api/v1/panel/ita/recalcular`
- **Implementa:** RF-02-029 (forzar recálculo)

### `GET /api/v1/panel/auditoria`
- **Implementa:** auditoría (todos los cambios)
- **Permisos:** `administrador`, `seguridad`

### `GET /api/v1/panel/transparencia/documentos/{id}/versiones`
- **Implementa:** RF-02-021 (historial)

### `POST /api/v1/panel/transparencia/documentos/{id}/restaurar/{versionId}`
- **Implementa:** RF-02-021 (restaurar versión anterior como actual)

---

## Form Requests (validación)

Todos los endpoints de escritura usan Form Requests con `rules()` y `authorize()`:

```php
// Ejemplo: CrearDocumentoRequest
class CrearDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear', Documento::class);
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'max:102400', 'mimes:pdf,xlsx,csv,json,rdf,odt,ods,odp,html,xml'],
            'titulo' => ['required', 'string', 'max:300'],
            'descripcion' => ['nullable', 'string'],
            'subseccion_id' => ['required', 'integer', 'exists:subseccion_transparencia,id'],
            'categoria_id' => ['nullable', 'integer', 'exists:categoria_documento,id'],
            'tipo_documento_id' => ['required', 'integer', 'exists:tipo_documento,id'],
            'fecha_publicacion' => ['nullable', 'date'],
            'periodicidad' => ['required', Rule::in(['anual','semestral','trimestral','mensual','eventual'])],
            'destacado' => ['boolean'],
            'metadatos' => ['array'],
            'metadatos.*.clave' => ['required', 'string', 'max:100'],
            'metadatos.*.valor' => ['required', 'string'],
            'dependencias' => ['array'],
            'dependencias.*' => ['integer', 'exists:dependencia,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.max' => 'El archivo no puede superar 100 MB.',
            'archivo.mimes' => 'Formato no soportado. Use PDF, XLSX, CSV, JSON, RDF, ODF, HTML o XML.',
        ];
    }
}
```

---

## Filtros aceptados (resumen)

| Recurso | Filtros |
|---|---|
| `documento` | `subseccion`, `categoria`, `vigencia`, `formato_abierto`, `destacado`, `periodicidad`, `fecha_desde`, `fecha_hasta` |
| `noticia` | `categoria`, `destacada`, `fecha_desde`, `fecha_hasta` |
| `servidor_publico` | `dependencia`, `cargo` |
| `busqueda` | `subseccion` |

---

## Ordenamiento aceptado (resumen)

| Recurso | Sort |
|---|---|
| `documento` | `fecha_publicacion`, `titulo`, `created_at` |
| `noticia` | `fecha_publicacion`, `titulo` |
| `servidor_publico` | `apellidos`, `nombres`, `cargo` |

Default: `-fecha_publicacion` (más reciente primero) para documentos/noticias, `apellidos` para servidores.
