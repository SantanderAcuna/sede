# Backlog Priorizado MoSCoW

> **Criterio MoSCoW:** **Must** = bloqueante para go-live; **Should** = importante pero no bloqueante; **Could** = deseable; **Won't** = fuera de alcance.

---

## 1. Backlog por prioridad MoSCoW

### MUST (45 RF + 17 RNF + 11 RN + 12 RT = 85 ítems)

#### Módulo 01 — Identidad (19 RF Must)

| ID | RF | Título | Sprint |
|---|---|---|---|
| RF-01-001 | Must | Top bar GOV.CO | S-02 |
| RF-01-002 | Must | Footer GOV.CO completo | S-02 |
| RF-01-003 | Must | Teléfonos con prefijo +57 | S-02 |
| RF-01-004 | Must | Logo Alcaldía en cabecera | S-02 |
| RF-01-005 | Must | Identidad visual Kit UI | S-02 |
| RF-01-007 | Must | Menú principal obligatorio | S-02 |
| RF-01-008 | Must | Menú responsive | S-02 |
| RF-01-009 | Must | Buscador predictivo | S-04 |
| RF-01-010 | Must | Mapa del sitio + sitemap.xml | S-08 |
| RF-01-011 | Must | Migas de pan | S-02 |
| RF-01-012 | Must | Cero vínculos rotos | S-08 |
| RF-01-013 | Must | Noticias en home | S-04 |
| RF-01-014 | Must | Carrusel accesible | S-06 |
| RF-01-015 | Must | Página 404 | S-06 |
| RF-01-016 | Must | Aviso salida externa | S-06 |
| RF-01-017 | Must | Banner de cookies | S-06 |
| RF-01-018 | Must | Publicar 5 políticas | S-02 |
| RF-01-019 | Must | Términos y condiciones | S-02 |
| RF-01-020 | Must | Política privacidad Ley 1581 | S-02 |
| RF-01-021 | Must | Componentes Kit UI | S-02..S-08 |

#### Módulo 02 — Transparencia (26 RF Must)

| ID | RF | Título | Sprint |
|---|---|---|---|
| RF-02-001 | Must | 10 subsecciones obligatorias | S-03 |
| RF-02-002 | Must | Orden cronológico inverso | S-03 |
| RF-02-003 | Must | Buscador de transparencia | S-04 |
| RF-02-004 | Must | Fuente única | S-03 |
| RF-02-005 | Must | Información institucional | S-03 |
| RF-02-006 | Must | Directorio SIGEP | S-04 |
| RF-02-008 | Must | Normativa completa | S-07 |
| RF-02-009 | Must | Enlace SUIN/SUCOP | S-07 |
| RF-02-010 | Must | Contratación SECOP | S-07 |
| RF-02-011 | Must | Plan de Acción antes 31-ene | S-07 |
| RF-02-012 | Must | Informe de gestión anual | S-07 |
| RF-02-013 | Must | Informes trimestrales PQRSD | S-07 |
| RF-02-014 | Must | Control interno semestral | S-07 |
| RF-02-015 | Must | Información tributaria | S-07 |
| RF-02-016 | Must | Calendario tributario | S-07 |
| RF-02-017 | Must | Datos abiertos | S-07 |
| RF-02-018 | Must | Trámites SUIT | S-07 |
| RF-02-019 | Must | Participa | S-07 |
| RF-02-020 | Must | Reporte específico | S-07 |
| RF-02-021 | Must | CRUD + versionado | S-05 |
| RF-02-024 | Must | Hash SHA-256 publicado | S-05 |
| RF-02-025 | Must | Formatos abiertos ≥90% | S-03 |
| RF-02-026 | Must | Lenguaje claro (Fernández-Huerta) | S-03 |
| RF-02-027 | Must | Búsqueda full-text | S-04 |
| RF-02-028 | Must | Filtros subsección/año | S-03 |
| RF-02-029 | Must | Tablero ITA interno | S-07 |
| RF-02-030 | Must | Metadatos Dublin Core | S-05 |

#### RNF Must (17)

| ID | Título | Sprint |
|---|---|---|
| RNF-REND-01 | TTFB p95 ≤ 200 ms | S-08 |
| RNF-REND-02 | LCP p75 ≤ 2.5 s | S-08 |
| RNF-REND-03 | INP p75 ≤ 200 ms | S-08 |
| RNF-CAP-01 | Throughput ≥1000 VU | S-08 |
| RNF-CAP-02 | Storage ≥500 GB | S-08 |
| RNF-DISP-01 | SLA ≥99.5% | S-08 |
| RNF-DISP-02 | RTO 4h, RPO 1h | S-08 |
| RNF-SEG-01 | HTTPS + cabeceras | S-08 |
| RNF-SEG-02 | MFA + bloqueo | S-01..S-08 |
| RNF-SEG-03 | Rate limit + DDoS | S-01..S-08 |
| RNF-USAB-01 | SUS ≥ 80 | S-08 |
| RNF-USAB-02 | Ancho línea 60-80 char | S-02..S-08 |
| RNF-ACES-01 | WCAG 2.1 AA Lighthouse ≥90 | S-08 |
| RNF-ACES-02 | Barra accesibilidad | S-06 |
| RNF-MANT-01 | Tests ≥80% / ≥70% | S-08 |
| RNF-PORT-01 | Compatibilidad navegadores | S-08 |
| RNF-PD-01 | Ley 1581/2012 | S-01..S-08 |

### SHOULD (5 RF + 1 RNF = 6 ítems)

| ID | RF/RNF | Título | Sprint |
|---|---|---|---|
| RF-01-006 | Should | Botón volver arriba | S-06 |
| RF-01-022 | Should | Plan integración GOV.CO | S-08 |
| RF-02-007 | Should | Grupos de interés | S-04 |
| RF-02-022 | Should | Alertas vencimiento | S-07 |
| RF-02-023 | Should | Manejo caída integraciones | S-08 |
| RNF-PORT-02 | Should | Diseño responsive | S-02..S-08 |

### COULD (2 ítems)

| ID | RF | Título | Sprint |
|---|---|---|---|
| RF-01-D01 | Could | Botón idioma persistente (diferido) | — (futuro) |

### WON'T (fuera de alcance)

| Tema | Por qué |
|---|---|
| App móvil nativa | El sitio es responsive; app nativa es una decisión posterior |
| Integración pasarela de pagos | Módulo separado (03-servicios-tramites) |
| Sistema de notificaciones push | Diferido a fase 2 |
| Multi-idioma (inglés) | Diferido (decisión #16) |
| Versionado v2 de la API | Se mantiene v1 durante todo el proyecto |

---

## 2. Backlog detallado por épica

### ÉPICA E-01: Identidad institucional (RF-01-*)

**Story points:** 90
**Sprints:** S-02, S-04, S-06
**RF cubiertos:** 22 (19 Must + 2 Should + 1 Could)

Tareas resumidas:
- T-BE-07..11: backend identidad
- T-BD-04..06: BD identidad
- T-FE-04..09: frontend identidad
- T-FE-31..34, T-FE-38..39: accesibilidad
- T-FE-28..30, T-FE-33..37: home y noticias
- T-QA-02..03: tests

### ÉPICA E-02: Transparencia Ley 1712 (RF-02-*)

**Story points:** 240
**Sprints:** S-03, S-04, S-05, S-07
**RF cubiertos:** 30 (26 Must + 3 Should + 1 D0X)

Tareas resumidas:
- T-BD-07..10, T-BD-13..16: BD transparencia
- T-BE-12..37: backend transparencia
- T-FE-10..27, T-FE-40..45: frontend transparencia
- T-QA-04..07, T-QA-10: tests

### ÉPICA E-03: Operación y plataforma (RNF-*)

**Story points:** 110
**Sprints:** S-01, S-08
**Cobertura:** RNF-REND, RNF-CAP, RNF-DISP, RNF-SEG, RNF-USAB, RNF-MANT, RNF-PORT, RNF-PD

Tareas resumidas:
- T-INF-01..07: infra y DevOps
- T-BE-38..40: backend hardening
- T-FE-46..48: frontend optimization
- T-QA-11..14: QA final

### ÉPICA E-04: Accesibilidad WCAG (RNF-ACES-*)

**Story points:** 45
**Sprints:** S-06, S-08
**Cobertura:** RNF-ACES-01, RNF-ACES-02

Tareas resumidas:
- T-FE-31, T-FE-32: barra accesibilidad
- T-FE-38, T-FE-39: skip link, contraste
- T-QA-08, T-QA-09, T-QA-11: auditoría WCAG

### ÉPICA E-05: ITA y cumplimiento (RF-02-029)

**Story points:** 35
**Sprints:** S-07
**Cobertura:** RF-02-029, RF-02-022, RNF-PD-01

Tareas resumidas:
- T-BD-14..16: BD ITA
- T-BE-30..32: backend ITA + alertas
- T-FE-40..42: frontend ITA
- T-QA-14: auditoría ITA

---

## 3. Estimación por equipo

| Equipo | Puntos | % |
|---|---|---|
| Backend Laravel | 252 | 39% |
| Frontend sitio | 168 | 26% |
| Frontend panel | 116 | 18% |
| BD | 56 | 9% |
| DevOps | 28 | 4% |
| QA | 20 | 3% |
| **Total** | **640** | **100%** |

**Distribución por persona** (asumiendo 100% de capacidad):
- Backend (4 devs): 63 pts/persona = ~8 pts/sprint/persona
- Frontend (3 devs): 95 pts/persona = ~12 pts/sprint/persona
- DevOps (1 persona): 28 pts/sprint × 8 = ~3.5 pts/sprint/persona
- QA (1 persona): 20 pts / 8 sprints = 2.5 pts/sprint/persona

**Validación:** 4 devs × 8 sprints × 8 pts = 256 ≈ 252 ✅
