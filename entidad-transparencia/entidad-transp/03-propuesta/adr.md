# Architecture Decision Records (ADR)

> **Marco:** plantilla Michael Nygard (2011) +扩展 con secciones de calidad y trazabilidad.
> **Estado:** cada ADR pasa por `Propuesto → Aceptado → Reemplazado/Rechazado`.
> **Trazabilidad:** cada ADR referencia los RF, RNF y restricciones que lo motivan.

---

## ADR-001 — Backend único Laravel 13 expone toda la lógica vía API REST

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** El proyecto requiere servir dos productos distintos (sitio público + panel de administración) sobre una misma lógica de negocio (transparencia, directorio, documentos). Una arquitectura monolítica con vistas Blade duplica código; una arquitectura de microservicios introduce complejidad operativa no justificada para un equipo de 4 devs backend.
- **Decisión:** Adoptar un único backend Laravel 13 que expone TODA la lógica vía API REST `/api/v1/*` con JSON:API 1.0. Los dos clientes desacoplados (`sitio/` Nuxt 4 + `panel/` Vue 3/Vite) consumen este único backend.
- **Alternativas consideradas:**
  1. **Monolito Laravel con Blade y dos sub-apps:** descarte — duplica lógica de negocio, hace imposible el versionado del cliente.
  2. **Microservicios (separar Transparencia, Directorio, etc. en servicios):** descarte — premature optimization, complejidad operativa innecesaria para el equipo actual.
  3. **Backend NestJS + PostgreSQL:** descarte — fuera del stack fijado por MinTIC/Ley GEL.
- **Consecuencias:**
  - **Positivas:** un único lugar para lógica de negocio; versionado claro; testing centralizado.
  - **Negativas:** cualquier pico de tráfico en el panel puede afectar el sitio público si no hay aislamiento de recursos (mitigado por rate limiting y CDN).
  - **Neutras:** se debe documentar muy bien el contrato OpenAPI.
- **Referencias:** RT-01, RT-02, RT-05, [13] Fielding, [14] JSON:API, [19] Laravel Docs.

## ADR-002 — Dos clientes desacoplados: `sitio/` (Nuxt 4 SSR público) + `panel/` (Vue 3 + Vite SPA admin)

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** El sitio público (ciudadano) requiere SSR para SEO (la transparencia debe ser indexable, exigencia implícita de Ley 1712/2014) y alta accesibilidad. El panel admin (editor/aprobador) requiere autenticación robusta con Sanctum (cookie HttpOnly + CSRF), MFA, UX densa y manejo de sesión persistente. Un único bundle Nuxt para ambos casos haría compromisos en SSR (panel no necesita) o en autenticación SPA (sitio no requiere). El stack diferenciado refleja la naturaleza distinta de cada cliente.
- **Decisión:** Crear dos clientes completamente independientes con stacks diferenciados:
  - **`sitio/`** (puerto 3000): **Nuxt 4** + Vue 3 + TypeScript, SSR habilitado, file-based routing (`app/pages/`), `$fetch` + `useFetch` para HTTP público, sin autenticación, auto-import de composables y componentes.
  - **`panel/`** (puerto 3001): **Vue 3 + Vite + TypeScript** (SPA pura, **sin Nuxt**), `vue-router` manual (`src/router/index.ts`), Axios con `withCredentials` para Sanctum, autenticación + MFA + Pinia, sin SSR.
- **Alternativas consideradas:**
  1. **Ambos Nuxt 4 (sitio SSR + panel SSR):** descarte — el panel no se beneficia del SSR; el SSR añade latencia al login y al dashboard inicial; bundle mayor innecesario.
  2. **Ambos Vue 3 + Vite (sin Nuxt en el sitio):** descarte — pierde SSR nativo, file-based routing, auto-imports y `$fetch` SSR-safe; afecta severamente el SEO de la transparencia pública (Art. 9 Ley 1712).
  3. **Nuxt único con módulos separados:** descarte — el bundle sigue siendo uno y los compromisos en SSR/auth se mantienen.
  4. **Panel en Next.js / React:** descarte — fuera del stack Vue fijado.
- **Consecuencias:**
  - **Positivas:** cada cliente optimizado para su propósito (SSR para SEO público; SPA para UX admin reactivo); stacks nativos de cada rol; tipos TS compartidos generados del OpenAPI; despliegues independientes; menor superficie de ataque en el panel.
  - **Negativas:** doble trabajo de CI/CD; doble `package.json`; doble cobertura de tests; posibles duplicaciones de tipos entre ambos (mitigado por el generador único del OpenAPI).
  - **Neutras:** requiere disciplina para no compartir lógica de negocio entre clientes; documentación explícita de la frontera entre ambos (ver §0 de `estructura-directorios.md`).
- **Referencias:** RT-01, RNF-ACES-01, RNF-REND-02, [20] Vue Docs, [21] Nuxt 4 Docs.

## ADR-003 — PostgreSQL 15+ como motor de base de datos único

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** El sistema requiere transacciones ACID, JSON nativo (para flexibilidad en metadatos de documentos), full-text search (FTS con `tsvector`), partitioning (para logs de auditoría), RLS (para políticas de acceso por fila en datos sensibles como datos personales).
- **Decisión:** Adoptar PostgreSQL 15+ como único motor.
- **Alternativas consideradas:**
  1. **MySQL 8.0+:** descarte — FTS menos maduro, sin partitioning declarativo nativo en la versión comunitaria.
  2. **MariaDB:** descarte — menor soporte de herramientas de monitoreo.
  3. **MongoDB como complemento:** descarte — añade complejidad operacional; los datos son altamente relacionales.
- **Consecuencias:**
  - **Positivas:** FTS nativo, JSONB indexable, partitioning declarativo, RLS, replicación streaming madura.
  - **Negativas:** costo mayor en hosting gestionado (Cloud SQL PostgreSQL es ~10% más caro que MySQL).
  - **Neutras:** requiere expertise PostgreSQL del equipo.
- **Referencias:** RT-04, [27] Date, [28] Silberschatz.

## ADR-004 — JSON:API 1.0 nativo Laravel (sin paquete de terceros)

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** RT-02 fija JSON:API sin paquetes de terceros. Laravel 13 trae soporte nativo para `JsonResource` con extensiones que cumplen JSON:API 1.0 (incluyendo `include`, `fields`, `sort`, `filter`, `page`).
- **Decisión:** Usar `App\Http\Resources\*Resource` extendiendo `JsonResource` con los métodos JSON:API requeridos. No usar `cloudcreativity/json-api` ni similares.
- **Alternativas consideradas:**
  1. **json-api-php/laravel-json-api:** descarte — viola RT-02.
  2. **JSON:API artesanal:** descarte — propenso a errores; reinventar la rueda.
  3. **GraphQL:** descarte — fuera del stack fijado; requiere otro cliente (Apollo/urql).
- **Consecuencias:**
  - **Positivas:** menos dependencias, mejor rendimiento, control total.
  - **Negativas:** se debe implementar manualmente el manejo de `include`, `fields`, sparse fieldsets (mitigado por `JsonResource` base).
  - **Neutras:** documentación interna del patrón es obligatoria.
- **Referencias:** RT-02, [14] JSON:API Specification, [19] Laravel Resources.

## ADR-005 — OpenAPI 3.1 como contrato API y fuente de verdad

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** RT-05 exige documentación OpenAPI 3.1; se requiere evitar drift entre spec y rutas; los dos clientes desacoplados (`sitio/` Nuxt 4 y `panel/` Vue 3/Vite) necesitan tipos TS generados automáticamente.
- **Decisión:** La spec `contract/openapi.yaml` es la fuente de verdad. Las rutas Laravel se generan automáticamente desde la spec (o se validan contra ella en CI). Los tipos TS se generan con `openapi-typescript`.
- **Alternativas consideradas:**
  1. **Generar spec desde código (Laravel OpenAPI):** descarte — la spec queda atada al código; cambios accidentales pasan.
  2. **Swagger PHP annotations:** descarte — duplica información en el código.
  3. **Postman + documentar manualmente:** descarte — propenso a desactualizarse.
- **Consecuencias:**
  - **Positivas:** contrato único, tipos TS actualizados, validación en CI; integración con MockServer para desarrollo frontend paralelo.
  - **Negativas:** disciplina para mantener spec actualizada antes de implementar.
  - **Neutras:** se requiere capacitación del equipo en OpenAPI 3.1.
- **Referencias:** RT-05, [15] OpenAPI Specification v3.1.

## ADR-006 — Laravel Sanctum para autenticación del panel

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** El panel requiere autenticación robusta con MFA, sesión persistente en navegador y posibilidad de tokens para integraciones (CI/CD, scripts). El sitio público no requiere autenticación.
- **Decisión:** Usar Laravel Sanctum con cookie HttpOnly + CSRF para sesión de navegador (panel), y Bearer Token opcional para integraciones externas.
- **Alternativas consideradas:**
  1. **JWT puro (tymon/jwt-auth):** descarte — más complejo, sin protección CSRF automática; revocación más difícil.
  2. **OAuth2 con Passport:** descarte — overkill para este caso; curva de aprendizaje alta.
  3. **Auth0 / Clerk externo:** descarte — costos recurrentes y datos de usuarios fuera de la jurisdicción colombiana.
- **Consecuencias:**
  - **Positivas:** primera clase en Laravel, MFA con TOTP (`pragmarx/google2fa`), revocación simple, integración con Spatie Permissions.
  - **Negativas:** requiere HTTPS obligatorio (mitigado por Cloudflare).
  - **Neutras:** se debe implementar el flujo de recuperación de contraseña.
- **Referencias:** RT-11, RNF-SEG-02, Ley 1581/2012, [19] Laravel Sanctum.

## ADR-007 — PostgreSQL FTS para búsqueda full-text

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** El buscador de transparencia debe buscar entre 100k+ documentos con tolerancia a errores ortográficos y resaltado. Elasticsearch sería ideal pero introduce complejidad operativa y costo extra.
- **Decisión:** Adoptar PostgreSQL FTS con extensión `unaccent`, `pg_trgm` para tolerancia a errores, e índices GIN.
- **Alternativas consideradas:**
  1. **Elasticsearch:** queda como upgrade opcional si el volumen crece >500k documentos o se requiere búsqueda semántica.
  2. **Algolia / Meilisearch externo:** descarte — datos fuera de Colombia.
  3. **MySQL FULLTEXT:** descarte — menor calidad en español y sin `pg_trgm`.
- **Consecuencias:**
  - **Positivas:** sin servicio adicional, datos en la misma BD, costos mínimos.
  - **Negativas:** búsqueda semántica (embeddings) no disponible; requiere diseño cuidadoso del índice.
  - **Neutras:** evaluación de Elasticsearch queda como spike post-go-live.
- **Referencias:** RNF-REND-01, RF-02-027, RF-01-009.

## ADR-008 — Cloudflare como edge (CDN + WAF + DDoS)

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** El sitio público debe soportar ≥1000 usuarios concurrentes (RNF-CAP-01) y resistir DDoS. Cloudflare ofrece CDN global, WAF, mitigación DDoS y TLS termination en un solo producto.
- **Decisión:** Cloudflare plan Business para el dominio principal, con:
  - DNS gestionado.
  - CDN con caché agresivo para assets estáticos.
  - WAF con reglas OWASP.
  - TLS 1.3 obligatorio.
  - Rate limiting perimetral.
- **Alternativas consideradas:**
  1. **AWS CloudFront + WAF:** descarte — más caro, configuración más compleja.
  2. **Sin CDN (origen directo):** descarte — riesgo de DDoS, latencia alta para usuarios lejanos.
- **Consecuencias:**
  - **Positivas:** seguridad por defecto, caché reduce carga al origen, latencia <50 ms en Colombia.
  - **Negativas:** dependencia de proveedor; costo mensual (USD 200 plan Business).
  - **Neutras:** se debe configurar correctamente el bypass de caché para el panel (autenticado).
- **Referencias:** RNF-SEG-01, RNF-CAP-01, RNF-REND-01.

## ADR-009 — Redis para cache de respuestas API y colas de jobs

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** La API tiene alta lectura (sitio público) y operaciones costosas (búsqueda, hash de documentos, sincronización SIGEP). Se necesita cache de respuestas y cola de jobs.
- **Decisión:** Redis 7 (Memorystore en GCP) para:
  - Cache de respuestas API (TTL 5 min por defecto).
  - Cache de sesión Sanctum.
  - Cola de jobs (Horizon).
  - Rate limiting (contadores).
- **Alternativas consideradas:**
  1. **Memcached:** descarte — menos features (sin colas, sin rate limit).
  2. **RabbitMQ + Memcached:** descarte — dos servicios en lugar de uno.
- **Consecuencias:**
  - **Positivas:** un solo servicio para cache + cola + rate limit; persistencia opcional con AOF.
  - **Negativas:** Redis como SPOF (mitigado por alta disponibilidad de Memorystore).
  - **Neutras:** se debe diseñar invalidación de caché cuidadosa.
- **Referencias:** RNF-REND-01, RNF-CAP-01, RF-02-021, [19] Laravel Redis.

## ADR-010 — Almacenamiento de documentos en S3-compatible (Cloud Storage)

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** Los documentos de transparencia (PDF, XLSX, CSV) suman ≥500 GB con crecimiento anual de 50 GB (RNF-CAP-02). Se requiere durabilidad, versionado y ciclo de vida.
- **Decisión:** Google Cloud Storage (compatible S3) con:
  - Bucket principal multi-regional.
  - Versionado habilitado.
  - Lifecycle policy: docs antiguos → Nearline (30 días) → Coldline (365 días).
  - Acceso vía `league/flysystem-aws-s3-v3` desde Laravel.
- **Alternativas consideradas:**
  1. **Almacenamiento en BD (BYTEA):** descarte — degrada rendimiento, backups gigantes.
  2. **Filesystem local del servidor:** descarte — no escalable, riesgo de pérdida.
- **Consecuencias:**
  - **Positivas:** durabilidad 99,999999999%, versionado automático, costos decrecientes con lifecycle.
  - **Negativas:** dependencia de proveedor; configuración IAM cuidadosa.
  - **Neutras:** se debe implementar URL firmada para descargas con expiración.
- **Referencias:** RNF-CAP-02, RN-06.

## ADR-011 — Cloudflare R2 vs GCS para objetos (consideración rechazada)

- **Fecha:** 2026-10-01
- **Estado:** Rechazado
- **Contexto:** Cloudflare R2 ofrece S3-compatible sin egress fees, lo cual es atractivo para documentos públicos descargados frecuentemente.
- **Decisión:** Descartado en favor de Google Cloud Storage.
- **Razones del descarte:**
  - Latencia desde Colombia es mayor con R2 (almacenado en US).
  - GCP ya está seleccionado para cómputo; consolidar almacenamiento simplifica IAM.
  - Egress fees de GCS son despreciables para el volumen estimado.
- **Consecuencias:** mantener consistencia con el ecosistema GCP.
- **Referencias:** RNF-REND-01.

## ADR-012 — Pruebas con Pest (backend) y Vitest + Playwright (frontend)

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** RT-12 fija Pest y Vitest; se requiere además tests E2E y accesibilidad.
- **Decisión:**
  - **Backend:** Pest (PHPUnit-compatible con sintaxis moderna).
  - **Frontend:** Vitest + Vue Test Utils para unit/component.
  - **E2E + accesibilidad:** Playwright + axe-core.
  - **Linting:** Pint (PHP), ESLint (TS).
  - **Análisis estático:** Larastan nivel 6.
- **Alternativas consideradas:**
  1. **PHPUnit "clásico":** descarte — viola RT-12.
  2. **Jest:** descarte — Vite usa Vitest por defecto.
  3. **Cypress:** descarte — Playwright es más rápido y mejor mantenido.
- **Consecuencias:**
  - **Positivas:** herramientas modernas, integración con CI, buena DX.
  - **Negativas:** doble suite de tests a mantener.
  - **Neutras:** se requiere capacitación del equipo en Playwright.
- **Referencias:** RT-12, RNF-MANT-01.

## ADR-013 — Búsqueda full-text con `pg_trgm` + `unaccent`

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** El buscador debe tolerar errores ortográficos (RF-01-009, RF-02-027) y resaltar coincidencias. PostgreSQL tiene extensiones maduras para esto.
- **Decisión:** Activar las extensiones `pg_trgm` (trigramas para tolerancia) y `unaccent` (ignorar tildes) en la BD; usar índices GIN en columnas relevantes (`titulo`, `descripcion`, `texto_pleno`).
- **Consecuencias:**
  - **Positivas:** búsqueda tolerante a "prespuesto" → "presupuesto" sin servicios externos.
  - **Negativas:** índices GIN ocupan más espacio en disco.
  - **Neutras:** se debe mantener actualizado el índice al insertar/modificar documentos (trigger o evento Laravel).
- **Referencias:** RF-01-009, RF-02-027, RNF-REND-01.

## ADR-014 — Cache de respuestas API con claves versionadas

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** El sitio público tiene alta lectura y datos que cambian poco (transparencia histórica). Se requiere cachear respuestas para mejorar TTFB.
- **Decisión:** Cachear respuestas API GET con clave `sha256(ruta + query_params + etag_contenido)`, TTL configurable por endpoint:
  - Top bar/footer: TTL 1h.
  - Lista de subsecciones: TTL 5 min.
  - Documento individual: TTL 15 min.
  - Búsqueda: TTL 1 min.
- **Invalidación:** evento Laravel `ContenidoPublicado` invalida claves relacionadas.
- **Consecuencias:**
  - **Positivas:** TTFB p95 ≤ 200 ms sin sacrificar frescura.
  - **Negativas:** riesgo de contenido desactualizado (mitigado por invalidación basada en eventos).
  - **Neutras:** se debe monitorear hit ratio.
- **Referencias:** RNF-REND-01, RF-02-021.

## ADR-015 — Despliegue en Google Cloud (GKE + Cloud SQL + Memorystore + GCS)

- **Fecha:** 2026-10-01
- **Estado:** Aceptado
- **Contexto:** Se requiere infraestructura gestionada, escalable, con regiones cercanas a Colombia.
- **Decisión:** Google Cloud Platform con:
  - **Cómputo:** GKE Autopilot (Kubernetes gestionado).
  - **BD:** Cloud SQL PostgreSQL 15 con HA y PITR 7 días.
  - **Cache/Queue:** Memorystore Redis 7 HA.
  - **Storage:** Cloud Storage multi-regional.
  - **CI/CD:** GitHub Actions → Artifact Registry → GKE.
- **Alternativas consideradas:**
  1. **AWS (ECS Fargate + RDS):** descarte — GCP tiene mejor presencia en Colombia y costo similar.
  2. **Servidor único (VM):** descarte — no escala, no cumple RNF-CAP-01.
  3. **Vercel + Neon (Postgres serverless):** descarte — limitante para Laravel PHP.
- **Consecuencias:**
  - **Positivas:** servicios gestionados, SLA 99,95%+, facturación por uso.
  - **Negativas:** costos fijos mensuales (~USD 800 operación).
  - **Neutras:** se requiere expertise Kubernetes (o usar Cloud Run como alternativa más simple).
- **Referencias:** RNF-DISP-01, RNF-DISP-02, RNF-CAP-01.

---

## Resumen de ADRs

| ID | Título | Estado |
|---|---|---|
| ADR-001 | Backend único Laravel 13 API REST | Aceptado |
| ADR-002 | `sitio/` Nuxt 4 (SSR) + `panel/` Vue 3+Vite (SPA) | Aceptado |
| ADR-003 | PostgreSQL 15+ como BD única | Aceptado |
| ADR-004 | JSON:API 1.0 nativo Laravel | Aceptado |
| ADR-005 | OpenAPI 3.1 como contrato | Aceptado |
| ADR-006 | Sanctum para autenticación | Aceptado |
| ADR-007 | PostgreSQL FTS para búsqueda | Aceptado |
| ADR-008 | Cloudflare como edge | Aceptado |
| ADR-009 | Redis para cache y queue | Aceptado |
| ADR-010 | S3-compatible para documentos | Aceptado |
| ADR-011 | Cloudflare R2 (rechazado) | Rechazado |
| ADR-012 | Pest + Vitest + Playwright | Aceptado |
| ADR-013 | `pg_trgm` + `unaccent` para FTS | Aceptado |
| ADR-014 | Cache de respuestas con claves versionadas | Aceptado |
| ADR-015 | Despliegue en Google Cloud | Aceptado |

> **Total:** 15 ADRs (14 aceptados + 1 rechazado), supera el mínimo exigido de 5.
