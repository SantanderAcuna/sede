# 03 — Propuesta Arquitectónica

> **Propósito:** especificar la arquitectura del sistema completo (sede electrónica) para los módulos **01-estructura-identidad** y **02-transparencia**, incluyendo la base de datos `_bd/`. La arquitectura es **desacoplada por obligación** (RT-01): un único backend API Laravel 13 consumido por **dos clientes físicamente separados y con stacks diferenciados**:
> - **`sitio/`** → **Nuxt 4 + Vue 3 + TypeScript** (SSR para el ciudadano, SEO crítico para Ley 1712/2014, file-based routing, `$fetch` SSR-safe).
> - **`panel/`** → **Vue 3 + Vite + TypeScript** (SPA pura, autenticación Sanctum con cookie HttpOnly + MFA, Axios con `withCredentials`, `vue-router` manual).
>
> Ambos comparten únicamente los **tipos TS generados del OpenAPI** (`pnpm openapi:generar-ts`). **No comparten componentes, stores ni lógica de negocio.**
>
> **Marco:** C4 Model [16], ADRs (Michael Nygard), 13 zones architecture (#217).
> **Decisiones críticas:** documentadas como ADRs (≥5) con justificación normativa/técnica.

---

## Índice

| Documento | Contenido |
|---|---|
| `vision-arquitectonica.md` | Principios, objetivos, restricciones globales, vista C1 (contexto) |
| `diagramas-c4.md` | Diagramas C4: contexto (C1), contenedores (C2), componentes (C3), código (C4) |
| `adr.md` | **≥10 Architecture Decision Records** con justificación |
| `estructura-directorios.md` | Árbol completo de `backend/`, `sitio/`, `panel/` con justificación |
| `patron-comunicacion.md` | HTTP/JSON:API, versionado, rate limit, CORS, errores |
| `estrategia-despliegue.md` | Despliegue: contenedores, CI/CD, observabilidad |

---

## Resumen ejecutivo de la arquitectura

| Aspecto | Decisión | Justificación |
|---|---|---|
| Patrón arquitectónico | Desacoplado: API única + 2 clientes | RT-01, #217 |
| Backend | Laravel 13 + PHP 8.3 | Stack fijado |
| Frontend público | **Nuxt 4** (sitio) | SSR para SEO de transparencia (Ley 1712 Art. 9) |
| Frontend admin | **Vue 3 + Vite** (panel) | SPA pura, autenticación Sanctum + MFA |
| Serialización API | JSON:API 1.0 (Laravel nativo) | RT-02, [17] |
| Documentación API | OpenAPI 3.1 | RT-05, [15] |
| Autenticación | Laravel Sanctum (sesión SPA + Bearer) | RT-11, [19] |
| BD | PostgreSQL 15+ (MySQL 8.0+ opcional) | RT-04 |
| Cache/Queue | Redis | Rendimiento |
| Storage objetos | S3-compatible (MinIO en dev) | Almacenamiento documentos |
| Búsqueda | PostgreSQL FTS (ElasticSearch opcional a futuro) | Capacidad de búsqueda |
| Container | Docker + Docker Compose (dev), Kubernetes opcional (prod) | Despliegue |
| Despliegue | Cloudflare (CDN + WAF + DDoS) | Seguridad |
| Monitoreo | Laravel Telescope (dev), Prometheus + Grafana (prod), Sentry (errores) | Observabilidad |
| CI/CD | GitHub Actions | DevOps |
| Tests backend | Pest + Laravel Pint + Larastan | RT-12 |
| Tests frontend | Vitest + Vue Test Utils + Playwright + axe-core | RT-12 |

---

## Vista C1 — Contexto del sistema

```mermaid
C4Context
    title Diagrama C1 — Contexto del sistema

    Person(ciudadano, "Ciudadano", "Persona que consulta la sede pública")
    Person(admin, "Administrador / Editor", "Funcionario de la Alcaldía que gestiona contenidos")
    Person(ente, "Ente de control / MinTIC", "Supervisa cumplimiento ITA")

    System(sede, "Sede Electrónica Santa Marta", "Plataforma de transparencia y servicios del Distrito")

    System_Ext(govco, "Portal Único GOV.CO", "Punto único de acceso al Estado colombiano")
    System_Ext(sigep, "SIGEP", "Sistema de Información y Gestión del Empleo Público")
    System_Ext(secop, "SECOP I/II", "Sistema Electrónico de Contratación Pública")
    System_Ext(suin, "SUIN", "Sistema Único de Información Normativa")
    System_Ext(sucop, "SUCOP", "Sistema Único de Consulta Pública")
    System_Ext(datosAbiertos, "datos.gov.co", "Portal de Datos Abiertos Colombia")
    System_Ext(and, "AND / Carpeta Ciudadana", "Servicios Ciudadanos Digitales")

    Rel(ciudadano, sede, "Consulta transparencia, trámites, directorio", "HTTPS")
    Rel(admin, sede, "Edita contenidos, gestiona documentos, aprueba", "HTTPS + Sanctum")
    Rel(ente, sede, "Audita cumplimiento ITA", "HTTPS")
    Rel(sede, govco, "Redirige con enmascaramiento", "HTTPS")
    Rel(sede, sigep, "Sincroniza directorio servidores", "API REST")
    Rel(sede, secop, "Enlaza contratos", "HTTPS")
    Rel(sede, suin, "Enlaza normativa", "HTTPS")
    Rel(sede, sucop, "Recibe comentarios a normas", "API")
    Rel(sede, datosAbiertos, "Publica datasets", "API CKAN")
    Rel(sede, and, "SSO y Carpeta Ciudadana", "OpenID Connect / X-Road")
```

---

## Vista C2 — Contenedores

```mermaid
C4Container
    title Diagrama C2 — Contenedores de la Sede Electrónica

    Person(ciudadano, "Ciudadano", "Consulta pública")
    Person(admin, "Administrador", "Gestión interna")

    System_Boundary(sede, "Sede Electrónica") {
        Container(sitio, "Sitio público (Nuxt 4 SSR)", "Vue 3 + TS", "Sirve páginas públicas de transparencia con SSR para SEO")
        Container(panel, "Panel admin (Vue 3 + Vite SPA)", "Vue 3 + TS", "Editor de contenidos con Sanctum + MFA")
        Container(api, "API Laravel 13", "PHP 8.3", "Expone toda la lógica de negocio vía /api/v1/*")
        Container(worker, "Worker (Horizon)", "PHP 8.3 + Redis", "Procesa colas: SIGEP sync, hash, notificaciones, alertas")
        Container(cdn, "CDN Cloudflare", "Edge", "Cache + WAF + DDoS + TLS termination")
    }

    System_Ext(govco, "GOV.CO", "")
    System_Ext(sigep, "SIGEP", "")
    System_Ext(secop, "SECOP", "")
    System_Ext(suin, "SUIN", "")
    System_Ext(sucop, "SUCOP", "")
    System_Ext(and, "AND", "")

    Rel(ciudadano, cdn, "Navega", "HTTPS")
    Rel(admin, cdn, "Accede panel", "HTTPS")
    Rel(cdn, sitio, "Cache miss", "HTTPS")
    Rel(cdn, panel, "Cache miss", "HTTPS")
    Rel(sitio, api, "GET /api/v1/*", "HTTPS + JSON:API")
    Rel(panel, api, "GET/POST/PATCH/DELETE", "HTTPS + Sanctum")
    Rel(api, worker, "Encola jobs", "Redis")
    Rel(worker, sigep, "Sincroniza", "API REST")
    Rel(api, secop, "Enlaza", "HTTPS")
    Rel(api, suin, "Enlaza", "HTTPS")
    Rel(api, sucop, "Recibe comentarios", "API")
    Rel(api, and, "SSO OIDC", "OpenID Connect")
    Rel(sitio, govco, "Redirige con enmascaramiento", "HTTPS")
```

---

## Vista C3 — Componentes del Backend

```mermaid
C4Component
    title Diagrama C3 — Componentes del Backend Laravel 13

    Container_Boundary(api, "API Laravel 13") {
        Component(routes, "Routes", "routes/api.php", "Declara endpoints /api/v1/*")
        Component(middleware, "Middleware", "Auth, CORS, RateLimit, JSON:API", "Filtros transversales")
        Component(controllers, "Controllers", "Api/V1/*Controller", "Adaptan HTTP a casos de uso")
        Component(formRequests, "Form Requests", "Validation rules", "Validan entrada")
        Component(resources, "JSON:API Resources", "Serialización", "Transforman modelos a JSON:API")
        Component(services, "Services", "Lógica de negocio", "TransparenciaService, IdentidadService, etc.")
        Component(repositories, "Repositories", "Acceso a datos", "Eloquent + QueryBuilder")
        Component(models, "Eloquent Models", "Modelos de dominio", "Entidades mapeadas")
        Component(jobs, "Jobs / Queues", "Tareas asíncronas", "HashDoc, SincronizarSigep, AlertaPublicacion")
        Component(events, "Events / Listeners", "Desacople", "Notificación enviada, etc.")
        Component(policies, "Policies", "Autorización", "Gates para panel")
    }

    ContainerDb(db, "PostgreSQL 15", "BD relacional")
    ContainerRedis(redis, "Redis 7", "Cache + Queue")
    ContainerS3(s3, "S3-compatible", "Storage objetos")

    Rel(routes, middleware, "")
    Rel(middleware, controllers, "")
    Rel(controllers, formRequests, "Valida")
    Rel(controllers, resources, "Serializa")
    Rel(controllers, services, "Delega")
    Rel(services, repositories, "")
    Rel(repositories, models, "")
    Rel(models, db, "Eloquent")
    Rel(controllers, jobs, "Encola")
    Rel(jobs, redis, "Persiste")
    Rel(services, s3, "Almacena docs")
    Rel(controllers, policies, "Autoriza")
    Rel(services, events, "Dispara")
```

---

## Estructura de directorios

Ver `estructura-directorios.md`.

---

## ADRs

Ver `adr.md` (≥10 decisiones).

---

## Estrategia de despliegue

Ver `estrategia-despliegue.md`.
