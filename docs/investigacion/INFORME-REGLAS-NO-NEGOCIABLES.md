# INFORME — REGLAS NO NEGOCIABLES DE `GUIA-MAESTRA-COMPLETA.md`

**Fuente:** `/home/sacunapolo/Documentos/sede/GUIA-MAESTRA-COMPLETA.md` (3312 líneas, leído íntegro en tramos: 1-400, 401-800, 801-1200, 1201-1600, 1601-2000, 2001-2400, 2401-2800, 2801-3200, 3201-3312).
**Criterio:** transcripción literal donde el documento fija regla. Cuando algo no aparece: **"no consta en el documento"**.

**Estructura real verificada (líneas exactas):**
- CAPÍTULO 0 — Fundamentos y Compromisos (l.23) · §0.1 Reglas Absolutas del Stack (l.25) · §0.2 Metodología: Vertical Slice Development (l.91) · §0.3 Flujo de Cierre SDD (l.119) · §0.4 Estrategia de Ramas y Pull Requests (l.134)
- CAPÍTULO 1 — Contract-First (l.187): PASO 0 (l.208), PASO 1 (l.216), PASO 1.1 (l.227), PASO 2 (l.404), PASO 3 (l.416), Resumen (l.429), Reglas del Hook (l.441)
- CAPÍTULO 2 — Arquitectura y Configuración Inicial (l.450): §2.1 (l.452), §2.2 (l.484), §2.3 (l.501)
- CAPÍTULO 3 — Ciclo de Desarrollo Backend (l.511): §3.1–§3.17 (l.516–l.1810), Checkpoints Backend (l.1826), Reglas del Hook Aplicables (l.1856)
- CAPÍTULO 4 — Ciclo de Desarrollo Frontend (l.1874): §4.1–§4.13 (l.1876–l.2551), Checkpoints Frontend (l.2562)
- CAPÍTULO 5 — Auditoría Final: Matriz de 25 puntos (l.2605) + Auditoría de Contrato C1–C7 (l.2637)
- CAPÍTULO 6 — Infraestructura (l.2653)
- CAPÍTULO 7 — Frontend Móvil: Flutter offline-first (l.2687)
- CAPÍTULO 8 — DevOps y Contenedores (l.2795)
- CAPÍTULO 9 — Integración de IA (l.3065)
- APÉNDICE A — Índice Maestro de Documentación Oficial (l.3191)

**PRINCIPIO RECTOR (l.4):** "Contrato primero → Implementación por Vertical Slice → Verificación continua en cada paso"
**Rigor (l.13):** "Nivel Doctorado. Un sistema que no supere cada checkpoint NO avanza a la siguiente fase."
**Fuente de verdad HTTP (l.15-19):** "el archivo OpenAPI (`openapi.yaml`) es la única fuente de verdad del shape de la API. El backend (clases `JsonResource` de Laravel 13 con `withoutWrapping()`) lo emite y el frontend (interfaces TS + services) lo consume con la **misma** convención. El shape es **Flat Envelope** (`success`, `message`, `data`, `meta`, `errors`), media type **`application/json`**."

---

## 1. Reglas Absolutas del Stack (§0.1, líneas 25-88)

**Fundamento (l.29):** "Cada tecnología, librería, paquete y framework se usa **estrictamente según su documentación oficial**. Nada de tutoriales de terceros, blogs, workarounds no documentados, o prácticas no validadas por la fuente oficial."

### Transcripción textual de las 14 reglas

1. **Documentación oficial siempre.** Toda decisión técnica debe poder rastrearse a la documentación oficial de la tecnología correspondiente. (l.31)
2. **Backend y frontend desacoplados.** El backend es API REST profesional. El frontend es un cliente independiente. Comunicación exclusiva por HTTP API + WebSockets. Sin Inertia, sin Blade. (l.33)
3. **Proyectos separados dentro de un mismo directorio raíz.** Backend (Laravel) y frontend (Vue) son carpetas independientes dentro del proyecto. (l.35)
   ```
   /var/www/mi-proyecto/
   ├── backend/          ← Laravel (API REST)
   └── frontend/         ← Vue 3 + TypeScript (cliente SPA)
   ```
4. **Clean Architecture con Contracts.** Separación estricta de capas con interfaces. (l.43)
5. **Desarrollo Full-Stack con integración continua.** Cada funcionalidad del backend se integra inmediatamente con el frontend y se valida con pruebas completas antes de pasar a la siguiente. (l.45)
6. **Super-Admin del sistema.** Todo proyecto debe tener un rol `super-admin` con acceso total mediante `Gate::before()` según la documentación oficial de **Spatie Permission ^8**: (l.47)
   - El callback **retorna `null`** —nunca `false`— cuando el usuario NO es super-admin. (l.48)
   - Desde Laravel 11 el `Gate::before` se registra en `AppServiceProvider::boot()`. (l.49)
   ```php
   public function boot(): void
   {
       Gate::before(function ($user, string $ability) {
           return $user->hasRole('super-admin') ? true : null;
       });
   }
   ```
7. **Seeders: usar `updateOrCreate` con password directo.** El password se escribe en texto plano y el cast `hashed` de Laravel lo hashea automáticamente. (l.60)
   ```php
   User::updateOrCreate(
       ['email' => 'usuario@example.com'],
       ['name' => 'Usuario', 'password' => 'password_real']
   );
   ```
8. **Frontend: Accept y Content-Type deben ser `application/json`.** El backend usa `JsonResource` con `withoutWrapping()` (Laravel 13 nativo). (l.69)
9. **Verificar login desde el navegador después de cambios en auth.** Las pruebas con curl no son suficientes. (l.71)
10. **Después de cada sub-agente, verificar:** servidores activos, login funciona desde navegador, tests pasan, build pasa. (l.73)
11. **Usar SDD para todo el desarrollo.** (l.75)
    ```
    sdd-new → sdd-explore → sdd-propose → sdd-spec → sdd-design → sdd-tasks
        → [Contract-First: OpenAPI spec + Prism mock]
        → sdd-apply → sdd-verify
    ```
12. **Usar Engram para memoria persistente.** (l.83)
13. **Usar Graphify para diagramas de arquitectura.** (l.85)
14. **Actualizar memoria después de cada regla o aprendizaje.** (l.87)

### Tabla: regla, implicación práctica, qué la violaría

| # | Regla | Implicación práctica | Qué la violaría |
|---|-------|----------------------|-----------------|
| 1 | Documentación oficial siempre | Toda decisión técnica rastreable a doc oficial; nada de blogs/tutoriales/workarounds | Copiar un patrón de un blog, usar API no documentada, "workaround" sin fuente oficial |
| 2 | Backend y frontend desacoplados | API REST + WebSockets como único canal | Instalar Inertia, renderizar Blade, cualquier acoplamiento server-side |
| 3 | Proyectos separados en un mismo directorio raíz | `backend/` y `frontend/` hermanos | Monolito con frontend dentro de `resources/`, o repos separados en rutas distintas |
| 4 | Clean Architecture con Contracts | Interfaces para Repository y Service; inyección por contrato | Repository/Service concreto sin interfaz; lógica directamente en el Controller |
| 5 | Full-stack con integración continua | Cada funcionalidad BE se integra y valida en FE antes de la siguiente | Acumular features backend y "luego" hacer frontend |
| 6 | Rol `super-admin` con `Gate::before()` | `Gate::before` en `AppServiceProvider::boot()`, callback devuelve `null` (no `false`) si no es super-admin | Devolver `false` en el callback; registrar en `AuthServiceProvider` como en Laravel ≤10; omitir el rol |
| 7 | Seeders con `updateOrCreate` y password plano | Cast `hashed` hashea solo; seeder idempotente | Usar `Hash::make()` en el seeder, `create()` en vez de `updateOrCreate`, password ya hasheado |
| 8 | Accept y Content-Type `application/json` | Headers JSON en FE; `JsonResource` + `withoutWrapping()` en BE | Enviar `application/vnd.api+json` (prohibido explícitamente, l.194), dejar el wrapping `data` por defecto |
| 9 | Verificar login desde el navegador tras cambios en auth | Prueba manual/navegador obligatoria | Validar solo con curl y declarar auth "funcionando" |
| 10 | Tras cada sub-agente verificar 4 cosas | Servidores activos + login navegador + tests + build | Aceptar salida de sub-agente sin verificar; saltarse el build |
| 11 | SDD para todo el desarrollo | Cadena `sdd-new → … → sdd-apply → sdd-verify`, con Contract-First entre `sdd-tasks` y `sdd-apply` | Empezar a codificar sin spec/tasks; saltarse el mock de Prism |
| 12 | Engram para memoria persistente | Persistir decisiones y aprendizajes en Engram | Memoria solo en el chat |
| 13 | Graphify para diagramas de arquitectura | Diagramas generados con Graphify | Diagramas manuales/inconsistentes |
| 14 | Actualizar memoria tras cada regla o aprendizaje | Alta inmediata en memoria, no al final | Acumular aprendizajes sin registrarlos |

---

## 2. Vertical Slice Development (§0.2, líneas 91-115)

**Encabezado de rigor (l.93):** "⚠️ **CUMPLIMIENTO ESTRICTO — SIN EXCEPCIONES.**"

**Definición (l.95):** "Cada funcionalidad se desarrolla completa — desde base de datos hasta UI — antes de pasar a la siguiente."

### Principio fundamental (NO NEGOCIABLE) — l.97-101

> **No se avanza a una nueva funcionalidad sin antes haber integrado y validado la actual en el frontend.**
>
> **Cada slice vertical: base de datos → API → lógica → frontend → pruebas → validación visual.**

### Flujo por funcionalidad (l.103-113)

| Paso | Actividad |
|------|-----------|
| 1 | Backend: API, lógica, base de datos |
| 2 | Integración con frontend |
| 3 | Pruebas unitarias (backend y frontend) |
| 4 | Pruebas de integración |
| 5 | Feature tests |
| 6 | Suite completa (unitarias + integración + feature + E2E) |
| 7 | Validación visual y funcional en el frontend |

**Criterio de cierre (l.115):** "Solo cuando **todas las pruebas pasan y la integración es visible y funcional en el frontend**, se considera la tarea completa."

**Comandos exactos:** el documento **no da comandos** para este flujo (no consta comando de `pest`, `phpunit`, `npm test`, `vitest`, etc.). Los únicos comandos del ciclo son los de SDD en §0.1 (l.78-80, ver arriba), los de Contract-First (§1: Prism y `openapi-typescript`) y los de instalación/artisan.

---

## 3. Flujo de Cierre SDD (§0.3, líneas 119-130)

#### Por cada PR archivado (l.121-126):
1. Verificar que TODO esté guardado en Engram
2. Llamar `mem_session_summary` con resumen completo del PR
3. Ejecutar `/sinapsis-learning`
4. Ejecutar `/promote`

#### Solo al cerrar el cambio completo (último PR aprobado) (l.128-130):
5. Ejecutar `/clear`

**Nota de fidelidad:** el documento NO describe el procedimiento de "archivar un cambio" ni lista artefactos de especificación que se sincronicen (p. ej. `specs/`, `design.md`, `tasks.md`) en §0.3. Lo único que se "sincroniza" explícitamente es la **memoria persistente (Engram)**: "Verificar que TODO esté guardado en Engram". Los artefactos SDD mencionados en todo el documento son las fases (`sdd-new`, `sdd-explore`, `sdd-propose`, `sdd-spec`, `sdd-design`, `sdd-tasks`, `sdd-apply`, `sdd-verify`) — §0.1 regla 11, l.78-80 — pero **no consta** en el documento un paso de archivado que sincronice specs/design/tasks ni rutas de archivos de esos artefactos.

---

## 4. Estrategia de ramas y Pull Requests (§0.4, líneas 134-183)

### 4.1 Las 3 ramas permanentes (l.136-142) — nombres EXACTOS

| Rama | Propósito | Protegida |
|------|-----------|-----------|
| `main` | Producción | Sí — requiere PR + review |
| `develop` | Integración | Sí — requiere PR |
| `staging` | Pruebas/QA | Sí — requiere PR |

**RESPUESTA A LA PREGUNTA CRÍTICA:** el documento dice **`main`**, nunca `master`. La palabra "master" **no aparece ni una sola vez** en las 3312 líneas (verificado con grep: 0 coincidencias). La rama principal es `main` (l.140) y `main` es también el destino final del flujo (l.164: "Merge staging → main (producción)").

### 4.2 Flujo por módulo/feature (l.144-154)

```
main (producción)
 ↑
staging (pruebas/QA)
 ↑
develop (integración)
 ↑
feat/{modulo} (desarrollo)
```

### 4.3 Flujo completo (l.156-165)

```
1. feat/{modulo}  → desde develop
2. Merge feat → develop (tests, resolver conflictos)
3. Merge develop + staging → staging (fusionar)
4. Deploy a servidor de pruebas (staging)
5. Tests pasan en staging
6. Merge staging → main (producción)
```

### 4.4 Tipos de ramas (l.167-177) — prefijos exactos

| Prefijo | Uso |
|---------|-----|
| `feat/` | Nueva funcionalidad |
| `fix/` | Corrección de bug |
| `refactor/` | Reestructuración sin cambio funcional |
| `docs/` | Solo documentación |
| `test/` | Solo tests |
| `chore/` | Mantenimiento, dependencias, config |
| `hotfix/` | Fix urgente en producción |

**Convención de nombre (formato exacto):** `{prefijo}/{modulo}` — el único ejemplo literal que da el documento es **`feat/{modulo}`** (l.153 y l.159). No fija formato con número de issue, ni kebab-case obligatorio, ni longitud máxima. Los demás prefijos se listan sin ejemplo de nombre completo.

### 4.5 Pull Requests (l.179-183)

- **l.181:** "**PR como unidad de review, no de código.** El PR debe poder revisarse en ≤ 30 minutos."
- **l.183:** "**Review Workload Guard:** > 400 líneas modificadas → el PR **debe** dividirse en 2 o más PRs encadenados."

**Tamaño máximo:** **> 400 líneas modificadas** obliga a dividir. El objetivo operativo es **≤ 30 minutos de revisión**.

**Requisitos / revisión / qué debe incluir un PR:** el documento **no detalla** plantilla de PR, checklist, ni contenido obligatorio del cuerpo del PR (descripción, issue link, screenshots). Lo único fijado es: (a) el tamaño ≤400 líneas / ≤30 min; (b) `main` y `develop`/`staging` requieren PR; (c) `main` requiere **PR + review** (l.140); (d) los "PRs encadenados" cuando se supera 400 líneas. **No consta** en el documento: número mínimo de revisores, obligatoriedad de aprobación de CI antes del merge, conventional commits, ni firmas.

---

## 5. Contract-First: Flat Envelope (CAPÍTULO 1, líneas 187-447)

### 5.1 Posición y prohibición de media type (l.189-194)

> **"Contrato primero, código después"**
> **Posición en el flujo SDD:** este capítulo se ejecuta DESPUÉS de `sdd-tasks` y ANTES de `sdd-apply`.
> **Convención de respuesta:** Flat Envelope — `{success, message, data, meta, errors}`
> **Media type:** `application/json` (NO SE DEBE USAR ES PROHIBIDO `application/vnd.api+json`)

### 5.2 Fuente de Verdad Única (l.198-204)

- "El OpenAPI (`openapi.yaml`) es la **única fuente de verdad** del contrato HTTP. Backend y frontend NO se acoplan entre sí: ambos se acoplan al MISMO OpenAPI."
- "**Backend** DEBE emitir exactamente lo que el OpenAPI declara."
- "**Frontend** DEBE tipar y validar exactamente lo que el OpenAPI declara."
- "Cada operación lleva `x-status` (`pending`/`implemented`) para indicar contra qué servidor apuntar."

### 5.3 PASO 0 — Verificación Inicial (l.208-212)

**¿Existe ya una especificación OpenAPI para esta API?**
- **SÍ** → continuar en el **Paso 2**
- **NO** → proceder al **Paso 1**

### 5.4 PASO 1 — Diseño del Contrato (l.216-223)

- "Redactar especificación OpenAPI (**3.1.x**) en formato YAML, en el archivo `openapi.yaml`."
- El contrato **debe contener obligatoriamente**: `openapi`, `info`, `servers`, `paths`, `components.schemas`, `components.securitySchemes`, **ejemplos**
- "Se valida con **`openapi-spec-validator`** y de estilo con **Redocly CLI**." (l.223)

### 5.5 Formato canónico del Flat Envelope (l.229-295)

**Recurso individual (l.234-246):**
```json
{
  "success": true,
  "message": null,
  "data": {
    "id": 1,
    "type": "users",
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "created_at": "2026-01-15T05:00:18.000000Z"
  }
}
```

**Colección paginada (l.248-272):**
```json
{
  "success": true,
  "message": null,
  "data": [
    { "id": 1, "type": "users", "name": "Ada Lovelace", "email": "ada@example.com" },
    { "id": 2, "type": "users", "name": "Alan Turing", "email": "alan@example.com" }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  },
  "links": {
    "first": "/api/v1/users?page=1",
    "last": "/api/v1/users?page=5",
    "prev": null,
    "next": "/api/v1/users?page=2"
  }
}
```

**Error (404/401/403) (l.274-282):**
```json
{
  "success": false,
  "message": "El recurso no fue encontrado.",
  "data": null,
  "errors": null
}
```

**Error de validación (422) (l.284-295):**
```json
{
  "success": false,
  "message": "Error de validación.",
  "data": null,
  "errors": {
    "name": ["El campo name es obligatorio."],
    "email": ["El correo electrónico debe ser válido."]
  }
}
```

### 5.6 Componentes compartidos canónicos (l.297-348) — nombres EXACTOS de schemas

`ApiEnvelope`, `PaginatedEnvelope`, `PageMeta`, `CollectionLinks`, `ApiError`.

```yaml
components:
  schemas:
    ApiEnvelope:
      type: object
      required: [success, data]
      properties:
        success: { type: boolean }
        message: { type: [string, 'null'] }
        data: { type: [object, 'null'] }
        errors: { type: [object, 'null'] }

    PaginatedEnvelope:
      allOf:
        - $ref: '#/components/schemas/ApiEnvelope'
        - type: object
          required: [meta, links]
          properties:
            data: { type: array }
            meta: { $ref: '#/components/schemas/PageMeta' }
            links: { $ref: '#/components/schemas/CollectionLinks' }

    PageMeta:
      type: object
      required: [current_page, from, last_page, path, per_page, to, total]
      properties:
        current_page: { type: integer, minimum: 1 }
        from: { type: [integer, 'null'], minimum: 1 }
        last_page: { type: integer, minimum: 1 }
        path: { type: string, format: uri }
        per_page: { type: integer, minimum: 1 }
        to: { type: [integer, 'null'], minimum: 1 }
        total: { type: integer, minimum: 0 }

    CollectionLinks:
      type: object
      required: [first, last, prev, next]
      properties:
        first: { type: [string, 'null'], format: uri }
        last: { type: [string, 'null'], format: uri }
        prev: { type: [string, 'null'], format: uri }
        next: { type: [string, 'null'], format: uri }

    ApiError:
      type: object
      required: [message]
      properties:
        message: { type: string }
        errors: { type: [object, 'null'] }
```

> Nota literal (l.352): "Por cada recurso se declaran **tres** schemas (no cuatro)".

### 5.7 Patrón por recurso: Item / Collection / Input (l.350-388) — ejemplo literal

```yaml
# 1) Item — recurso individual (va en data del envelope)
UserItem:
  type: object
  required: [id, type]
  properties:
    id: { type: integer }
    type: { type: string, const: users }
    name: { type: string }
    email: { type: string }
    role: { type: string }
    active: { type: boolean }
    created_at: { type: string, format: date-time, readOnly: true }
    updated_at: { type: string, format: date-time, readOnly: true }

# 2) Collection — array paginado (va en data del PaginatedEnvelope)
UserCollection:
  type: object
  required: [data, meta, links]
  properties:
    data: { type: array, items: { $ref: '#/components/schemas/UserItem' } }
    meta: { $ref: '#/components/schemas/PageMeta' }
    links: { $ref: '#/components/schemas/CollectionLinks' }

# 3) Input — request body (para POST/PATCH)
UserInput:
  type: object
  required: [name, email]
  properties:
    name: { type: string, maxLength: 120 }
    email: { type: string, format: email, maxLength: 200 }
    password: { type: string, format: password, minLength: 8 }
    role: { type: string, maxLength: 60 }
    active: { type: boolean }
```

### 5.8 Reglas de autoría (l.390-400) — tabla literal

| Regla | Convención |
|-------|-----------|
| Media type | `application/json` en toda respuesta y request |
| `id` | integer (no string) |
| `type` | plural en kebab-case, declarado con `const` |
| `readOnly` | campos autogenerados → excluidos del request body |
| Update | verbo `PATCH` (no `PUT`) |
| Paginación | parámetros `page` y `per_page`; `links` y `meta` en top-level |
| Errores | objeto `errors` con clave por campo: `{"campo": ["mensaje"]}` |

### 5.9 PASO 2 — Mock con Prism (l.404-412) — comando literal

```bash
docker run --rm -p 4010:4010 \
  -v $(pwd)/openapi.yaml:/tmp/openapi.yaml \
  stoplight/prism:4 mock -h 0.0.0.0 /tmp/openapi.yaml
```

**l.412:** "**No se continúa al Paso 3 hasta que el mock responda correctamente.**"

### 5.10 PASO 3 — Desarrollo en Paralelo (l.416-425)

**"Solo después del mock activo:"**
- "**Backend**: implementa lógica real."
- "**Frontend**: Tipos TypeScript se generan desde el contrato:"
  ```bash
  npx openapi-typescript openapi.yaml -o src/types/api.d.ts --read-write-markers
  ```

### 5.11 Resumen del Flujo Contract-First (l.429-437)

| # | Paso | Condición para avanzar |
|---|------|------------------------|
| 0 | Verificar si existe contrato | — |
| 1 | Diseñar contrato OpenAPI | Aprobación del Product Owner |
| 2 | Levantar mock con Prism | Mock responde correctamente |
| 3 | Desarrollo paralelo | Backend + frontend implementados |
| 4 | Validar implementación contra contrato | 100% de pruebas de contrato pasan |

### 5.12 Reglas del Hook (Flat Envelope) (§1, l.441-446) — tabla literal

| Regla | Descripción | Estado |
|-------|-------------|--------|
| R-22 | Usar `JsonResource` con `withoutWrapping()` (Laravel 13) | ✅ Actualizada |
| R-24 | OpenAPI debe usar `application/json` | ✅ Vigente |

**R-25 y R-26: no constan en el documento.** Verificado con grep `R-2[356]` → 0 coincidencias.

### 5.13 Todas las reglas `R-xx` del documento (inventario completo verificado)

Reglas numeradas existentes: **R-06, R-07, R-09, R-10, R-17, R-19, R-22, R-24, R-37, R-38, R-39, R-40, R-41, R-44, R-45, R-46, R-47, R-51**. No existe R-01..R-05, R-08, R-11..R-16, R-18, R-20, R-21, R-23, R-25..R-36, R-42, R-43, R-48..R-50 en el texto.

| Regla | Descripción literal | Línea |
|-------|---------------------|-------|
| R-06 | Token en localStorage — XSS | 2543 |
| R-07 | `v-html` con datos de usuario | 2544 |
| R-09 | `window.location` — usar `router.push()` | 2545 |
| R-10 | `new Function()` — RCE | 2546 |
| R-17 | PHP sin `declare(strict_types=1)` | 1860 |
| R-19 | `any` en TypeScript | 2547 |
| R-22 | Usar `JsonResource` con `withoutWrapping()` (Laravel 13) | 445 |
| R-24 | OpenAPI debe usar `application/json` | 446 |
| R-37 | Lógica en Controller — debe estar en Service | 1861 |
| R-38 | Repository sin Contract — crear Contract primero | 1862 |
| R-39 | `hasRole()` en Policy — usar `$user->can()` | 1863 |
| R-40 | FormRequest sin `#[FailOnUnknownFields]` | 1864 |
| R-41 | Dinero como float/double — usar `decimal()` | 1865 |
| R-44 | CORS con `*` y credentials — configuración insegura | 1866 |
| R-45 | Migración con `->change()` — usar migración explícita | 1867 |
| R-46 | FK sin `onDelete` — requerir `->onDelete('cascade')` | 1868 |
| R-47 | Relaciones sin return type — requerido en Eloquent | 1869 |
| R-51 | Service con Request en firma — usar DTO | 1870 |

> **Interpretación literal:** las reglas se enuncian como **anti-patrón detectado por el hook**, no como imperativo positivo. Ej.: R-39 "`hasRole()` en Policy — usar `$user->can()`" significa: *si el hook detecta `hasRole()` dentro de una Policy, es violación; se debe usar `$user->can()`*. R-40 significa: *un FormRequest sin el atributo `#[FailOnUnknownFields]` es violación*. R-51: *firmar un Service con `Request` es violación; usar DTO*.

### 5.14 Herramienta que valida el contrato y ruleset

- **Validador de especificación:** `openapi-spec-validator` (l.223)
- **Validador de estilo:** **Redocly CLI** (l.223)
- **Generador de tipos:** `openapi-typescript` con flag `--read-write-markers` (l.424, l.1938)
- **Mock:** `stoplight/prism:4` (l.409)
- **Versión de OpenAPI exigida:** **3.1.x**, formato **YAML**, archivo **`openapi.yaml`** (l.218)
- **Ruleset de Redocly:** el documento **no especifica ningún ruleset concreto** (p. ej. `recommended`, `minimal`) — **no consta**.
- **Media type prohibido:** `application/vnd.api+json` (l.194). Aunque el Apéndice A lista JSON:API (l.3210) como referencia de documentación, el media type JSON:API está prohibido.

---

## 6. Convenciones de código backend (PHP/Laravel)

### 6.1 Stack y versiones exigidas (§3.1, l.511-525)

> "Basado en Laravel 13.x con PHP 8.4+ y PostgreSQL 18. Documentación oficial: `laravel-13-docs/`"

| Categoría | Tecnología |
|-----------|-----------|
| Lenguaje | PHP ^8.4 |
| Framework | Laravel ^13 |
| Base de datos | PostgreSQL 18 |
| Testing | Pest 4 / PHPUnit 12 |
| Análisis estático | PHPStan nivel 8 |
| Formateo | Pint (auto-fix) |

- **PHP:** `^8.4` (l.520). **Laravel:** `^13` (l.521). **PostgreSQL:** 18 (l.522).
- **PHPStan nivel 8** (l.524) y `phpstan.neon` con `level: 8` e incluido `vendor/larastan/larastan/extension.neon` (l.2967-2975).
- PHPUnit 12 (implícito por "Pest 4 / PHPUnit 12", l.523).

### 6.2 Convenciones PHP (§3.1, l.527-537)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;
```

**l.537:** "**`declare(strict_types=1)` es OBLIGATORIO en todo archivo PHP.**"

Además, del resto del documento se derivan estas convenciones aplicadas sistemáticamente en todos los snippets:
- Clases declaradas **`final class`** (p. ej. l.593 `final class User extends Model`, l.686 `final class UserResource`, l.895 `final class StoreUserRequest`, l.1023, l.1099, l.1154, l.1183, l.1242, l.1300, l.1500).
- **Inyección por constructor promovido con `private readonly`** (l.1101-1103, l.1302-1304).
- **Tipos de retorno explícitos** en todos los métodos (`: void`, `: bool`, `: array`, `: JsonResponse`, `: HasMany`…).
- Métodos de migración tipados `public function up(): void` / `down(): void` (l.556, l.573).
- Bindings de interfaces en `AppServiceProvider::register()` (l.1156-1160).

### 6.3 Estructura obligatoria de carpetas (Clean Architecture)

**§2.3 (l.501-507) — diagrama literal:**
```
Controller → FormRequest → Service → Repository → Model
                ↑
    Contracts (Interface de Repository y Service)
```

**IMPORTANTE / precisión de fidelidad:** el §2.3 **no enumera un árbol de directorios**. La "lista exacta de directorios y su responsabilidad" **no consta como tal** en el documento. Los namespaces/directorios sí quedan determinados de forma literal por los `namespace` de los snippets canónicos:

| Directorio / namespace | Responsabilidad | Evidencia (línea) |
|---|---|---|
| `app/Http/Controllers/Api/V1` | Controllers; delgados, sin lógica compleja; delegan en Service | l.1291 (`namespace App\Http\Controllers\Api\V1`), l.1849 |
| `app/Http/Requests` | FormRequest: validación + autorización | l.889, l.933 |
| `app/Http/Resources` | `JsonResource` que emite el Flat Envelope | l.681, l.714 |
| `app/Contracts/Repository` | Interfaces de repositorio | l.992 |
| `app/Contracts/Service` | Interfaces de servicio | l.1070 |
| `app/Repositories` | Implementación concreta de acceso a datos | l.1016 |
| `app/Services` | Reglas de negocio | l.1091 |
| `app/Models` | Modelos Eloquent | l.587, l.1417 |
| `app/Policies` | Autorización por recurso | l.1177 |
| `app/Jobs` | Jobs en cola (`ShouldQueue`) | l.1492 |
| `app/Events` | Eventos broadcast (`ShouldBroadcast`) | l.1705 |
| `app/Providers` | `AppServiceProvider` (bindings + `Gate::before`) | l.1146, l.1236 |
| `app/Support/Api` | Helper `ApiResponse` | l.813 |
| `routes/api.php` | Rutas de la API bajo `auth:sanctum` / `throttle:api` | l.1259, l.2571 |
| `routes/channels.php` | Autorización de canales privados (Reverb) | l.1689 |
| `config/` | `auth.php`, `horizon.php`, `octane.php`, `cors`, `bootstrap/app.php` | l.477, l.1563, l.1661, l.1758, l.1786 |
| `database/migrations`, seeders | Migraciones anónimas `return new class extends Migration` y seeders `updateOrCreate` | l.554, l.653 |

### 6.4 Migraciones y Modelos (§3.2, l.541-658)

**Migración (l.545-578):** `return new class extends Migration` con `up(): void` / `down(): void`; columnas con longitud explícita (`string('name', 120)`, `string('email', 200)->unique()`, `string('phone', 20)->nullable()`, `string('role', 60)->default('user')`, `boolean('active')->default(true)`); `timestamps()` y `softDeletes()`.

**Modelo (l.582-619) — forma canónica literal:**
```php
final class User extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'role', 'active', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'active' => 'boolean',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```
→ `$fillable` explícito, `$hidden` para `password`/`remember_token`, cast `'password' => 'hashed'`, **relaciones con return type tipado** (`HasMany`, `BelongsTo`, `BelongsToMany`, `HasOne` — l.614-648), consistente con R-47.

**Seeder con password plano (l.653-658):**
```php
User::updateOrCreate(
    ['email' => 'admin@example.com'],
    ['name' => 'Admin', 'password' => 'password_real', 'role' => 'admin']
);
```

**Reglas derivadas (hook backend, l.1856-1870):**
- R-41: dinero nunca `float`/`double` → usar `decimal()`.
- R-45: prohibido `->change()` en migraciones → usar migración explícita.
- R-46: FK sin `onDelete` → requerir `->onDelete('cascade')`.
- R-47: relaciones sin return type → requerido en Eloquent.
- R-17: PHP sin `declare(strict_types=1)` → violación.

### 6.5 Resource (§3.3, l.662-874)

**Generación:** `php artisan make:resource UserResource` (l.671)

**Forma canónica "declarativa (forma recomendada)" (l.674-701):**
```php
final class UserResource extends JsonResource
{
    /** Los atributos se exponen directamente en el envelope — Laravel los extrae automáticamente */
    public $attributes = [
        'name', 'email', 'phone', 'role', 'active', 'created_at', 'updated_at',
    ];

    public static $wrap = null; // disables wrapping — flat envelope output
}
```
**l.703-705:** "En `AppServiceProvider::boot()` o en el constructor del Resource: `JsonResource::withoutWrapping();` Esto hace que TODOS los recursos de la aplicación emitAN flat envelope."

**Resource con atributos condicionales (l.707-741)** — `toArray(Request $request): array` construyendo `['id','type','name','email','role','created_at']` y añadiendo `phone` solo si `$request->user()?->can('view-phone', $this->resource)`. `type` va hardcodeado como `'users'` y `created_at` como `$this->created_at?->toIso8601String()`.

**Los 4 shapes (l.743-804):** individual, colección paginada, error no-validación, error de validación (422) — idénticos a §5.5 (ver arriba); la variante de §3.3 incluye `path` en `meta` (l.772).

### 6.6 Helpers obligatorios `ApiResponse` (§3.3, l.806-874)

Namespace `App\Support\Api`, `final class ApiResponse`. Métodos y forma:

| Método | Firma | Cuerpo |
|---|---|---|
| `ok` | `public static function ok(mixed $data = null, ?string $message = null): JsonResponse` | `['success' => true, 'message' => $message, 'data' => $data, 'errors' => null]` |
| `created` | `public static function created(mixed $data = null, ?string $message = null): JsonResponse` | igual, con `'message' => $message ?? 'Recurso creado.'` y **status 201** |
| `error` | `public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse` | `['success' => false, 'message' => $message, 'data' => null, 'errors' => $errors]`, status `$status` |
| `notFound` | `public static function notFound(string $message = 'Recurso no encontrado.'): JsonResponse` | `self::error($message, 404)` |
| `unauthorized` | `public static function unauthorized(string $message = 'No autenticado.'): JsonResponse` | `self::error($message, 401)` |
| `forbidden` | `public static function forbidden(string $message = 'No autorizado.'): JsonResponse` | `self::error($message, 403)` |
| `validationError` | `public static function validationError(array $errors): JsonResponse` | `self::error('Error de validación.', 422, $errors)` |
| `tooManyRequests` | `public static function tooManyRequests(string $message = 'Demasiadas solicitudes.'): JsonResponse` | `self::error($message, 429)` |

**Uso obligatorio en el manejador de excepciones (l.1788-1805):** `ApiResponse::unauthorized()` para `AuthenticationException`, `forbidden()` para `AuthorizationException`, `notFound()` para `ModelNotFoundException`, `tooManyRequests()` para `TooManyRequestsHttpException` — registrado con `->withExceptions(function (Exceptions $exceptions) { $exceptions->render(...) })` en `bootstrap/app.php`.

### 6.7 FormRequest (§3.4, l.878-979)

- **StoreRequest:** `authorize()` delega en Policy: `return $this->user()?->can('create', \App\Models\User::class) ?? false;` (l.899). `rules()` con arrays de reglas string. **`failedValidation(Validator $validator): void`** lanza `HttpResponseException` con el envelope 422 (`success:false`, `message:'Error de validación.'`, `data:null`, `errors:$validator->errors()->toArray()`, status 422) — l.912-922.
- **UpdateRequest:** `authorize()` obtiene el recurso de ruta (`$this->route('user')`) y usa `can('update', $user)`; reglas **`sometimes`** y `unique:users,email,` + `$this->route('user')` (l.945-956) — coherente con checkpoint FASE 2 #6 "UpdateRequest con `sometimes`".
- **Validación de arrays (l.962-967):** `'items' => ['required','array','min:1']`, `'items.*.quantity' => ['required','integer','min:1']`, `'items.*.product_id' => ['required','integer','exists:products,id']`.
- **Mensajes personalizados (l.971-978):** método `messages(): array` con claves `'email.required'`, `'email.unique'`.

### 6.8 Service y Repository (§3.5, l.983-1162)

**Contrato de Repository (l.997-1006):** `interface UserRepositoryInterface` con `findAll(): Collection`, `findAllPaginated(int $perPage = 15)`, `findById(int $id): ?Model`, `findByEmail(string $email): ?Model`, `create(array $data): Model`, `update(Model $model, array $data): Model`, `delete(Model $model): bool`.

**Repository concreto (l.1023-1060):** `final class UserRepository implements UserRepositoryInterface`; paginación con `User::query()->paginate($perPage)->withQueryString()`; `update()` hace `$model->update($data); return $model->fresh();`; `delete()` devuelve `$model->delete()`.

**Contrato de Service (l.1074-1081):** `list(int $perPage = 15): LengthAwarePaginator`, `show(int $id): User`, `store(array $data): User`, `update(int $id, array $data): User`, `destroy(int $id): bool`.

**Service concreto (l.1099-1136):** constructor promovido `private readonly UserRepositoryInterface $userRepository`; **`?? throw new ModelNotFoundException()`** en `show`/`update`/`destroy` — que el handler global convierte en 404 vía `ApiResponse::notFound()`.

**Registro en ServiceProvider (l.1154-1161):**
```php
public function register(): void
{
    $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    $this->app->bind(UserServiceInterface::class, UserService::class);
}
```
**Reglas del hook relacionadas:** R-37 (lógica en Controller — debe estar en Service), R-38 (Repository sin Contract — crear Contract primero), R-51 (Service con Request en firma — usar DTO).

### 6.9 Policy y Autorización (§3.6, l.1166-1251)

**Forma canónica (l.1183-1226):** `final class PostPolicy` con métodos que devuelven **`Response`**: `viewAny(User $user): Response`, `create(User $user): Response`, `update(User $user, Post $post): Response`, `delete(User $user, Post $post): Response`. Patrón literal:
```php
return $user->hasPermissionTo('view posts')
    ? Response::allow()
    : Response::deny();
```
y para owner/admin:
```php
$isOwner = $user->id === $post->user_id;
$isAdmin = $user->hasRole('admin');

if ($isOwner || $isAdmin) {
    return Response::allow();
}
```
**Checkpoint FASE 3 #11:** "Policy con métodos completos | `viewAny`, `create`, `update`, `delete`".
**Regla hook R-39:** "`hasRole()` en Policy — usar `$user->can()`" — es decir, en Policy no se invoca `hasRole()` como mecanismo de autorización, se usa `$user->can()`. (Nótese la tensión interna del documento: el snippet canónico de Policy sí usa `hasRole('admin')` como atajo de owner/admin, mientras R-39 lo marca como violación; **el documento no resuelve esa tensión explícitamente**.)

**`Gate::before` Super-Admin (l.1242-1250):**
```php
final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
```

### 6.10 Rutas y Controladores (§3.7, l.1255-1340)

**Rutas canónicas (l.1261-1282):**
```php
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('admin')
        ->middleware('permission:access admin')
        ->group(function () {
            Route::apiResource('users', UserController::class)->except(['update']);
            Route::patch('users/{user}', [UserController::class, 'update']);
        });
});
```
→ `apiResource` **excluye `update`** y el update se expone como **`PATCH`**, coherente con la regla de autoría "Update | verbo `PATCH` (no `PUT`)". Prefijo de versión `/api/v1` (l.2571: "Coinciden con `routes/api.php` bajo `/api/v1`").

**Controlador canónico (l.1300-1339):** `final class UserController extends Controller` en `App\Http\Controllers\Api\V1`, constructor con `private readonly UserServiceInterface $userService`. Métodos `index` (usa `UserResource::collection(...)->response()` y `per_page` con `request()->integer('per_page', 15)`), `store` (`UserResource::make($user)->response()->setStatusCode(201)`), `show`, `update` (`PATCH`), `destroy` (`return response()->json(null, 204);`).

### 6.11 Autenticación (§2.1, l.452-482)

| Consumidores | Solución |
|---|---|
| Solo frontend web (Vue SPA) | Sanctum |
| Solo apps móviles (Flutter) o terceros | Passport |
| Ambos (web + móvil + terceros) | Sanctum + Passport |

- Sanctum: `php artisan install:api` (l.463)
- Passport: `php artisan install:api --passport` (l.469)
- "El modelo `User` debe usar el trait `HasApiTokens` **y** implementar la interfaz `Laravel\Passport\Contracts\OAuthenticatable`." (l.472)
- Coexistencia (l.477-482): guards `'web' => ['driver' => 'session', ...]`, `'api' => ['driver' => 'passport', ...]`.
- Sanctum SPA (l.1352), API token Bearer (l.1362-1373 con `scope:read-tasks` / `scope:create-tasks`), CSRF con `$middleware->statefulApi();` en `bootstrap/app.php` (l.1380-1382).
- Tests con `Sanctum::actingAs(User::factory()->create(), ['view-tasks']);` y todos los permisos `['*']` (l.1393, l.1401).
- Passport scopes con `Passport::tokensCan([...])` y `Passport::setDefaultScope(['read-posts']);` (l.1444-1451).

### 6.12 Rate Limiting (§3.10, l.1457-1477)

```php
RateLimiter::for('login', function (Request $request) {
    return [
        Limit::perMinute(5)->by($request->input('email') . '|' . $request->ip()),
        Limit::perMinute(20)->by($request->ip()),
    ];
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### 6.13 Colas, Horizon, Octane, Reverb, CORS, Errores (§3.11-§3.16)

- **Jobs (§3.11, l.1481-1550):** `final class ProcessUserExport implements ShouldQueue` con `use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;`, `public int $tries = 3; public int $backoff = 60;`, constructor promovido `public readonly`, `handle(): void`, `failed(\Throwable $exception): void`. Delay: `ProcessUserExport::dispatch($userId, 'pdf')->delay(now()->addMinutes(5))->onQueue('exports');`. Variante: `public array $backoff = [10, 30, 60, 300];`.
- **Horizon (§3.12, l.1554-1615):** `composer require laravel/horizon` + `php artisan horizon:install`; `config/horizon.php` con `'supervisor-1'`, `balance => 'auto'`, `autoScalingStrategy => 'time'`, `maxProcesses => 10` (local 3, producción 50); estrategias `auto`/`simple`/`none`; deploy: `php artisan horizon:terminate` y `php artisan horizon`.
- **Octane (§3.13, l.1619-1672):** `composer require laravel/octane`, `php artisan octane:install --server=frankenphp`; dev `php artisan octane:frankenphp --host=0.0.0.0 --port=8000 --watch`; prod `... --max-requests=500`; reload `php artisan octane:reload`; concurrencia con `Octane::concurrently([...])`; cache store `octane`.
- **Reverb (§3.14, l.1676-1745):** `php artisan install:broadcasting`; canal privado con `Broadcast::channel('chat-room.{room}', ...)`; evento `final class MessageSent implements ShouldBroadcast` con `use InteractsWithSockets, SerializesModels;`, `broadcastOn(): array` devolviendo `new PrivateChannel(...)` y `broadcastAs(): string` → `'message.sent'`; servicio compose con `command: ["php","artisan","reverb:start","--host=0.0.0.0","--port=8080"]`.
- **CORS y headers (§3.15, l.1749-1778):** `paths => ['api/*','sanctum/csrf-cookie']`, `allowed_origins => [env('FRONTEND_URL','http://localhost:5173')]`, `supports_credentials => true`, exposed headers `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`. Headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Content-Security-Policy: default-src 'self'`. **Regla R-44:** "CORS con `*` y credentials — configuración insegura".
- **Errores (§3.16, l.1782-1806):** ver §6.6.

### 6.14 Códigos HTTP (§3.17, l.1810-1823) — tabla completa literal

| Código | Uso |
|--------|-----|
| 200 | GET, PATCH exitoso con cuerpo |
| 201 | POST creado |
| 204 | DELETE sin cuerpo |
| 401 | No autenticado |
| 403 | No autorizado |
| 404 | No encontrado |
| 409 | Conflicto |
| 422 | Error de validación |
| 429 | Rate limit |

### 6.15 Checkpoints de Verificación Backend (l.1826-1853)

#### FASE 1: Migración y Modelo (l.1828-1833)
| # | Verificación | Criterio |
|---|-------------|----------|
| 1 | FK en migración tiene relación en modelo | No puede haber FK sin relación |
| 2 | Soft deletes si el contrato lo declara | `SoftDeletes` trait y `$table->softDeletes()` |

#### FASE 2: Resource y FormRequest (l.1835-1843)
| # | Verificación | Criterio |
|---|-------------|----------|
| 3 | Resource declara todos los campos | Coincide con schema OpenAPI |
| 4 | Relaciones como `{ data: { type, id } }` | Nunca FK plana |
| 5 | StoreRequest cubre todos los campos | Excluye autogenerados |
| 6 | UpdateRequest con `sometimes` | Coherencia de tipos |
| 7 | `authorize()` usa Policy | Permiso `create` |

#### FASE 3: Service y Policy (l.1845-1852)
| # | Verificación | Criterio |
|---|-------------|----------|
| 8 | Service implementa reglas de negocio | Controller sin lógica compleja |
| 9 | Campos autoincrementables en Service | No recibidos del payload |
| 10 | Campos generados no en `$fillable` | Método dedicado en Service |
| 11 | Policy con métodos completos | `viewAny`, `create`, `update`, `delete` |

### 6.16 Reglas del Hook Aplicables (backend) (l.1856-1870)

| Regla | Descripción |
|-------|-------------|
| R-17 | PHP sin `declare(strict_types=1)` |
| R-37 | Lógica en Controller — debe estar en Service |
| R-38 | Repository sin Contract — crear Contract primero |
| R-39 | `hasRole()` en Policy — usar `$user->can()` |
| R-40 | FormRequest sin `#[FailOnUnknownFields]` |
| R-41 | Dinero como float/double — usar `decimal()` |
| R-44 | CORS con `*` y credentials — configuración insegura |
| R-45 | Migración con `->change()` — usar migración explícita |
| R-46 | FK sin `onDelete` — requerir `->onDelete('cascade')` |
| R-47 | Relaciones sin return type — requerido en Eloquent |
| R-51 | Service con Request en firma — usar DTO |

---

## 7. Convenciones de código frontend (CAPÍTULO 4, líneas 1874-2602)

### 7.1 Stack exacto y versiones (§4.1, l.1876-1889)

| Categoría | Tecnología | Notas |
|-----------|------------|-------|
| UI | Vue 3 `<script setup lang="ts">` + Composition API | **NO Options API** |
| Tipos | TypeScript strict | `noImplicitAny`, cero `any` |
| Estado | Pinia | Store tipado |
| HTTP | Axios | Instancia única |
| Routing | Vue Router 4 | Routing declarativo |
| Server state | TanStack Vue Query | Cache y refetch |
| Tablas | TanStack Table v8 | Composable |
| Forms | TanStack Form + Zod | Validación tipada |
| Estilos | TailwindCSS v4 | CSS-first con `@theme {}` |
| Notificaciones | `vue3-toastify` | Singleton global |

**Flujo de Datos (l.1891-1895):** `VIEW → COMPOSABLE → SERVICE → HTTP CLIENT → INTERFACE (OpenAPI)`

**Instalación (§2.2, l.493-499) — comando literal:**
```bash
npm create vite@latest frontend -- --template vue-ts
npm install pinia vue-router@4 @tanstack/vue-query @tanstack/vue-table @tanstack/vue-form zod axios tailwindcss @tailwindcss/vite vue3-toastify @fortawesome/fontawesome-free
npm install -D openapi-typescript
```

**Versiones:** el documento **solo fija explícitamente**: `vue-router@4` (l.497), **TanStack Table v8** (l.1886), **TailwindCSS v4** (l.1888), **Vue 3.5+** para destructuring reactivo (l.2063-2073) y **Vue 3.4+** para `defineModel` (l.2018). No fija versión de Vue, TypeScript, Axios, Pinia ni Zod — **no consta**.

### 7.2 Estructura de carpetas del frontend

El documento **no publica un árbol de carpetas completo** para el frontend. Los directorios quedan fijados por las rutas literales usadas en los snippets:

| Ruta | Contenido | Evidencia |
|---|---|---|
| `src/api/client.ts` | Instancia Axios única + interceptores | l.1902 |
| `src/types/api.d.ts` | Tipos generados desde OpenAPI | l.1938 |
| `src/types/` (via `@/types/api`) | Reexportación de `components['schemas']` y utilidades | l.1944 |
| `src/composables/useUser.ts` | Composable de datos | l.2285 |
| `src/composables/useNotify.ts` | Composable de notificaciones | l.2319 |
| `src/stores/auth.ts` | Store Pinia | l.2345 |
| `src/router/index.ts` | Router + guards | l.2386 |
| `src/schemas/user.ts` | Schemas Zod | l.2444 |
| `src/views/public/auth/LoginView.vue` | Vista login (pública) | l.2396 |
| `src/views/admin/AdminLayout.vue` | Layout admin | l.2401 |
| `src/views/admin/users/UsersView.vue` | Vista de recurso admin | l.2407 |
| `src/views/ForbiddenView.vue` | Vista 403 | l.2415 |
| Alias `@/` | Alias de import hacia `src/` | l.2396, l.2287 |
| `frontend/` dentro de la raíz del proyecto | Carpeta hermana de `backend/` | l.38-41 |

**Convención de nombres de vistas observada:** `PascalCase` + sufijo `View.vue` para vistas, `XxxLayout.vue` para layouts, y directorios por área (`public/`, `admin/<recurso>/`). El documento **no enuncia esta convención como regla explícita**, solo la usa. **No consta** carpeta `src/services/` como directorio separado (el "SERVICE" del flujo de datos no tiene ruta ejemplificada).

### 7.3 Cliente Axios del Flat Envelope (§4.2, l.1899-1931) — forma canónica literal

```ts
// src/api/client.ts
import axios from 'axios'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
  timeout: 15_000,
})

// Interceptor de respuestas
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore().clear()
      router.push({ name: 'forbidden' })
    }
    if (error.response?.status === 429) {
      useNotify().warning('Demasiados intentos. Intenta más tarde.')
    }
    return Promise.reject(error)
  }
)

export default apiClient
```
Reglas fijadas aquí: **instancia única** (l.1883), `Content-Type` y `Accept` `application/json` (regla absoluta 8, l.69), `withCredentials: true` (cookies httpOnly de Sanctum), timeout `15_000` ms, y manejo global de **401 → `clear()` + `router.push({ name: 'forbidden' })`** y **429 → warning**.

### 7.4 Generación de tipos desde OpenAPI (§4.3, l.1935-1969)

**Herramienta:** `openapi-typescript`. **Comando literal:**
```bash
npx openapi-typescript openapi.yaml -o src/types/api.d.ts --read-write-markers
```

**Interfaces canónicas:**
```ts
import type { components } from '@/types/api'

type S = components['schemas']

export type UserItem = S['UserItem']
export type UserCollection = S['UserCollection']
export type UserInput = S['UserInput']

export type PageMeta = S['PageMeta']
export type CollectionLinks = S['CollectionLinks']
export type ApiEnvelope = S['ApiEnvelope']
export type PaginatedEnvelope = S['PaginatedEnvelope']

// Tipos utilitarios para el frontend
export interface ApiResponse<T> {
  success: boolean
  message: string | null
  data: T
  errors: Record<string, string[]> | null
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: PageMeta
  links: CollectionLinks
}
```
→ Los tipos **no se escriben a mano**: se derivan del contrato. Checkpoint FASE 4 #14 exige `openapi-typescript --read-write-markers`.

### 7.5 Componentes Vue 3 `<script setup>` — patrones oficiales (§4.4, l.1973-2113)

**(a) SFC Spec (l.1977-1998):**
- "**1 `<template>`** — obligatorio"
- "**1 `<script setup>`** (o `<script>`) — obligatorio"
- "**Múltiples `<style>`** (normal + `scoped` + `module`)"

**(b) Compiler Macros (Vue 3.5+) (l.2000-2037):** `withDefaults(defineProps<{...}>(), { count: 0 })`, `defineEmits<{ (e: 'update', id: string, data: Record<string, unknown>): void; (e: 'delete', id: string): void }>()`, `defineModel<string>()` (Vue 3.4+), `defineExpose({ focus, value: model.value })`, `defineOptions({ name: 'UserCard', inheritAttrs: false })`, `defineSlots<{ default(): any; icon(): any }>()`.

**(c) Props tipadas con validación — Style Guide Priority A (l.2039-2061):** ✅ correcto con `type`, `required`, `validator`, `default`; ❌ incorrecto `const props = defineProps(['title', 'status'])`.

**(d) Destructuring reactivo de props (Vue 3.5+) (l.2063-2073):** `const { title, status = 'pending' } = defineProps<{...}>()`.

**(e) Scoped CSS (l.2075-2113):** `:deep(.child-element)`, `:slotted(div)`, `:global(.red)`, CSS Modules con `<style module="classes">`, y `v-bind('theme.color')` en CSS.

**(f) Reactivity (§4.5, l.2117-2251):** `ref`/`reactive`/`readonly`/`shallowRef`/`shallowReactive`/`shallowReadonly`; `computed` de solo lectura y escribible (`get`/`set`); `watch` (explícito, perezoso) vs `watchEffect` (inmediato, con `onCleanup`); `triggerRef`, `customRef` (ejemplo `useDebouncedRef`); `toRaw`, `markRaw`; lifecycle `onBeforeMount`/`onMounted`/`onBeforeUpdate`/`onUpdated`/`onBeforeUnmount`/`onUnmounted` + `onActivated`/`onDeactivated`/`onRenderTracked`/`onRenderTriggered`; nota literal: "setup() corre antes que beforeCreate — no usar created aquí" (l.2240).

**(g) Provide/Inject (§4.6, l.2255-2276):** `provide('theme', theme)` / `inject('user', defaultUser)`; y "Provide/inject con símbolos (evitar colisiones)" con `InjectionKey<Ref<string>> = Symbol('theme')`.

**(h) Composables (§4.7, l.2280-2338):** forma canónica `useUser()` con `ref<PaginatedResponse<UserItem> | null>`, `isLoading`, `error`, función async `fetchUsers(page = 1)` con `try/catch/finally`, `computed` `userCount` y retorno de objeto con estado + acción. `useNotify()` con `success/error/info/warning` y `toast` de `vue3-toastify` en `position: 'top-right'`.

### 7.6 Estado (Pinia) (§4.8, l.2342-2379)

**Forma canónica:** setup-store, no options store.
```ts
export const useAuthStore = defineStore('auth', () => {
  const user = ref<UserResource | null>(null)
  const token = ref<string | null>(null)
  const permissions = ref<string[]>([])

  const isAuthenticated = computed(() => !!token.value)

  function hasPermission(permission: string): boolean {
    return permissions.value.includes(permission)
  }
  function login(credentials: { email: string; password: string }) { /* Login logic */ }
  function logout() { user.value = null; token.value = null; permissions.value = [] }
  function clear() { logout() }

  return { user, token, permissions, isAuthenticated, hasPermission, login, logout, clear }
})
```
Regla de la tabla §4.1: "Pinia | Store tipado" (l.1882). `clear()` es la API que invoca el interceptor de 401. Máxima del documento sobre estado: `localStorage` ❌ NO (l.2536, R-06).

### 7.7 Router y guards (§4.9, l.2383-2437)

- `createRouter({ history: createWebHistory(), routes: [...] })` — **history mode**, no hash.
- Rutas con `name` jerárquico (`auth.login`, `admin.users`, `forbidden`, `admin.dashboard`), `component: () => import(...)` **lazy**, y `meta`: `{ guest: true }`, `{ requiresAuth: true, permission: 'panel-administrative' }`, `{ permission: 'view users' }`.
- **Guard global `router.beforeEach` (l.2420-2434):**
```ts
router.beforeEach((to, from) => {
  const authStore = useAuthStore()

  if (to.meta.guest && authStore.isAuthenticated) {
    return { name: 'admin.dashboard' }
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'forbidden' }
  }

  if (to.meta.permission && !authStore.hasPermission(to.meta.permission as string)) {
    return { name: 'forbidden' }
  }
})
```
**Regla clave:** un no autenticado **NO se redirige al login**, va a `forbidden` (checkpoints FASE 7 #22 y #23, l.2593 y l.2633). Un autenticado que entra a una ruta `guest` sí se redirige a `admin.dashboard` (l.2424, checkpoint #21).

### 7.8 Formularios y validación (§4.10, l.2441-2457)

```ts
// src/schemas/user.ts
import { z } from 'zod'

export const UserCreateSchema = z.object({
  name: z.string().min(1).max(120),
  email: z.string().email().max(200),
  role: z.string().min(1).max(60),
})

export const UserUpdateSchema = UserCreateSchema.partial()

export type UserCreateInput = z.infer<typeof UserCreateSchema>
export type UserUpdateInput = z.infer<typeof UserUpdateSchema>
```
→ Zod **espeja las constraints del FormRequest/contrato** (checkpoint FASE 8 #25: "Zod vs FormRequest | Proyección estricta de constraints"). Stack de forms: **TanStack Form + Zod** (l.1887).

### 7.9 Style Guide Priority A (§4.11, l.2461-2528)

| Regla | Incorrecto | Correcto | Prioridad |
|-------|-----------|----------|-----------|
| **Multi-word names** | `<Item />`, `<TodoItem />` OK | `<Item />` ❌ → `<TodoItem />` ✅ | A |
| **Props tipadas** | `defineProps(['user'])` | `defineProps<{ user: User }>()` | A |
| **`:key` en v-for** | `v-for="item in items"` | `v-for="item in items" :key="item.id"` | A |
| **Styles scoped** | `<style>` global | `<style scoped>` | A |
| **v-if + v-for** | `<li v-for="i in items" v-if="i.active">` | Usar computed filter o `<template v-for>` | A |
| **v-bind inline** | `:style="{ color: isActive && 'red' }"` | Usar computed | B |

### 7.10 Seguridad frontend (§4.12, l.2532-2547)

| Método | ¿Seguro? | Alternativa |
|--------|----------|-------------|
| `localStorage` | ❌ NO | `httpOnly` cookie (Sanctum) |
| `httpOnly cookie` | ✅ SÍ | Solo Sanctum SPA |

**Prohibiciones OWASP:** R-06 token en localStorage (XSS), R-07 `v-html` con datos de usuario, R-09 `window.location` → usar `router.push()`, R-10 `new Function()` (RCE), R-19 `any` en TypeScript.

### 7.11 Accesibilidad WCAG AA (§4.13, l.2551-2558)

- `SkipToContent.vue` — saltar al contenido
- `BaseAccessibilityBar.vue` — contraste y tamaño
- Contraste mínimo **4.5:1**
- `tabindex` y **focus trapping** en modales
- Labels asociados a inputs
- Tamaño táctil mínimo **`min-h-[44px]`**

### 7.12 Checkpoints de Verificación Frontend (l.2562-2601)

#### FASE 4: Interfaces y Servicios (l.2564-2571)
| # | Verificación | Criterio |
|---|-------------|----------|
| 12 | Interfaces TS mapean Flat Envelope | `ApiResponse<T>`, `PaginatedResponse<T>` |
| 13 | Schemas correctos por endpoint | `<Recurso>Item` para show, `<Recurso>Collection` para index |
| 14 | Tipos generados | `openapi-typescript --read-write-markers` |
| 15 | URLs de servicios | Coinciden con `routes/api.php` bajo `/api/v1` |

#### FASE 5: Composables y Store (l.2573-2577)
| # | Verificación | Criterio |
|---|-------------|----------|
| 16 | Composables tipados | Estados reactivos con tipos de interfaces |

#### FASE 6: Vistas y Formularios (l.2579-2586)
| # | Verificación | Criterio |
|---|-------------|----------|
| 17 | Campos del form | Coinciden con schema Input del contrato |
| 18 | Campos en migración | Existen en `$fillable` del modelo |
| 19 | Campos autogenerados | `disabled` + valor visible |
| 20 | Botones según permisos | `v-if="can('action', entity)"` |

#### FASE 7: Guards (l.2588-2595)
| # | Verificación | Criterio |
|---|-------------|----------|
| 21 | Usuario autenticado no ve login | Redirige a dashboard |
| 22 | No redirige automáticamente al login | Va a `forbidden` |
| 23 | Panel admin requiere `panel-administrative` | 403 sin permiso |
| 24 | Permisos FE ↔ BE | Coinciden exactamente |

#### FASE 8: Validaciones (l.2597-2601)
| # | Verificación | Criterio |
|---|-------------|----------|
| 25 | Zod vs FormRequest | Proyección estricta de constraints |

### 7.13 SSR / SEO / Nuxt / metaetiquetas / sitemap — RESPUESTA CRÍTICA

**Verificado con grep sobre las 3312 líneas:**

| Término | ¿Aparece? | Evidencia |
|---|---|---|
| **Nuxt** | **NO** | 0 coincidencias en todo el archivo |
| **SSR** | **NO** | 0 coincidencias. El frontend es explícitamente **SPA**: l.40 "`frontend/` ← Vue 3 + TypeScript (cliente SPA)"; l.1348 "Sanctum SPA (cookie-based — Vue SPA)"; l.2537 "Solo Sanctum SPA" |
| **SEO** | **NO** | 0 coincidencias. La única coincidencia del patrón `SEO` fue la subcadena `Dio(BaseOptions` en l.2716 (falso positivo) |
| **sitemap** | **NO** | 0 coincidencias |
| **metaetiquetas / meta tags / `<meta` / og:** | **NO** | 0 coincidencias. Todas las coincidencias de "meta" son: (a) la clave `meta` del Flat Envelope de paginación (l.18, 193, 257, 315, 318, 321, 372, 375, 399, 770, 1952, 1966, 2310, 2642); (b) `import.meta.env` de Vite (l.1906, 3006-3007); (c) `meta` de rutas de Vue Router (l.2397, 2402, 2408, 2423, 2427, 2431); (d) `metadata:` de Kubernetes (l.3031, 3053) |
| **Title / description / canonical / hreflang / robots.txt** | **NO** | 0 coincidencias |

**Conclusión literal:** el documento **no menciona Nuxt, ni SSR, ni renderizado del lado del servidor, ni metaetiquetas, ni sitemap, ni ninguna práctica SEO**. El único artefacto de "documento" que menciona es el `index.html` implícito de Vite y el build (`npm run build` → `dist/`, l.2854-2864). El único requerimiento cercano a "crawlability" es CSP/headers de seguridad y WCAG AA. **Si el proyecto exige SEO, este documento no lo cubre: debe tratarse como vacío de especificación.**

---

## 8. Testing

**Niveles exigidos y herramientas:**

- **§0.2 Vertical Slice (l.103-115)** — el flujo de pruebas por funcionalidad es explícito y obligatorio:
  1. Pruebas **unitarias (backend y frontend)**
  2. Pruebas de **integración**
  3. **Feature tests**
  4. **Suite completa (unitarias + integración + feature + E2E)**
  5. **Validación visual y funcional en el frontend**
- **Herramientas backend (§3.1, l.523):** "Testing | Pest 4 / PHPUnit 12". Apéndice A: Pest (l.3305), PHPUnit (l.3306).
- **Herramientas frontend:** **no consta ninguna** — no se menciona Vitest, Jest, Playwright, Cypress ni Testing Library en ninguna línea (verificado con grep: 0 coincidencias). El nivel "E2E" se exige en l.112 pero **sin herramienta asociada**.
- **Testing de API autenticada (§3.8, l.1385-1402):** `Sanctum::actingAs(User::factory()->create(), ['view-tasks']);` + `$response->assertOk();`, y con todos los permisos `['*']`.
- **Job sincrónico "para testing" (§3.11, l.1485):** el ejemplo de Job está etiquetado como "(para testing)".
- **Pruebas de contrato (§1, l.437):** condición para avanzar del paso 3 al 4 es "**100% de pruebas de contrato pasan**".
- **Verificación post-sub-agente (§0.1 regla 10, l.73):** "servidores activos, login funciona desde navegador, tests pasan, build pasa".
- **Login verificado desde navegador (§0.1 regla 9, l.71):** "Las pruebas con curl no son suficientes."
- **Calidad estática obligatoria:** PHPStan **nivel 8** (l.524, l.2974) con Larastan; **Pint** para formateo automático (l.525).

**Qué es obligatorio antes de cerrar una funcionalidad:**
1. Todas las pruebas del flujo de 7 pasos de §0.2 pasan (l.115).
2. La integración es **visible y funcional en el frontend** (l.115) — "validación visual y funcional en el frontend" (l.113).
3. La **Auditoría Final de 25 puntos** (CAPÍTULO 5, l.2605-2635) **más** la **Auditoría de Contrato C1–C7** (l.2637-2647) están completas.
   **Criterio de Aprobación literal (l.2649):** "**25/25 + C1–C7 ✅ → El módulo puede marcarse como terminado.**"
   **Alcance (l.2607):** "**Esta auditoría se ejecuta al completar CADA módulo o Vertical Slice antes de marcarlo como terminado.**"
4. Los checkpoints de la fase correspondiente (FASE 1–8) superados: "Un sistema que no supere cada checkpoint NO avanza a la siguiente fase" (l.13).
5. Cierre SDD por PR (§0.3): Engram completo, `mem_session_summary`, `/sinapsis-learning`, `/promote`.

**Matriz de 25 puntos (l.2609-2635) — transcripción literal:**
| # | Requisito |
|---|-----------|
| 1 | Mapear campos de migración y relaciones |
| 2 | Resource retorna campos planos del contrato (`id`, `type`, campos) |
| 3 | FormRequest Store valida campos del contrato |
| 4 | FormRequest Update valida campos |
| 5 | FormRequest autoriza según Policy |
| 6 | Service implementa reglas de negocio |
| 7 | Service genera campos autoincrementados |
| 8 | Policy definida con métodos y lógica de roles |
| 9 | Frontend genera automáticamente el código |
| 10 | Input disabled, código visible |
| 11 | Frontend tipa el resource object flat envelope |
| 12 | Schemas por endpoint: `<Recurso>Item` y `<Recurso>Collection` |
| 13 | Servicios FE envían payload flat envelope con Content-Type: application/json |
| 14 | Services del FE mapean endpoints exactos |
| 15 | Composables y Storage usan interfaces completas |
| 16 | Formularios: campos del contrato = campos del form |
| 17 | Validaciones Frontend vs Backend |
| 18 | Botones según permiso del rol |
| 19 | Rutas validan acceso con guardia |
| 20 | Layout requiere `panel-administrative` |
| 21 | Sidebar filtra rutas según permisos |
| 22 | Usuario autenticado no accede a login |
| 23 | No autenticado NO redirige al login |
| 24 | Guard valida `panel-administrative` |
| 25 | Coherencia de permisos FE ↔ BE |

**Auditoría de Contrato (Flat Envelope) (l.2639-2647) — literal:**
| # | Verificación |
|---|-----------|
| C1 | ¿Flat Envelope `{success, message, data}` + Content-Type `application/json`? |
| C2 | ¿`meta` lleva las 7 claves del paginador? |
| C3 | ¿`links` de colección con 4 claves (first/last/prev/next)? |
| C4 | ¿Error con `success: false`, `message`, `errors`? |
| C5 | ¿Errores de validación como `{ errors: { campo: [mensaje] } }`? |
| C6 | ¿`per_page>100` dispara 422? |
| C7 | ¿Datos personales enmascarados salvo permiso? |

> Nota: **C6 exige que `per_page > 100` dispare 422**, aunque el documento no incluye el código que implementa ese límite (los snippets de §3.4 no lo muestran). Es un requisito de auditoría sin snippet correspondiente. **C7 exige enmascarado de datos personales salvo permiso**, implementado en el ejemplo de Resource condicional de §3.3 (l.734).

---

## 9. Prohibiciones explícitas

| Prohibición | Cita literal | Línea |
|---|---|---|
| **Inertia** | "Sin Inertia, sin Blade." (regla absoluta 2) | 33 |
| **Blade** | "Sin Inertia, sin Blade." (regla absoluta 2) | 33 |
| **`application/vnd.api+json`** | "Media type: `application/json` (NO SE DEBE USAR ES PROHIBIDO `application/vnd.api+json`)" | 194 |
| **Options API** | "UI \| Vue 3 `<script setup lang="ts">` + Composition API \| **NO Options API**" | 1880 |
| **`any` en TypeScript** | "Tipos \| TypeScript strict \| `noImplicitAny`, cero `any`" (l.1881) y "R-19 \| `any` en TypeScript" (l.2547) | 1881, 2547 |
| **Token en `localStorage`** | "`localStorage` \| ❌ NO \| `httpOnly` cookie (Sanctum)"; "R-06 \| Token en localStorage — XSS" | 2536, 2543 |
| **`v-html` con datos de usuario** | "R-07 \| `v-html` con datos de usuario" | 2544 |
| **`window.location`** | "R-09 \| `window.location` — usar `router.push()`" | 2545 |
| **`new Function()`** | "R-10 \| `new Function()` — RCE" | 2546 |
| **PHP sin `declare(strict_types=1)`** | "`declare(strict_types=1)` es OBLIGATORIO en todo archivo PHP." (l.537) y "R-17 \| PHP sin `declare(strict_types=1)`" | 537, 1860 |
| **Lógica en Controller** | "R-37 \| Lógica en Controller — debe estar en Service" | 1861 |
| **Repository sin Contract** | "R-38 \| Repository sin Contract — crear Contract primero" | 1862 |
| **`hasRole()` en Policy** | "R-39 \| `hasRole()` en Policy — usar `$user->can()`" | 1863 |
| **FormRequest sin `#[FailOnUnknownFields]`** | "R-40 \| FormRequest sin `#[FailOnUnknownFields]`" | 1864 |
| **Dinero como float/double** | "R-41 \| Dinero como float/double — usar `decimal()`" | 1865 |
| **CORS con `*` y credentials** | "R-44 \| CORS con `*` y credentials — configuración insegura" | 1866 |
| **Migración con `->change()`** | "R-45 \| Migración con `->change()` — usar migración explícita" | 1867 |
| **FK sin `onDelete`** | "R-46 \| FK sin `onDelete` — requerir `->onDelete('cascade')`" | 1868 |
| **Relaciones sin return type** | "R-47 \| Relaciones sin return type — requerido en Eloquent" | 1869 |
| **Service con `Request` en la firma** | "R-51 \| Service con Request en firma — usar DTO" | 1870 |
| **Verbo `PUT` para updates** | "Update \| verbo `PATCH` (no `PUT`)" | 398 |
| **Exponer `password`/`remember_token`** | `protected $hidden = ['password', 'remember_token'];` | 601-603 |
| **Relaciones como FK plana** | "Relaciones como `{ data: { type, id } }` \| Nunca FK plana" | 1840 |
| **`<style>` global** | "Styles scoped \| `<style>` global \| `<style scoped>` \| A" | 2470 |
| **`v-if` + `v-for` juntos** | "Evitar v-if + v-for juntos" | 2518 |
| **`v-for` sin `:key`** | "`:key` en v-for — siempre requerido" | 2502 |
| **Componentes de una sola palabra** | "`<Item />` ❌ → `<TodoItem />` ✅" | 2467 |
| **PR > 400 líneas** | "> 400 líneas modificadas → el PR **debe** dividirse en 2 o más PRs encadenados." | 183 |
| **Tutoriales/blogs/workarounds** | "Nada de tutoriales de terceros, blogs, workarounds no documentados, o prácticas no validadas por la fuente oficial." | 29 |
| **Ejecutar contenedores como root** | "No ejecutar como root \| `user: www-data` en el contenedor PHP" | 2679 |
| **Imágenes no oficiales** | "Imágenes oficiales \| Solo imágenes oficiales de Docker Hub" | 2680 |
| **Exponer puertos innecesarios** | "No exponer puertos innecesarios \| Solo 80/443" | 2683 |
| **LLM en request HTTP síncrono** | "Las llamadas a LLM **nunca** van en el request HTTP síncrono." | 3162 |
| **Secretos de LLM en git** | "Secretos de LLM (API keys) por entorno (Secret/vault), nunca en git." | 3185 |
| **PII a cloud sin enmascarar** | "Enmascarado de PII obligatorio antes de cualquier salida a cloud." | 3184 |

**Prohibiciones NO presentes (no consta):** el documento **no prohíbe** Tailwind, ni Vite, ni Axios, ni ninguna librería de la que hable. Tampoco prohíbe `master` como nombre de rama porque simplemente nunca lo menciona.

---

## 10. CMS, panel de administración y superficie editorial — SECCIÓN CRÍTICA

Búsqueda exhaustiva realizada con grep insensible a mayúsculas sobre las 3312 líneas:

| Término buscado | Resultado | Detalle |
|---|---|---|
| **Filament** | **0 coincidencias** | El documento **NO menciona Filament en ningún punto**. Ni como stack, ni como panel, ni en el Apéndice A. |
| **CMS** | **0 coincidencias** | No se menciona ningún CMS (ni WordPress, ni Strapi, ni Directus, ni October). |
| **"panel de administración"** | **0 coincidencias literales** | La frase exacta no aparece. |
| **"panel administrativo"** | **0 coincidencias** | La frase exacta no aparece. |
| **"administrativo" / "administrativa"** | **0 coincidencias** (grep `-i administrativo` → 0) | El documento **no usa la palabra en español**. |
| **`panel-administrative`** | **SÍ — 4 coincidencias** | Es un **string de permiso en inglés**, no una descripción de UI. |
| **Tailwind** | **SÍ — 3 coincidencias** | Adoptado, no prohibido. |
| **Bootstrap** | **Solo `bootstrap/app.php` de Laravel** | No es Bootstrap CSS. Ver detalle abajo. |
| **gov.co / kit gov.co** | **Solo un dominio de ejemplo en Nginx** | Ver detalle abajo. |

### 10.1 Lo que SÍ dice el documento sobre "panel de administración"

La única "superficie administrativa" que el documento especifica es **una SPA Vue con rutas protegidas por permiso**, gobernada por el string `panel-administrative`. Transcripciones literales:

- **l.2402** (router): `meta: { requiresAuth: true, permission: 'panel-administrative' },` sobre la ruta `/admin` que monta `@/views/admin/AdminLayout.vue` (l.2401).
- **Checkpoint FASE 7 #23 (l.2594):** "| 23 | Panel admin requiere `panel-administrative` | 403 sin permiso |"
- **Punto 20 de la matriz de 25 (l.2630):** "| 20 | Layout requiere `panel-administrative` | ⬜ |"
- **Punto 24 de la matriz de 25 (l.2634):** "| 24 | Guard valida `panel-administrative` | ⬜ |"
- **Rutas de administración (l.1275-1280):** `Route::prefix('admin')->middleware('permission:access admin')->group(...)` — permiso backend `access admin`; `permission: 'view users'` en la ruta hija `admin.users` (l.2408) y `hasPermissionTo('view posts'|'create posts'|'edit posts'|'delete posts')` en la Policy (l.1187-1222).
- **Punto 21 de la matriz (l.2631):** "| 21 | Sidebar filtra rutas según permisos | ⬜ |" — el documento menciona un **sidebar** como requisito de auditoría, pero **no da su código ni su componente**.
- **Punto 20 y Checkpoint #19 (l.2585):** "Campos autogenerados | `disabled` + valor visible"; Punto 10 (l.2620): "Input disabled, código visible".
- **Punto 18 (l.2628):** "Botones según permiso del rol"; Checkpoint #20 (l.2586): "Botones según permisos | `v-if="can('action', entity)"`".
- **Vistas admin ejemplificadas:** `@/views/admin/AdminLayout.vue` (l.2401), `@/views/admin/users/UsersView.vue` (l.2407), `@/views/public/auth/LoginView.vue` (l.2396), `@/views/ForbiddenView.vue` (l.2415).

**Conclusión literal:** el documento **no habla de Filament ni de ningún CMS ni de un panel de administración como producto de terceros**. El "panel admin" existe únicamente como (a) una ruta `/admin` en la SPA Vue con `AdminLayout.vue`, (b) protegida por el permiso `panel-administrative`, (c) con sidebar filtrado por permisos según la auditoría, y (d) con autorización delegada a Spatie Permission en el backend. **No hay superficie editorial, ni editor de contenido, ni gestión de medios/páginas.**

### 10.2 Lo que SÍ dice el documento sobre Tailwind — adoptado, no prohibido

- **l.497 (instalación):** `npm install pinia vue-router@4 @tanstack/vue-query @tanstack/vue-table @tanstack/vue-form zod axios tailwindcss @tailwindcss/vite vue3-toastify @fortawesome/fontawesome-free`
- **l.1888 (stack §4.1):** "| Estilos | TailwindCSS v4 | CSS-first con `@theme {}` |"
- **l.3234 (Apéndice A):** "| Tailwind CSS | https://tailwindcss.com/docs |"

→ **TailwindCSS v4 es el sistema de estilos oficial**, con enfoque **CSS-first** mediante `@theme {}`. No es una prohibición; es un requisito.
→ **No consta** ninguna referencia a un framework CSS alternativo.

### 10.3 Lo que SÍ dice el documento sobre Bootstrap — NO hay Bootstrap CSS

Las **únicas 2 coincidencias** de la cadena "bootstrap" son el archivo de Laravel, no el framework CSS:
- **l.1379:** `// En bootstrap/app.php (Laravel 11+):`
- **l.1786:** `### config/bootstrap/app.php (Laravel 11+)`

→ **Bootstrap (el framework CSS) NO se menciona en el documento.** No está adoptado ni prohibido: simplemente **no consta**. (El Apéndice A no lo lista.)

### 10.4 Lo que SÍ dice el documento sobre "gov.co" — solo un dominio de ejemplo

Único contexto (nginx en §8.3, l.2943-2944):
```nginx
ssl_certificate /etc/letsencrypt/live/midominio.gov.co/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/midominio.gov.co/privkey.pem;
```
y en `compose.yaml`/K8s aparecen `APP_NAME`, `midominio.gov.co` (l.2943) y `registry.midominio.co/miapp-app:1.4.0` (l.3038).

→ **`midominio.gov.co` es un FQDN de ejemplo en la configuración TLS de Certbot.** **NO CONSTA** ninguna mención al **kit gov.co**, a GOV.CO, al sistema de diseño del Estado colombiano, ni a identidad visual gubernamental en ninguna de las 3312 líneas. **Si el proyecto requiere kit gov.co, este documento no lo contempla.**

---

## 11. Hechos verificados (con sección y línea)

1. `hecho` — El documento tiene exactamente 3312 líneas y fue leído íntegro; su estructura de encabezados va de CAPÍTULO 0 a CAPÍTULO 9 más APÉNDICE A — §encabezados (l.23, 187, 450, 511, 1874, 2605, 2653, 2687, 2795, 3065, 3191).
2. `hecho` — El principio rector es "Contrato primero → Implementación por Vertical Slice → Verificación continua en cada paso" — §preámbulo (l.4).
3. `hecho` — El rigor exigido es "Nivel Doctorado. Un sistema que no supere cada checkpoint NO avanza a la siguiente fase." — §preámbulo (l.13).
4. `hecho` — El OpenAPI (`openapi.yaml`) es la **única fuente de verdad** del contrato HTTP; backend y frontend se acoplan al MISMO OpenAPI — §1 Fuente de Verdad Única (l.200).
5. `hecho` — §0.1 contiene **14 reglas absolutas numeradas** — §0.1 (l.31-87).
6. `hecho` — La regla absoluta 2 prohíbe Inertia y Blade: "Comunicación exclusiva por HTTP API + WebSockets. Sin Inertia, sin Blade." — §0.1 (l.33).
7. `hecho` — La regla absoluta 3 fija la estructura `backend/` + `frontend/` como carpetas hermanas — §0.1 (l.35-41).
8. `hecho` — `Gate::before` debe devolver `null` (nunca `false`) para no super-admin, y registrarse en `AppServiceProvider::boot()` desde Laravel 11 — §0.1 (l.47-57) y §3.6 (l.1242-1250).
9. `hecho` — El stack SDD obligatorio es `sdd-new → sdd-explore → sdd-propose → sdd-spec → sdd-design → sdd-tasks → [Contract-First] → sdd-apply → sdd-verify` — §0.1 (l.78-80).
10. `hecho` — Las 3 ramas permanentes son **`main`**, **`develop`**, **`staging`**; las tres están protegidas — §0.4 (l.136-142).
11. `hecho` — **El documento dice `main`, NO `master`.** La palabra "master" tiene **0 ocurrencias** en las 3312 líneas; `main` aparece como rama de producción en l.140 y destino final del flujo en l.164.
12. `hecho` — Los 7 prefijos de rama son `feat/`, `fix/`, `refactor/`, `docs/`, `test/`, `chore/`, `hotfix/`; el único ejemplo de nombre completo es `feat/{modulo}` — §0.4 (l.167-177, l.153, l.159).
13. `hecho` — El **Review Workload Guard** obliga a dividir en 2+ PRs encadenados cuando se superan **400 líneas modificadas** — §0.4 (l.183).
14. `hecho` — El PR debe poder revisarse en **≤ 30 minutos** y es "unidad de review, no de código" — §0.4 (l.181).
15. `hecho` — El flujo de cierre SDD por PR exige 4 pasos: Engram completo, `mem_session_summary`, `/sinapsis-learning`, `/promote`; y `/clear` solo al cerrar el cambio completo — §0.3 (l.121-130).
16. `hecho` — El Flat Envelope canónico es `{success, message, data, meta, errors}` con media type `application/json`; `application/vnd.api+json` está **prohibido** — §1 (l.193-194).
17. `hecho` — Los 5 componentes compartidos canónicos del contrato son `ApiEnvelope`, `PaginatedEnvelope`, `PageMeta`, `CollectionLinks`, `ApiError` — §1 (l.299-348).
18. `hecho` — Por cada recurso se declaran **tres** schemas: `<Recurso>Item`, `<Recurso>Collection`, `<Recurso>Input`, y el documento lo subraya: "(no cuatro)" — §1 (l.352-388).
19. `hecho` — El contrato se redacta en **OpenAPI 3.1.x**, YAML, archivo `openapi.yaml`, y se valida con **`openapi-spec-validator`** y estilo con **Redocly CLI** — §1 PASO 1 (l.218-223). El ruleset concreto de Redocly **no consta**.
20. `hecho` — El mock obligatorio es **Prism 4** en Docker sobre el puerto **4010**; "No se continúa al Paso 3 hasta que el mock responda correctamente" — §1 PASO 2 (l.406-412).
21. `hecho` — Los tipos TS se generan con `npx openapi-typescript openapi.yaml -o src/types/api.d.ts --read-write-markers` — §1 PASO 3 (l.424) y §4.3 (l.1938).
22. `hecho` — Las reglas del hook del contrato son **solo R-22** (`JsonResource` con `withoutWrapping()`, ✅ Actualizada) y **R-24** (OpenAPI debe usar `application/json`, ✅ Vigente) — §1 (l.445-446). **R-25 y R-26 no constan.**
23. `hecho` — En total el documento enumera **18 reglas R-xx**: R-06, R-07, R-09, R-10, R-17, R-19, R-22, R-24, R-37, R-38, R-39, R-40, R-41, R-44, R-45, R-46, R-47, R-51 — §3 y §4 (l.445-446, 1860-1870, 2543-2547).
24. `hecho` — El backend exige **PHP ^8.4**, **Laravel ^13**, **PostgreSQL 18**, **Pest 4 / PHPUnit 12**, **PHPStan nivel 8** y **Pint** — §3.1 (l.516-525) y §8.4 (l.2967-2975).
25. `hecho` — `declare(strict_types=1)` es **OBLIGATORIO en todo archivo PHP** — §3.1 (l.537), reforzado por R-17 (l.1860).
26. `hecho` — La cadena Clean Architecture es `Controller → FormRequest → Service → Repository → Model`, con `Contracts` como interfaz de Repository y Service — §2.3 (l.503-507).
27. `hecho` — El helper obligatorio es `App\Support\Api\ApiResponse` con 8 métodos: `ok`, `created` (201), `error`, `notFound` (404), `unauthorized` (401), `forbidden` (403), `validationError` (422), `tooManyRequests` (429) — §3.3 (l.817-873).
28. `hecho` — Los códigos HTTP fijados son 200, 201, 204, 401, 403, 404, 409, 422, 429 con su uso exacto — §3.17 (l.1812-1822).
29. `hecho` — El update se expone con verbo **`PATCH` (no `PUT`)**: la ruta canónica hace `apiResource(...)->except(['update'])` + `Route::patch('users/{user}', ...)` — §1 reglas de autoría (l.398) y §3.7 (l.1278-1279).
30. `hecho` — Los checkpoints backend son FASE 1 (Migración y Modelo, puntos 1-2), FASE 2 (Resource y FormRequest, 3-7), FASE 3 (Service y Policy, 8-11) — §3 (l.1826-1853).
31. `hecho` — Los checkpoints frontend son FASE 4 (Interfaces y Servicios, 12-15), FASE 5 (Composables y Store, 16), FASE 6 (Vistas y Formularios, 17-20), FASE 7 (Guards, 21-24), FASE 8 (Validaciones, 25) — §4 (l.2562-2601).
32. `hecho` — La auditoría final es de **25 puntos + C1–C7**, se ejecuta "al completar CADA módulo o Vertical Slice", y el criterio de aprobación es "**25/25 + C1–C7 ✅ → El módulo puede marcarse como terminado**" — §5 (l.2607, l.2649).
33. `hecho` — El punto C6 exige que `per_page>100` dispare **422**, aunque el documento no incluye el snippet que implementa ese límite — §5 (l.2646).
34. `hecho` — El frontend usa **Vue 3 `<script setup lang="ts">` + Composition API**, con "**NO Options API**" explícito, TypeScript strict con "cero `any`" — §4.1 (l.1880-1881).
35. `hecho` — El stack frontend completo es: Vue 3, TypeScript strict, Pinia, Axios (instancia única), Vue Router 4, TanStack Vue Query, TanStack Table v8, TanStack Form + Zod, TailwindCSS v4 (CSS-first con `@theme {}`), `vue3-toastify`, `@fortawesome/fontawesome-free` — §4.1 (l.1878-1889) y §2.2 (l.497).
36. `hecho` — El flujo de datos frontend es `VIEW → COMPOSABLE → SERVICE → HTTP CLIENT → INTERFACE (OpenAPI)` — §4.1 (l.1894).
37. `hecho` — El cliente Axios es una **instancia única** con `Content-Type`/`Accept: application/json`, `withCredentials: true`, `timeout: 15_000`, y maneja 401 (`clear()` + `router.push({name:'forbidden'})`) y 429 (warning) — §4.2 (l.1902-1930).
38. `hecho` — El guard de router manda al **no autenticado a `forbidden`**, nunca al login; y al autenticado en ruta `guest` a `admin.dashboard` — §4.9 (l.2420-2434), checkpoints 21-23 (l.2592-2594).
39. `hecho` — El permiso que gobierna el acceso al panel es el string **`panel-administrative`**, usado en la ruta `/admin` y en los puntos 20 y 24 de la auditoría — §4.9 (l.2402), §5 (l.2630, l.2634).
40. `hecho` — `localStorage` está **prohibido**: "❌ NO"; la alternativa es cookie `httpOnly` de Sanctum — §4.12 (l.2536, l.2543).
41. `hecho` — **Filament tiene 0 menciones**, **CMS tiene 0 menciones** y **"panel de administración"/"panel administrativo"/"administrativo" tienen 0 menciones** en las 3312 líneas (verificado con grep `-i`).
42. `hecho` — **Nuxt, SSR, SEO, sitemap y metaetiquetas tienen 0 menciones** en las 3312 líneas; el frontend se declara explícitamente **SPA** (§0.1 l.40, §3.8 l.1348, §4.12 l.2537).
43. `hecho` — **TailwindCSS v4 SÍ es stack oficial** (no está prohibido) con enfoque "CSS-first con `@theme {}`" — §4.1 (l.1888), instalación en l.497, doc oficial en l.3234.
44. `hecho` — **Bootstrap (el framework CSS) NO consta**: las 2 únicas ocurrencias de "bootstrap" son `bootstrap/app.php` de Laravel (l.1379, l.1786).
45. `hecho` — **El kit gov.co NO consta**: la única aparición de "gov.co" es el FQDN de ejemplo `midominio.gov.co` en las rutas de certificado TLS de Certbot — §8.3 (l.2943-2944).
46. `hecho` — Los niveles de prueba exigidos en el vertical slice son unitarias (BE y FE), integración, feature, suite completa (…+E2E) y validación visual — §0.2 (l.103-115). La herramienta de E2E **no consta**; no se menciona Vitest, Playwright, Cypress ni Jest (0 coincidencias).
47. `hecho` — La verificación post-sub-agente exige "servidores activos, login funciona desde navegador, tests pasan, build pasa" y "Las pruebas con curl no son suficientes" — §0.1 (l.71, l.73).
48. `hecho` — El Apéndice A lista documentación oficial de Laravel 13, PHP 8.4, OpenAPI 3.1, JSON:API, Vue 3, Flutter, PostgreSQL 18, Docker, K8s, FastAPI/LangGraph/vLLM/MiniMax/pgvector/MCP/Langfuse, Pest/PHPUnit/PHPStan/Pint — APÉNDICE A (l.3191-3308).
49. `hecho` — El capítulo de IA exige que las llamadas a LLM **nunca** vayan en el request HTTP síncrono: se encolan como jobs con timeout, reintentos con backoff e idempotencia — §9.3 (l.3162).
50. `hecho` — Los puertos canónicos son Nginx 80/443 (único expuesto), app Octane 8000, Reverb 8080, PostgreSQL 5432, Redis 6379, FastAPI 8001, vLLM 8200, Ollama 11434 — §8.1.1 (l.2821-2830).
51. `hecho` — Las redes canónicas son `{proyecto}-edge`, `{proyecto}-backend`, `{proyecto}-data` — §8.1.2 (l.2834-2838).

---

*Informe generado por lectura íntegra del documento (3312/3312 líneas) y verificación por búsqueda literal de cada término crítico. Sin invenciones: donde el documento calla, se indica "no consta".*
