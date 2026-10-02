# 05 — Especificaciones de la API

> **Versión:** 1.0
> **Especificación:** OpenAPI 3.1.0 (ADR-005) + JSON:API 1.0 (ADR-004).
> **Ruta base:** `https://api.santamarta.gov.co/api/v1/`
> **Content-Type:** `application/vnd.api+json` (todas las request/response).
> **Trazabilidad:** cada endpoint referencia los RF que implementa.

---

## Índice

| Archivo | Contenido |
|---|---|
| `openapi-3.1.yaml` | Spec completa (extraída a archivo por tamaño) |
| `endpoints-estructura-identidad.md` | Endpoints del módulo 01 |
| `endpoints-transparencia.md` | Endpoints del módulo 02 |
| `json-api-recursos.md` | Detalle de cada JSON:API Resource |
| `errores-y-codigos.md` | Catálogo de errores y formato estándar |

---

## Resumen ejecutivo

| Aspecto | Valor |
|---|---|
| Total endpoints | **48** |
| Endpoints públicos (sitio) | 32 (GET solamente) |
| Endpoints autenticados (panel) | 16 (GET/POST/PATCH/DELETE) |
| Versión | `v1` (en URL) |
| Auth | Sanctum (cookie HttpOnly + Bearer opcional) |
| Rate limit | 60 req/min IP pública · 300 req/min user autenticado |
| Formato | JSON:API 1.0 |
| Documentación | OpenAPI 3.1 en `contract/openapi.yaml` |

---

## Mapeo RF → Endpoints

### Módulo 01 — Estructura-Identidad

| RF | Endpoint |
|---|---|
| RF-01-001 | `GET /identidad/top-bar` |
| RF-01-002 | `GET /identidad/footer` |
| RF-01-003 | Validación en Form Request |
| RF-01-004 | `GET /identidad/logo` |
| RF-01-005 | `GET /identidad/kit-ui` (variables CSS) |
| RF-01-006 | Componente Vue (no endpoint) |
| RF-01-007 | `GET /identidad/menu` |
| RF-01-008 | Componente Vue (no endpoint) |
| RF-01-009 | `GET /buscar?q=...&autocompletar=true` |
| RF-01-010 | `GET /sitemap.xml` + `GET /mapa-del-sitio` |
| RF-01-011 | Componente Vue (breadcrumbs desde menú) |
| RF-01-012 | Job `VinculoRoto:Verificar` (no endpoint público) |
| RF-01-013 | `GET /noticias` + `GET /noticias/{slug}` |
| RF-01-014 | Componente Vue (no endpoint) |
| RF-01-015 | Página Vue (no endpoint) |
| RF-01-016 | Componente Vue (modal) |
| RF-01-017 | Componente Vue + cookie |
| RF-01-018 | `GET /politicas` |
| RF-01-019 | `GET /politicas/terminos` |
| RF-01-020 | `GET /politicas/privacidad` |
| RF-01-021 | Componentes UI (no endpoint específico) |
| RF-01-022 | `CRUD /panel/plan-integracion` |

### Módulo 02 — Transparencia

| RF | Endpoint |
|---|---|
| RF-02-001 | `GET /transparencia/subsecciones` |
| RF-02-002 | `GET /transparencia/documentos?sort=-fecha_publicacion` |
| RF-02-003 | `GET /transparencia/buscar?q=...` |
| RF-02-004 | URL canónica `GET /transparencia/documentos/{slug}` |
| RF-02-005 | `GET /transparencia/subsecciones/informacion-entidad` |
| RF-02-006 | `GET /transparencia/directorio` |
| RF-02-007 | `GET /transparencia/subsecciones/grupos-interes` |
| RF-02-008 | `GET /transparencia/documentos?filter[subseccion]=normativa` |
| RF-02-009 | `GET /transparencia/enlaces-externos?codigo=suin` |
| RF-02-010 | `GET /transparencia/documentos?filter[subseccion]=contratacion` |
| RF-02-011 | `GET /transparencia/documentos?filter[categoria]=plan_accion&filter[vigencia]=2026` |
| RF-02-012 | `GET /transparencia/documentos?filter[categoria]=informe_gestion` |
| RF-02-013 | `GET /transparencia/documentos?filter[categoria]=informe_pqrsd` |
| RF-02-014 | `GET /transparencia/documentos?filter[categoria]=informe_control_interno` |
| RF-02-015 | `GET /transparencia/subsecciones/tributaria` |
| RF-02-016 | `GET /transparencia/calendario-tributario` |
| RF-02-017 | `GET /transparencia/subsecciones/datos-abiertos` |
| RF-02-018 | `GET /transparencia/subsecciones/tramites` |
| RF-02-019 | `GET /transparencia/subsecciones/participa` |
| RF-02-020 | `GET /transparencia/subsecciones/reporte-especifico` |
| RF-02-021 | `POST/PATCH /panel/transparencia/documentos` + versionado |
| RF-02-022 | `GET /panel/alertas` + job `AlertaPublicacion:Verificar` |
| RF-02-023 | Manejo en backend de excepciones + UI |
| RF-02-024 | Hash en respuesta `GET /transparencia/documentos/{slug}` |
| RF-02-025 | Validación en Form Request + reporte ITA |
| RF-02-026 | Validación en Form Request |
| RF-02-027 | `GET /transparencia/buscar` |
| RF-02-028 | Filtros `filter[subseccion]=...&filter[vigencia]=...` |
| RF-02-029 | `GET /panel/ita/tablero` |
| RF-02-030 | Campos en `GET /transparencia/documentos/{slug}?include=metadatos` |
