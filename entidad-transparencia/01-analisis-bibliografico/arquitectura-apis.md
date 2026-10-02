# 01.3 — Arquitectura y APIs

> Marco teórico de la arquitectura de software y de la especificación de APIs que sustentan las decisiones técnicas de este proyecto.

---

## 1. Roy T. Fielding — Architectural Styles and the Design of Network-based Software Architectures (PhD Dissertation, UC Irvine, 2000)

Fielding [1] define el estilo arquitectónico **REST** (Representational State Transfer) en su tesis doctoral. Los seis **constraints** que definen REST son:

1. **Cliente-servidor:** separación de responsabilidades; el cliente no conoce el almacenamiento, el servidor no conoce la UI.
2. **Sin estado (stateless):** cada petición del cliente contiene toda la información necesaria; el servidor no guarda estado de sesión entre peticiones.
3. **Cacheable:** las respuestas deben indicar si son cacheables y por cuánto tiempo.
4. **Interfaz uniforme:** recursos identificados por URI; manipulación a través de representaciones; mensajes autodescriptivos; HATEOAS como hipermedia.
5. **Sistema en capas:** el cliente no puede distinguir si habla con el servidor final o un intermediario.
6. **Code-on-demand (opcional):** el servidor puede extender la funcionalidad del cliente enviando código ejecutable.

> **Aplicación:** este proyecto implementa REST con la convención del sobre plano `{success, message, data, errors}` (ver ADR-002). JSON:API v1.0 [2] se cita como referencia conceptual para los principios de recursos, pero la convención adoptada es **plana**, no `application/vnd.api+json` (descartada por el equipo, ver `backend/AGENTS.md`).

### 1.1 Principios adoptados de REST en este proyecto

- **Recursos identificados por URI:** `/api/v1/tramites`, `/api/v1/transparencia/normativas/{id}`.
- **Representaciones JSON:** `application/json`.
- **Stateless:** la autenticación viaja en cada petición (cookie de sesión para navegador, Bearer token para API).
- **Verbos HTTP correctos:** GET (lectura), POST (creación), PATCH (actualización parcial, nunca PUT), DELETE (eliminación).
- **Versionado por URL:** `/api/v1/` (ver ADR-009).

---

## 2. JSON:API v1.0 — Especificación

La especificación **JSON:API v1.0** [2] define un formato estándar para APIs JSON. **Decisión del proyecto:** se citan los principios (recursos con `type` + `id`, relaciones, sparse fieldsets, includes) como referencia conceptual, **pero no se adopta** el formato completo `application/vnd.api+json`. Véase:

- `backend/AGENTS.md`: "El sobre es plano `{success, message, data, errors}` con `application/json`. `application/vnd.api+json` está descartado."
- `contract/openapi.yaml` líneas 12–23: justificación normativa.

### 2.1 Principios adoptados de JSON:API

| Principio JSON:API | Aplicación en este proyecto |
|---|---|
| Recurso con `type` + `id` | Todo recurso lleva `id` (entero) + `type` (string en kebab-case, plural, declarado con `const` en OpenAPI) |
| Sparse fieldsets | Parámetro `?fields[tramites]=id,nombre,slug` opcional |
| Includes (relaciones) | Parámetro `?include=categoria` opcional |
| Paginación | `page` y `per_page` (≤100) |
| Errores | `success: false` + `message` + `errors` por campo |

---

## 3. OpenAPI 3.1 — Especificación

**OpenAPI 3.1** [3] es el estándar OAS para describir APIs RESTful. En este proyecto:

- **Fuente de verdad del contrato:** `contract/openapi.yaml` (931 líneas, vigente).
- **Generación de tipos TypeScript:** `openapi-typescript` (declarado en `sitio/package.json`).
- **Documentación navegable:** Redocly con `contract/redocly.yaml`.
- **Validación CI:** la prueba de **deriva** garantiza que ninguna ruta exista sin operación declarada en el contrato.

### 3.1 Secciones del OpenAPI declaradas

- `info.title`, `version`, `summary`, `description` con texto normativo.
- `servers` (staging + local).
- `tags` (Entidad, Trámites; en este sprint: Identidad, Transparencia).
- `paths` con `operationId` único.
- `components.schemas` con esquemas nombrados (`ApiEnvelope`, `PaginatedEnvelope`, `TramiteItem`, etc.).
- `x-criterios` extensión para vincular la operación a los RF/RN que cubre.

---

## 4. Simon Brown — The C4 Model for Visualising Software Architecture

El **modelo C4** [4] define cuatro niveles de abstracción para visualizar arquitectura de software:

### 4.1 Niveles del modelo C4

| Nivel | Alcance | Audiencia | Pregunta que responde |
|---|---|---|---|
| **C1 — Context** | Sistema en su entorno (usuarios, sistemas externos) | Stakeholders no técnicos | ¿Qué hace el sistema para sus usuarios? |
| **C2 — Containers** | Aplicaciones, bases de datos, sistemas de almacenamiento | Técnicos y tech leads | ¿Cómo se distribuye? |
| **C3 — Components** | Componentes dentro de cada contenedor | Desarrolladores | ¿Cómo se organiza internamente? |
| **C4 — Code** | Diagramas UML / clases | Desarrolladores (opcional) | ¿Cómo se implementa? |

> **Aplicación en este proyecto:** `03-propuesta/diagramas-c4.md` contiene los niveles C1 y C2 con diagramas Mermaid; el nivel C3 se cubre en `03-propuesta/estructura-directorios.md` (estructura de carpetas Laravel + Nuxt); el nivel C4 se omite (la documentación de código se hace en PHPDoc y TSDoc).

---

## 5. Eric Evans — Domain-Driven Design

Evans [5] define **DDD** como un enfoque para construir software donde la estructura del código refleja el dominio del negocio.

### 5.1 Conceptos adoptados

- **Lenguaje ubicuo (Ubiquitous Language):** el vocabulario del SRS y de la BD coincide con el de los stakeholders (ej. "tramite", "solicitud", "radicado" son los mismos términos en todos los documentos).
- **Agregados (Aggregates):** cada agregado tiene una raíz; en Laravel se modela con Eloquent y relaciones.
- **Repositorios:** el acceso a datos se hace a través de `RepositoryInterface` (no directo desde Eloquent). Implementado en `app/Repositories/Eloquent/`.
- **Servicios de dominio:** la lógica de negocio reside en `app/Services/` (no en controladores).
- **Value Objects:** tipos inmutables sin identidad; representados en PHP con `final readonly class`.

### 5.2 Capas aplicadas (ver `backend/AGENTS.md`)

```
Controller → FormRequest → Service → Repository → Model
```

Cada capa tiene una responsabilidad única y testeable de forma aislada.

---

## 6. Sam Newman — Building Microservices (2ª ed.)

Newman [6] presenta los principios y patrones para construir microservicios. Aunque este proyecto es **monolito modular** (no microservicios), algunos principios se adoptan:

### 6.1 Principios adoptados (en contexto monolítico)

- **API primero:** el contrato OpenAPI es la fuente de verdad; el código se ajusta al contrato.
- **Back-end for front-end (BFF):** el backend sirve a dos clientes (sitio público Nuxt + panel de administración Nuxt); ambos consumen la misma API.
- **Resiliencia:** ante fallo de integraciones externas (SECOP, SIGEP, SUIN, SUCOP, KOGUI), el sistema degrada con mensaje claro (no muestra error HTTP crudo).
- **Observabilidad:** logs estructurados + métricas (a documentar en módulo de observabilidad).

---

## Referencias IEEE (arquitectura y APIs)

[1] R. T. Fielding, *Architectural Styles and the Design of Network-based Software Architectures*, PhD Dissertation, University of California, Irvine, 2000. [En línea]. Disponible: https://www.ics.uci.edu/~fielding/pubs/dissertation/top.htm

[2] JSON:API Working Group, *JSON:API Specification, Version 1.0*, 2015–2025. [En línea]. Disponible: https://jsonapi.org/format/

[3] OpenAPI Initiative, *OpenAPI Specification, Version 3.1.0*, Linux Foundation, 2021–2025. [En línea]. Disponible: https://spec.openapis.org/oas/v3.1.0

[4] S. Brown, *The C4 Model for Visualising Software Architecture*, 2011–2025. [En línea]. Disponible: https://c4model.com/

[5] E. Evans, *Domain-Driven Design: Tackling Complexity in the Heart of Software*, Boston, MA: Addison-Wesley Professional, 2003.

[6] S. Newman, *Building Microservices: Designing Fine-Grained Systems*, 2nd ed. Sebastopol, CA: O'Reilly Media, 2021.
