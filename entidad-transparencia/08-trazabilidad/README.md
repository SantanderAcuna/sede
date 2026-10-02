# 08 — Trazabilidad Bidireccional

> **Objetivo:** garantizar trazabilidad completa **requisito ↔ diseño ↔ tarea ↔ test**, sin elementos huérfanos.
> **Estándar:** ISO/IEC/IEEE 29148:2018 §6.5 (Requirements Traceability).
> **Cobertura objetivo:** 100% RF cubiertos, 100% RNF cubiertos, 100% RN cubiertos.

---

## Índice

| Archivo | Contenido |
|---|---|
| `matriz-requisitos-tareas.md` | Matriz RF/RNF/RN → Tarea → Endpoint/Tabla/Componente |
| `matriz-requisitos-tests.md` | Matriz RF/RNF → Test (Pest + Playwright + axe-core) |
| `cobertura.md` | Análisis de cobertura (forward y backward) |

---

## Resumen ejecutivo

| Tipo | Total | Cubiertos | % |
|---|---|---|---|
| RF módulo 01 | 22 | 22 | 100% |
| RF módulo 02 | 30 | 30 | 100% |
| RNF | 18 | 18 | 100% |
| Restricciones (RN) | 11 | 11 | 100% |
| Restricciones (RT) | 12 | 12 | 100% |
| **Total requisitos** | **93** | **93** | **100%** |

| Tipo artefacto | Total | Trazables a RF/RNF |
|---|---|---|
| Endpoints API | 48 | 48 (100%) |
| Tablas BD | 27 | 27 (100%) |
| Componentes Vue | ≥30 | ≥30 (100%) |
| Migraciones Laravel | 26 | 26 (100%) |
| ADRs | 15 | 15 (100%) |
| Tests automatizables | ≥40 | ≥40 (100%) |

---

## Convenciones de IDs

| Prefijo | Significado | Ejemplo |
|---|---|---|
| `RF-01-NNN` | RF módulo 01 | `RF-01-001` |
| `RF-02-NNN` | RF módulo 02 | `RF-02-021` |
| `RNF-XX-NNN` | RNF | `RNF-REND-01`, `RNF-ACES-01` |
| `RN-NN` | Restricción normativa | `RN-03` |
| `RT-NN` | Restricción técnica | `RT-02` |
| `T-XX-NNN` | Tarea | `T-BE-07`, `T-FE-24` |
| `PT-XX-NNN` | Test plan / caso de prueba | `PT-01-001` |
| `ADR-NNN` | Architecture Decision Record | `ADR-004` |
| `BR-NN` | Business Rule | `BR-DEP-01` |

---

## Tipos de trazabilidad

### Forward traceability (Requisitos → Implementación)
- RF/RNF → Tarea → Endpoint/Tabla/Componente → Test.
- Garantiza que cada requisito tenga al menos 1 tarea y 1 test.

### Backward traceability (Implementación → Requisitos)
- Tarea/Endpoint/Tabla → RF/RNF.
- Garantiza que no haya elementos "huérfanos" sin requisito de negocio.

### Bidireccional
- Cualquier nodo (requisito, diseño, código, test) puede navegarse en ambas direcciones.

---

## Herramientas de mantenimiento

| Herramienta | Uso |
|---|---|
| GitHub Issues | Cada RF es una Epic con sub-issues por tarea |
| Matriz en Markdown | Fuente de verdad auditable |
| Script Python | Cruzar automáticamente RF en archivos `.md` con tareas en sprints |
| CI | Verifica que todo PR referencie al menos 1 RF/RNF |
