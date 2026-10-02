# 02 — Requisitos (SRS)

> Especificación de requisitos de los módulos **01-estructura-identidad** y **02-transparencia** de la Sede Electrónica del Distrito de Santa Marta.
> **Estructura:** ISO/IEC/IEEE 29148:2018 [12] + plantilla Wiegers [16].
> **Trazabilidad:** cada requisito tiene un ID único y se mapea a diseño, tarea y prueba (ver `08-trazabilidad/`).

---

## Índice

| Archivo | Contenido |
|---|---|
| `requisitos-funcionales.md` | **52 requisitos funcionales** con plantilla completa (descripción, prioridad MoSCoW, actor, flujos, criterios Gherkin, fuente normativa, trazabilidad) |
| `requisitos-no-funcionales.md` | **18 requisitos no funcionales** con umbrales medibles (rendimiento, seguridad, accesibilidad, usabilidad, mantenibilidad, portabilidad) |
| `restricciones.md` | 11 restricciones normativas (RN-01..RN-11) + 12 restricciones técnicas (RT-01..RT-12) |
| `criterios-aceptacion.md` | Especificación Gherkin (Given/When/Then) agregada para los flujos críticos |

---

## Convenciones de identificación

| Prefijo | Significado |
|---|---|
| `RF-01-NNN` | Requisito funcional del módulo 01 (estructura-identidad) |
| `RF-02-NNN` | Requisito funcional del módulo 02 (transparencia) |
| `RNF-XX-NNN` | Requisito no funcional |
| `RN-NN-NNN` | Regla de negocio |
| `HU-XX-NNN` | Historia de usuario |

Los requisitos toman como base el corpus ya elicitado en `sede-electronica-doc/01-estructura-identidad/` y `sede-electronica-doc/02-transparencia/` (entradas `RF-B1-*`, `RF-B2-*`, `RF-B3-*` con sus deltas `RF-01-D0*` / `RF-02-D0*`), consolidando y refinando la especificación al nivel de SRS formal.

---

## Resumen ejecutivo

| Categoría | Total |
|---|---|
| RF módulo 01 | 22 |
| RF módulo 02 | 30 |
| RNF | 18 |
| Restricciones | 23 |
| **Total** | **93 requisitos** |

Distribución MoSCoW:

| Prioridad | RF módulo 01 | RF módulo 02 | RNF |
|---|---|---|---|
| **Must** | 19 | 26 | 16 |
| **Should** | 2 | 3 | 2 |
| **Could** | 1 | 1 | 0 |
| **Won't** (fuera de alcance) | 0 | 0 | 0 |

---

## Arquitectura de dos clientes sobre una sola API

> **Hecho arquitectónico de primer orden:** este proyecto **NO** es un monolito renderizado por Laravel. Es una **API Laravel 13 única** consumida por **dos clientes Nuxt 4 independientes**:

| Cliente | Ruta en el repo | Alcance funcional | Endpoints del backend |
|---|---|---|---|
| **Sitio público** (`sitio`) | `/sitio/` | Endpoints **públicos de lectura** (`index`, `show`) consumidos por el ciudadano. Sin login. Sirve la sede pública: home, menú, transparencia (10 subsecciones), directorio, buscador, noticias, carrusel, políticas, búsqueda full-text. | `GET` exclusivamente; nunca `POST/PATCH/DELETE`. |
| **Panel de administración** (`panel`) | `/panel/` | Cliente Nuxt **independiente** con su propio bundle, donde editores / aprobadores / administradores / seguridad gestionan el contenido y los catálogos (crear, editar, versionar, despublicar, asignar permisos, ver logs). | `POST`, `PATCH`, `DELETE` además de `GET`. Autenticación Sanctum (cookie sesión HttpOnly + CSRF para navegador; Bearer token opcional para integraciones). |

**Implicaciones para el SRS:**

1. **Cada requisito lleva la columna `Cliente`** = `sitio` | `panel` | `ambos`. Un RF cuya implementación vive solo en el panel **no genera** un endpoint público equivalente.
2. **El frontend público (`sitio`) solo consume `GET`**; ningún endpoint de escritura se documenta ni se publica en su contrato.
3. **El frontend panel (`panel`) consume todo el contrato**, incluido el recurso protegido por Sanctum.
4. La **separación de directorios** en el repo (ver `03-propuesta/estructura-directorios.md`) refleja esta frontera: no se comparten componentes, tipos ni stores entre los dos Nuxt, salvo los **tipos TS generados del OpenAPI** (`openapi-typescript`).

> Ver diagrama C2 en `03-propuesta/diagramas-c4.md`.
