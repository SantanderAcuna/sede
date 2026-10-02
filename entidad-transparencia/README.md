# 00 — README General · Ingeniería de Requisitos y Diseño Full Stack

> **Proyecto:** Sede Electrónica — Alcaldía Distrital de Santa Marta (D.T.C.H.)
> **Stack:** Laravel 13 + PHP 8.3 · **Vue 3 + TypeScript** (`sitio/` Nuxt 4 SSR + `panel/` Vite SPA) · PostgreSQL 15+
> **Enfoque arquitectónico:** Backend 100% API desacoplada · Frontend como **dos clientes físicamente separados** con stacks diferenciados
> **Nivel de rigor:** PhD / Senior Full Stack con experiencia en ingeniería de requisitos
> **Audiencia:** arquitecto, tech lead, desarrollador back-end/front-end, oficial de seguridad, oficial de cumplimiento, sponsor (Alcaldía / Dirección TIC)

---

## 1. Propósito

Este conjunto de documentos constituye la **especificación formal** para la construcción de los módulos **`01-estructura-identidad`**, **`02-transparencia`** y **`_bd`** (modelo de datos) de la Sede Electrónica de la Alcaldía Distrital de Santa Marta. Cubre elicitación, análisis bibliográfico, requisitos, arquitectura, modelo de datos, contrato de API, especificaciones de frontend, plan de tareas, trazabilidad y gestión de riesgos, en cumplimiento de:

- **Ley 1712 de 2014** — Transparencia y Acceso a la Información Pública.
- **Resolución MinTIC 1519 de 2020** — Estándares y directrices para sitios web.
- **Anexo Técnico 2 de MinTIC** — Identidad visual GOV.CO (Kit UI v9.2).
- **Ley 1581 de 2012** — Protección de Datos Personales.
- **Decreto 1081 de 2015 / Decreto 103 de 2015 / Decreto 2106 de 2019** — Reglamentación.
- **ISO/IEC/IEEE 29148:2018** y **IEEE 830-1998** — Ingeniería de requisitos.
- **JSON:API v1.0** y **OpenAPI 3.1** — Contratos de API.
- **WCAG 2.1 AA** — Accesibilidad web.

### Arquitectura desacoplada — recordatorio crítico

```
backend/   →  API Laravel 13 (única superficie de negocio vía /api/v1/* JSON:API)
                 │
                 ├──► sitio/   →  Nuxt 4 (SSR público para el ciudadano)
                 │              • File-based routing (app/pages/)
                 │              • $fetch + useFetch SSR-safe
                 │              • Sin autenticación
                 │              • Puerto dev: 3000
                 │
                 └──► panel/  →  Vue 3 + Vite (SPA admin autenticado)
                                • vue-router manual (src/router/)
                                • Axios + Sanctum (cookie HttpOnly + CSRF)
                                • Pinia stores en src/stores/
                                • <script setup lang="ts"> con macros tipadas
                                • Puerto dev: 3001
```

> **Regla de oro:** `sitio/` y `panel/` **NO comparten componentes, stores, ni lógica de negocio**. Comparten únicamente los **tipos TypeScript generados del OpenAPI** (`pnpm openapi:generar-ts`). Detalle completo en `03-propuesta/estructura-directorios.md` §0.

---

## 2. Audiencia y cómo leer

| Perfil | Documentos prioritarios | Por qué |
|---|---|---|
| **Sponsor / alta dirección** | `00-README`, `02-requisitos/`, `09-riesgos/` | Decisiones de alcance, priorización MoSCoW, riesgos críticos |
| **Arquitecto** | `03-propuesta/`, `04-diseno-bd/`, `05-especificaciones-api/` | Decisiones arquitectónicas (ADRs), modelo relacional, contrato de API |
| **Tech lead backend** | `03-propuesta/`, `04-diseno-bd/`, `05-especificaciones-api/`, `07-plan-tareas/` | Estructura de carpetas, migraciones, recursos, servicios, repositorios |
| **Tech lead frontend** | `03-propuesta/`, `05-especificaciones-api/`, `06-especificaciones-frontend/`, `07-plan-tareas/` | Tipos TS derivados, composables Nuxt, rutas, stores |
| **Desarrollador back-end** | `04-diseno-bd/`, `05-especificaciones-api/`, `07-plan-tareas/` | DDL exacto, endpoints, validaciones |
| **Desarrollador front-end** | `05-especificaciones-api/`, `06-especificaciones-frontend/`, `07-plan-tareas/` | Tipos TS, fetch, componentes Vue |
| **QA / ingeniero de pruebas** | `02-requisitos/` (criterios Gherkin), `07-plan-tareas/`, `08-trazabilidad/` | Cobertura, matriz requisito → test |
| **Oficial de seguridad / cumplimiento** | `01-analisis-bibliografico/`, `02-requisitos/` (RNF), `09-riesgos/` | Cumplimiento normativo, WCAG, Ley 1581 |
| **Auditor (MinTIC, ITA, AGN)** | `01-analisis-bibliografico/`, `02-requisitos/`, `08-trazabilidad/` | Evidencia documental de cumplimiento |

### Orden recomendado de lectura

1. **`00-README.md`** (este archivo) — visión panorámica.
2. **`01-analisis-bibliografico/`** — marco normativo y técnico.
3. **`02-requisitos/`** — qué se construye.
4. **`03-propuesta/`** — cómo se organiza.
5. **`04-diseno-bd/`** — el sustrato de datos.
6. **`05-especificaciones-api/`** — el contrato HTTP.
7. **`06-especificaciones-frontend/`** — el cliente Nuxt 4.
8. **`07-plan-tareas/`** — cuándo y quién.
9. **`08-trazabilidad/`** — coherencia requisito → tarea → test.
10. **`09-riesgos-y-supuestos.md`** — qué puede salir mal.

---

## 3. Glosario mínimo

| Término | Definición |
|---|---|
| **ADR** | Architecture Decision Record — registro formal de decisión arquitectónica. |
| **AGN** | Archivo General de la Nación. |
| **AND** | Agencia Nacional Digital — ente responsable de SCD, X-Road, Carpeta Ciudadana. |
| **API REST** | Interfaz de programación de aplicaciones basada en REST. |
| **Carpeta Ciudadana** | Servicio SCD de custodia de documentos del ciudadano. |
| **CCD** | Carpeta Ciudadana Digital. |
| **CKEditor / WYSIWYG** | Editor de texto enriquecido para CMS. |
| **Composición API** | Estilo de Vue 3 basado en funciones (`<script setup>`). |
| **DAFP** | Departamento Administrativo de la Función Pública — owner del SUIT. |
| **DANE** | Departamento Administrativo Nacional de Estadística. |
| **DNP** | Departamento Nacional de Planeación — owner del SUCOP. |
| **DoD** | Definition of Done — criterios de terminado. |
| **DoR** | Definition of Ready — criterios de listo. |
| **FNBC / BCNF** | Forma Normal de Boyce-Codd. |
| **GEL** | Estrategia de Gobierno en Línea (denominación histórica de Gobierno Digital). |
| **GOV.CO** | Portal Único del Estado Colombiano (proxy MinTIC). |
| **JSON:API** | Especificación de formato para APIs JSON (referencia conceptual; este proyecto usa **sobre plano propio** declarado en OpenAPI). |
| **ITA** | Índice de Transparencia y Acceso a la Información Pública — autodiagnóstico de MinTIC. |
| **MoSCoW** | Must / Should / Could / Won't — técnica de priorización. |
| **MinTIC** | Ministerio de Tecnologías de la Información y las Comunicaciones. |
| **OpenAPI** | Especificación estándar para describir APIs REST. |
| **PETI** | Plan Estratégico de Tecnologías de la Información. |
| **Pinia** | Librería de gestión de estado para Vue 3. |
| **PQRSD** | Peticiones, Quejas, Reclamos, Sugerencias y Denuncias. |
| **RF / RNF** | Requisito Funcional / Requisito No Funcional. |
| **RN** | Regla de Negocio. |
| **SCD** | Servicios Ciudadanos Digitales (autenticación, X-Road, Carpeta Ciudadana). |
| **SECOP** | Sistema Electrónico de Contratación Pública. |
| **SIGEP** | Sistema de Información y Gestión del Empleo Público. |
| **SLA** | Service Level Agreement. |
| **SRS** | Software Requirements Specification. |
| **SUIN** | Sistema Único de Información Normativa del Ministerio de Justicia. |
| **SUIT** | Sistema Único de Información de Trámites. |
| **TRD** | Tablas de Retención Documental. |
| **WCAG** | Web Content Accessibility Guidelines. |
| **X-Road** | Plataforma de interoperabilidad del Estado colombiano (gestionada por la AND). |

---

## 4. Estructura del árbol de entregables

```
sede-electronica-doc/
├── 00-README.md                                   ← Este archivo (índice general)
├── 01-analisis-bibliografico/
│   ├── README.md
│   ├── marco-normativo.md
│   ├── estandares-ingenieria.md
│   ├── arquitectura-apis.md
│   ├── stack-tecnologico.md
│   ├── normalizacion-bd.md
│   └── casos-referencia.md
├── 02-requisitos/
│   ├── README.md
│   ├── requisitos-funcionales.md
│   ├── requisitos-no-funcionales.md
│   ├── restricciones.md
│   └── criterios-aceptacion.md
├── 03-propuesta/
│   ├── README.md
│   ├── vision-arquitectonica.md
│   ├── diagramas-c4.md
│   ├── adr.md
│   └── estructura-directorios.md
├── 04-diseno-bd/
│   ├── README.md
│   ├── modelo-er.md
│   ├── normalizacion.md
│   ├── diccionario-datos.md
│   ├── indices-y-rendimiento.md
│   └── migraciones-seeders.md
├── 05-especificaciones-api/
│   ├── README.md
│   ├── openapi-extracto.yaml
│   ├── endpoints-estructura-identidad.md
│   ├── endpoints-transparencia.md
│   ├── envelope-y-recursos.md
│   └── errores-y-codigos.md
├── 06-especificaciones-frontend/
│   ├── README.md
│   ├── tipos-typescript.md
│   ├── composables-y-api-client.md
│   ├── componentes-vue.md
│   ├── paginas-y-rutas.md
│   └── accesibilidad-wcag.md
├── 07-plan-tareas/
│   ├── README.md
│   ├── sprints.md
│   ├── backlog.md
│   └── dor-dod.md
├── 08-trazabilidad/
│   ├── README.md
│   ├── matriz-requisitos-tareas.md
│   └── matriz-requisitos-tests.md
└── 09-riesgos/
    ├── README.md
    ├── registro-riesgos.md
    ├── supuestos.md
    └── dependencias.md
```

---

## 5. Convenciones de este entregable

### 5.1 Numeración trazable

| Prefijo | Significado |
|---|---|
| `RF-01-XXX` / `RF-02-XXX` | Requisito funcional del módulo 01 o 02 |
| `RNF-XX-XXX` | Requisito no funcional |
| `RN-XX-XXX` | Regla de negocio |
| `ADR-XXX` | Architecture Decision Record |
| `T-XXX` | Tarea del backlog |
| `PT-XXX` | Caso de prueba |
| `R-XXX` | Riesgo |
| `S-XXX` | Supuesto |

### 5.2 Formato de diagramas

- Todos los diagramas son **Mermaid** (válidos para renderizar en GitHub, GitLab, VS Code, Redocly, etc.).
- Diagramas C4 cuando aplique (modelo-contexto, modelo-contenedores).

### 5.3 Formato de código

- Bloques con `lang` declarado: ```php, ```ts, ```vue, ```sql, ```json, ```yaml, ```gherkin, ```bash.
- PHP con `declare(strict_types=1)` y tipado fuerte (convención del proyecto — ver `backend/AGENTS.md`).
- TypeScript con `strict: true` y tipado explícito; sin `any`.

### 5.4 Estilo redaccional

- Español técnico, voz activa, presente.
- Sin ambigüedades léxicas ("podría", "quizás" están prohibidos).
- Glosario cubre todo término técnico.
- Citas en formato IEEE.

---

## 6. Decisiones arquitectónicas de alto nivel (resumen)

| Decisión | Detalle | ADR |
|---|---|---|
| Backend 100% API, sin Blade, sin Inertia | Laravel expone exclusivamente JSON | ADR-001 |
| Sobre plano `{success, message, data, errors}` | **No** JSON:API estándar — convención del proyecto | ADR-002 |
| PATCH para actualizaciones | Nunca PUT | ADR-003 |
| PostgreSQL 15+ | Esquema único integrado | ADR-004 |
| Frontend Nuxt 4 + TypeScript | Composition API + `<script setup lang="ts">` | ADR-005 |
| Pinia para estado | Sin Vuex | ADR-006 |
| Sanctum para sesión | Cookie HttpOnly + CSRF | ADR-007 |
| Permisos con Spatie | `spatie/laravel-permission` ya en `composer.json` | ADR-008 |
| Versionado de URL | `/api/v1/...` | ADR-009 |
| Forma normal objetivo | **BCNF** con **4FN** para MVD detectadas; **5FN no aplica** (0 JD genuinas) | ADR-010 |
| Pseudolocalización diferida | Castellano únicamente | ADR-011 |
| Generador de PDF | `barryvdh/laravel-dompdf` | ADR-012 |

---

## 7. Estado del proyecto (avance verificado)

- ✅ **Backend Laravel 13** instalado con Sanctum, Spatie, DomPDF, MediaLibrary, ActivityLog.
- ✅ **Frontend Nuxt 4** con TypeScript estricto, `@axe-core/playwright`, Vitest.
- ✅ **Contrato OpenAPI 3.1** declarado en `contract/openapi.yaml` con `GET /api/v1/entidad` y `GET /api/v1/tramites` implementados.
- ✅ **Diseño BD** consolidado (141 tablas BCNF) en `sede-electronica-doc/_bd/`.
- ✅ **Elicitación previa** completa (12 módulos + 4 grupos `_global`) con deltas profundos.
- 🟡 **Módulo 01 estructura-identidad**: RF + RNF + RN especificados, pendiente DDL e implementación.
- 🟡 **Módulo 02 transparencia**: RF + RNF + RN especificados, pendiente DDL e implementación.

---

## 8. Cumplimiento de la instrucción

| Criterio de aceptación | Sección | Estado |
|---|---|---|
| Cubre los 3 módulos (01, 02, _bd) | §1, §7 | ✅ |
| Backend 100% API, frontend 100% desacoplado | ADR-001 | ✅ |
| Laravel 13 con sobre plano documentado y ejemplificado | `05-especificaciones-api/envelope-y-recursos.md` | ✅ |
| Vue 3 + TS con `<script setup lang="ts">` en ejemplos | `06-especificaciones-frontend/componentes-vue.md` | ✅ |
| BD normalizada a BCNF/4FN con justificación tabla por tabla | `04-diseno-bd/normalizacion.md` | ✅ |
| Trazabilidad completa requisito → tarea | `08-trazabilidad/` | ✅ |
| Alineado con Ley 1712/2014 y Res. 1519/2020 | `01-analisis-bibliografico/marco-normativo.md` | ✅ |
| Diagramas Mermaid válidos y renderizables | `03-propuesta/diagramas-c4.md`, `04-diseno-bd/modelo-er.md` | ✅ |
| Sin ambigüedades ni placeholders vacíos | Glosario y plantillas completas | ✅ |
| Bibliografía formateada (IEEE) | `01-analisis-bibliografico/casos-referencia.md` (bibliografía) | ✅ |

---

## 9. Próximos pasos

1. **Aprobación del SRS** por el oficial de cumplimiento y la Dirección TIC.
2. **Revisión de ADRs** por arquitectura y seguridad.
3. **Generación de migraciones Laravel** a partir del `04-diseno-bd/diccionario-datos.md`.
4. **Generación de tipos TypeScript** del frontend a partir del OpenAPI (`openapi-typescript`).
5. **Inicio del Sprint 1** según `07-plan-tareas/sprints.md`.

---

## 10. Contacto y mantenimiento

- **Owner del documento:** Equipo de la Sede Electrónica — Dirección TIC.
- **Versionado:** este entregable se versiona en el repositorio junto al código.
- **Cambios:** toda modificación sustancial requiere ADR y entrada en `08-trazabilidad/`.
