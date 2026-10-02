# Visión Arquitectónica

> **Propósito:** establecer los principios rectores, objetivos de calidad y restricciones globales que guían todas las decisiones arquitectónicas del proyecto.
> **Audiencia:** arquitectos de software, tech leads, revisores de PR, auditores de calidad.

---

## 1. Principios arquitectónicos

### PA-01 — Desacoplamiento total frontend/backend
- **Enunciado:** el backend NUNCA renderiza vistas Blade; el frontend NUNCA accede a la BD ni a rutas internas de Laravel.
- **Implicación:** la única superficie de integración entre cliente y servidor es la API REST `/api/v1/*` con JSON:API 1.0.
- **Justificación:** RT-01, [14] (Fielding, REST como estilo arquitectónico para sistemas hipermedia), [16] (Brown, C4: separar contenedores).

### PA-02 — API first, no API after-thought
- **Enunciado:** la especificación OpenAPI 3.1 es la fuente de verdad del contrato HTTP; las rutas Laravel se generan a partir de la spec (o se validan contra ella en CI).
- **Implicación:** un cambio en la spec dispara verificación de drift en CI (`php artisan openapi:verificar-drift`).
- **Justificación:** RT-05, [15] (OpenAPI Specification v3.1).

### PA-03 — Single Responsibility por servicio
- **Enunciado:** cada servicio de aplicación (`TransparenciaService`, `IdentidadService`, `DocumentoService`, `DirectorioService`, `NoticiaService`) tiene una única responsabilidad bien definida.
- **Implicación:** los servicios no se inyectan entre sí salvo cuando hay relación explícita y justificada.
- **Justificación:** [18] (Newman, microservicios: cohesión alta, acoplamiento bajo).

### PA-04 — Idempotencia en operaciones de escritura
- **Enunciado:** toda operación de escritura (POST/PATCH/DELETE) debe ser idempotente o tener un mecanismo explícito de no-idempotencia (token de operación).
- **Implicación:** uso de `Idempotency-Key` header en operaciones críticas.
- **Justificación:** buenas prácticas REST [13].

### PA-05 — Defense in depth en seguridad
- **Enunciado:** aplicar múltiples capas de seguridad: edge (Cloudflare WAF + DDoS), transporte (HTTPS + HSTS), aplicación (Sanctum + MFA + rate limit), datos (cifrado en reposo + control de acceso por roles).
- **Implicación:** ninguna capa asume que la anterior es infalible.
- **Justificación:** NIST Cybersecurity Framework; RNF-SEG-01..03.

### PA-06 — Observabilidad por defecto
- **Enunciado:** toda acción significativa debe quedar registrada (logs estructurados) y ser trazable; métricas de negocio y técnicas se exponen a Prometheus.
- **Implicación:** middleware `AuditarOperacion` envuelve automáticamente las operaciones críticas.
- **Justificación:** [18] (cap. 8, observabilidad).

### PA-07 — Accesibilidad como ciudadano de primera
- **Enunciado:** la accesibilidad WCAG 2.1 AA no es una capa final: se diseña desde la primera línea de código (componentes Vue con roles ARIA, foco gestionado, contraste mínimo 4.5:1).
- **Implicación:** axe-core corre en cada PR como quality gate.
- **Justificación:** RN-11, RNF-ACES-01, [29] (WCAG 2.1).

### PA-08 — Datos con integridad demostrable
- **Enunciado:** todo documento publicado debe tener su hash SHA-256 calculado y publicado; toda operación de modificación queda versionada (soft-delete + audit log).
- **Implicación:** tabla `documento_electronico` con `hash_sha256`, `version`, `deleted_at`.
- **Justificación:** RN-06, Ley 1712/2014 Art. 4.

---

## 2. Objetivos de calidad (ISO 25010:2011)

| Característica | Subcaracterística | Objetivo medible | RNF |
|---|---|---|---|
| Rendimiento | Tiempo de respuesta | TTFB p95 ≤ 200 ms, LCP p75 ≤ 2,5 s | RNF-REND-01..03 |
| Capacidad | Throughput | ≥1000 usuarios concurrentes | RNF-CAP-01 |
| Disponibilidad | Fiabilidad | ≥99,5% mensual | RNF-DISP-01 |
| Seguridad | Confidencialidad, integridad, autenticidad | HTTPS + Sanctum + MFA + hash | RNF-SEG-01..03 |
| Usabilidad | Operabilidad, atractivo | SUS ≥ 80 | RNF-USAB-01 |
| Accesibilidad | WCAG 2.1 AA | Lighthouse ≥ 90, axe-core 0 serias | RNF-ACES-01 |
| Mantenibilidad | Modularidad, analizabilidad, modificabilidad | Tests ≥80% back / ≥70% front | RNF-MANT-01 |
| Compatibilidad | Coexistencia, interoperabilidad | Navegadores modernos, responsive | RNF-PORT-01..02 |
| Protección de datos | Cumplimiento Ley 1581/2012 | ARCO ≤15 días hábiles | RNF-PD-01 |

---

## 3. Restricciones globales

### 3.1 Restricciones del entorno
- **Hosting objetivo:** Cloud Run (Google Cloud) o AWS ECS Fargate (decisión por ADR-008).
- **BD objetivo:** PostgreSQL 15+ gestionado (Cloud SQL o Aurora).
- **CDN:** Cloudflare (plan Business mínimo).
- **Región primaria:** Bogotá (latencia <50 ms para usuarios de Santa Marta).
- **Presupuesto mensual objetivo:** ≤USD 800 (operación) + USD 200 (storage).

### 3.2 Restricciones de equipo
- Equipo de 4 devs backend, 3 devs frontend, 1 diseñador UX/UI, 1 DevOps, 1 G-CIO.
- Metodología Scrum con sprints de 2 semanas.
- Code review obligatorio por 1 par antes de merge.

### 3.3 Restricciones de tiempo
- Go-live objetivo: 6 meses desde inicio del proyecto.
- ITA objetivo: ≥85 sobre 100 en autoevaluación.

---

## 4. Mapa de stakeholders arquitectónicos

| Rol | Responsabilidad arquitectónica |
|---|---|
| **Arquitecto de Software** | Define la arquitectura global, ADRs, revisa PRs críticos |
| **Tech Lead Backend** | Lidera implementación Laravel, define servicios |
| **Tech Lead Frontend** | Lidera implementación Nuxt, define componentes base |
| **DevOps** | Define infra, CI/CD, observabilidad |
| **G-CIO** | Sponsor, decisiones de compliance |
| **Oficial de Seguridad** | Aprueba medidas de seguridad, gestiona incidentes |
| **Oficial de Protección de Datos** | Aprueba tratamientos, gestiona derechos ARCO |

---

## 5. Decisiones clave pre-adoptadas

Estas decisiones se formalizan en `adr.md`:

| # | Decisión | Estado |
|---|---|---|
| 1 | Laravel 13 + PHP 8.3 | Adoptada |
| 2 | Nuxt 4 + Vue 3 + TS | Adoptada |
| 3 | PostgreSQL 15+ | Adoptada |
| 4 | JSON:API 1.0 nativo Laravel | Adoptada |
| 5 | OpenAPI 3.1 como contrato | Adoptada |
| 6 | Sanctum para autenticación | Adoptada |
| 7 | Dos clientes Nuxt independientes | Adoptada |
| 8 | Cloudflare como edge | Adoptada |
| 9 | Redis para cache y queue | Adoptada |
| 10 | PostgreSQL FTS para búsqueda | Adoptada (ElasticSearch queda como upgrade opcional) |

---

## 6. Glosario arquitectónico

| Término | Definición |
|---|---|
| **Contenedor (C4)** | Unidad desplegable independientemente (sitio, panel, API, worker). |
| **Componente (C4)** | Bloque lógico dentro de un contenedor (controller, service). |
| **Edge** | Capa perimetral (Cloudflare) entre el usuario y el origen. |
| **JSON:API** | Especificación de formato para APIs JSON hipermedia. |
| **OpenAPI** | Especificación estándar para describir APIs REST. |
| **Sanctum** | Paquete de autenticación de Laravel para SPAs y tokens. |
| **SCD** | Servicios Ciudadanos Digitales (Colombia). |
| **SSO** | Single Sign-On. |
| **OIDC** | OpenID Connect. |
| **X-Road** | Plataforma de interoperabilidad internacional. |
