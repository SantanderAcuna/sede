# 07 — Plan de Tareas, Sprints y Backlog

> **Marco:** Scrum con sprints de 2 semanas.
> **Equipo:** 4 devs backend, 3 devs frontend, 1 diseñador UX/UI, 1 DevOps, 1 G-CIO.
> **Velocity objetivo:** 80 puntos historia/sprint (post-sprint 1 de calibración).
> **Total puntos:** estimación del trabajo completo para los módulos 01-estructura-identidad y 02-transparencia.
> **Trazabilidad:** cada tarea referencia el requisito origen y el test que la valida.

---

## Índice

| Archivo | Contenido |
|---|---|
| `sprints.md` | Plan de sprints (8 sprints × 2 semanas = 16 semanas = 4 meses) |
| `backlog.md` | Backlog completo priorizado MoSCoW con estimaciones |
| `dor-dod.md` | Definition of Ready y Definition of Done |
| `ceremonias.md` | Ceremonias Scrum, artefactos, roles |

---

## Resumen ejecutivo del plan

| Sprint | Fechas (estimadas) | Foco | Puntos | Hitos |
|---|---|---|---|---|
| S-01 | 2026-10-13 → 2026-10-24 | Cimientos backend + BD + auth | 78 | API `/salud` OK, login con Sanctum, BD PostgreSQL operativa |
| S-02 | 2026-10-27 → 2026-11-07 | Identidad: top bar/footer/menú + políticas | 82 | Sitio público muestra GOV.CO y políticas |
| S-03 | 2026-11-10 → 2026-11-21 | Transparencia núcleo: subsecciones + listado de documentos | 86 | 10 subsecciones servidas, listado funcional |
| S-04 | 2026-11-24 → 2026-12-05 | Búsqueda FTS + directorio + SIGEP stub | 84 | Búsqueda con tolerancia, directorio público |
| S-05 | 2026-12-08 → 2026-12-19 | CRUD documentos + hash SHA-256 + versionado | 80 | Editor del panel sube, versiona y publica |
| S-06 | 2026-12-22 → 2027-01-09 | Noticias + home + accesibilidad WCAG | 76 | Home completo, barra accesibilidad operativa |
| S-07 | 2027-01-12 → 2027-01-23 | ITA interno + alertas + auditoría + SEO | 82 | Tablero ITA funcional, alertas operativas |
| S-08 | 2027-01-26 → 2027-02-06 | Hardening + observabilidad + deploy | 72 | Go-live en producción, SLA cumplido |

**Total:** 640 puntos / 4 meses / 16 semanas / 8 sprints.

---

## Resumen por tipo de tarea

| Tipo | Cantidad | Puntos | % |
|---|---|---|---|
| Backend | 38 | 252 | 39% |
| Frontend (sitio) | 28 | 168 | 26% |
| Frontend (panel) | 18 | 116 | 18% |
| BD / migraciones | 12 | 56 | 9% |
| DevOps / infra | 8 | 28 | 4% |
| QA / accesibilidad | 10 | 20 | 3% |
| **Total** | **114** | **640** | **100%** |

---

## Mapa de RF → Sprint

### Sprint 1 — Cimientos
- RNF (todos): sentando base de seguridad, rendimiento, observabilidad.
- RF-12 (gestión contenidos): setup del CMS Laravel, RBAC, login Sanctum, MFA.

### Sprint 2 — Identidad
- RF-01-001, RF-01-002, RF-01-004, RF-01-005, RF-01-007, RF-01-018, RF-01-019, RF-01-020, RF-01-021 (parcial).

### Sprint 3 — Transparencia núcleo
- RF-02-001, RF-02-002, RF-02-004, RF-02-005 (parcial), RF-02-025, RF-02-026, RF-02-028.

### Sprint 4 — Búsqueda y directorio
- RF-01-009, RF-02-003, RF-02-006, RF-02-027, RF-02-007 (parcial).

### Sprint 5 — CRUD documentos
- RF-02-021, RF-02-022, RF-02-024, RF-02-030, RF-02-029 (parcial).

### Sprint 6 — Noticias y accesibilidad
- RF-01-013, RF-01-014, RF-01-006, RF-01-008, RF-01-011, RF-01-015, RF-01-016, RF-01-017, RNF-ACES-01, RNF-ACES-02, RF-02-008 (parcial).

### Sprint 7 — ITA, alertas, auditoría
- RF-02-008, RF-02-009, RF-02-010, RF-02-011, RF-02-012, RF-02-013, RF-02-014, RF-02-015, RF-02-016, RF-02-017, RF-02-018, RF-02-019, RF-02-020, RF-02-029, RF-02-022.

### Sprint 8 — Hardening
- RF-01-010, RF-01-012, RF-01-022, RF-02-023, RF-02-D01, RF-02-D03, RF-01-D01, RNF-REND, RNF-CAP, RNF-DISP, RNF-SEG (revisión final), RNF-PORT, RNF-PD, RNF-MANT.
