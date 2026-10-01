# Stakeholders y Entrevistas — Delta de la segunda pasada profunda (`jose-stakeholders`)

> **Proyecto:** Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Fecha:** 2026-06-04
> **Encuadre:** verificación adversarial de los 40 stakeholders + Mendelow + E-01..E-10 ya producidos, contra los 4 deltas profundos (`rf-rnf`, `rn`, `uc`, `hu`) y `contexto-transversal.md` §8/§11.3. Convenciones del artefacto base preservadas: Tipo I/E/R/P/C/S · `HECHO`/`[INFERIDO]` · cuadrante Mendelow. IDs nuevos con sufijo `-D` (`ST-NN-D`).
> **Estado:** DELTA pendiente de integración a `stakeholders-y-entrevistas.md` (puntos de inserción en §10).
> **Procedencia:** el corpus `/tmp/elicit` fue eliminado; los ítems del delta trazan a RF/RN/UC/HU `-D` y a `contexto-transversal.md` §8 (preguntas abiertas P1..P19).

> **Hallazgo de fondo:** el artefacto base de stakeholders es de **alta calidad** (no tan flojo como las HU base). Varios de los 9 roles que §11.3 declara "antes ausentes" YA existían como stakeholder, solo que **infra-caracterizados** (sin las necesidades/decisiones que los deltas hicieron visibles) y **sin guía de entrevista propia**. El residual real: (a) stakeholders genuinamente nuevos (Secretaría Jurídica, Tesorería, etc.); (b) desdoblar 2 stakeholders compuestos por SoD (Editor vs Aprobador); (c) 5 guías nuevas; (d) mapear las 19 preguntas abiertas a su entrevista (el base solo resolvía 5 → ahora 19/19).

---

## 1. Diagnóstico: cobertura de los 9 roles de §11.3 en el artefacto base

| Rol §11.3 | ¿Existe en base? | Estado | Acción del delta |
|---|---|---|---|
| Funcionario de dependencia (back-office) | **Sí** — ST-07 | Sin guía propia por ciclo de vida (E-06 cubre solo radicación/plazos) | Re-caracterizar ST-07 + ampliar E-06 |
| Administrador de atención / agenda de citas | **No** (subsumido en ST-07/ST-10) | Ausente como rol | **ST-41-D nuevo** + **E-11 nueva** |
| Editor vs Aprobador (SoD) | **Parcial** — ST-03 y ST-11 mezclados | Ausente como roles segregados | **ST-42-D (Editor)** + **ST-43-D (Aprobador)** + **E-12 nueva** |
| Oficial de seguridad / Auditor | **Sí** — ST-05 + ST-08 | E-09 no cubre IDOR/SoD/MFA/log/rate-limit | Re-caracterizar ST-05 + ampliar E-09 |
| Equipo técnico de interoperabilidad | **Sí** — ST-06 | E-05 no cubre error/cola TSA/monitoreo certificados | Ampliar E-05 |
| Representante legal de menor | **No** (NNA solo como sujeto, ST-22) | Ausente como actor con interacción | **ST-44-D nuevo** + bloque en E-04 |
| Administrador de cumplimiento ITA | **Parcial** — fundido en ST-03/ST-08 | Ausente como rol diferenciado | **ST-45-D nuevo** + bloque en E-03/E-07 |
| Oficial de Protección de Datos | **Sí** — ST-04 | E-04 no cubre retención/purga ni verificación de edad | Re-caracterizar ST-04 + ampliar E-04 |
| Secretaría Jurídica (SAP) | **No** | Ausente | **ST-46-D nuevo** + **E-13 nueva** |

---

## 2. Stakeholders NUEVOS (insertar en §1 "Tabla maestra", tras ST-40)

| ID | Actor / Rol | Tipo | Explícito / `[INFERIDO]` | Influencia | Interés | Cuadrante | RF/RN/UC/HU que lo involucran |
|----|-------------|------|--------------------------|------------|---------|-----------|--------|
| **ST-41-D** | Administrador de atención / agenda de citas | I | **[INFERIDO]** (RF-06-D01; §8 P12) | Media | Alta | Gestionar de cerca | RF-06-D01/D02/D03/D04 · RN-06-D01/D02 · UC-047 · HU-06-D01/D04 |
| **ST-42-D** | Editor de contenidos (crea/edita) — rol SoD | I | HECHO (desdobla ST-03/ST-11) | Baja | Alta | Mantener informado | RF-01-D02, RF-02-D01, RF-07-D01, RF-12-D01 · RN-12-D01, RN-09-D02 · UC-048 · HU-01-D02, HU-02-D01, HU-07-D01, HU-12-D01 |
| **ST-43-D** | Aprobador / Publicador de contenidos — rol SoD | I | HECHO (desdobla ST-03) | Media | Alta | Gestionar de cerca | RF-09-D03, RF-12-D01 · RN-09-D02, RN-12-D01 · UC-048 E1 · HU-09-D03, HU-12-D01 |
| **ST-44-D** | Representante legal de menor (titular NNA) | C | **[INFERIDO]** (RF-12-D04; Ley 1581 Art.7) | Baja | Media | Monitorear | RF-12-D04 · RN-12-D03 · UC-006 E6 · HU-12-D04 |
| **ST-45-D** | Administrador de cumplimiento ITA / transparencia | I | HECHO (rol diferenciado de ST-03/ST-08) | Media | Alta | Gestionar de cerca | RF-02-D01/D02/D03 · RN-02-D01/D02/D04 · UC-050 · HU-02-D02 |
| **ST-46-D** | Secretaría Jurídica (silencio administrativo positivo) | I | HECHO (§8 P19, RN-03-D08) | **Alta** | Alta | Gestionar de cerca | RN-03-D08 · UC-044 · HU-03-D09 |
| **ST-47-D** | Tesorería / Financiera distrital (reembolsos, recaudo) | I | HECHO (§8 P18, RN-03-D07) | Media | Media | Mantener satisfecho | RF-03-D01/D02/D07 · RN-03-D02/D03/D07 · UC-007, UC-043 E2 · HU-03-D02/D03/D08 |
| **ST-48-D** | Líder funcional de trámites / Product Owner de trámites | I | HECHO (precisa ST-17) | Media | Alta | Gestionar de cerca | RF-03-D03/D06 · RNF-03-D02 · RN-TX-D03/D04 · §8 P3/P10/P15/P18 |

> **ST-48-D vs ST-17:** el base tenía **ST-17 (PO / líder funcional)** como `[INFERIDO]`. Los deltas lo confirman y especializan como "Líder funcional de trámites" (responsable de 6 preguntas abiertas). **Decisión del orquestador:** promover ST-17 a HECHO y fusionar, o mantener ambos. Recomendación: promover y fusionar.
> **ST-44-D:** actor con interacción real (autoriza el tratamiento de datos del menor), distinto de ST-22 (NNA como sujeto). Inferido porque ninguna fuente lo nombra como persona, pero RF-12-D04 lo exige.

**Subtotal: 40 + 8 = 48 stakeholders.**

---

## 3. Re-caracterización de stakeholders existentes (ampliar ficha en §3)

**ST-04 — Oficial de Protección de Datos:** decide períodos de retención/purga por categoría (RN-TX-D03 / RNF-TX-D03; P10/P17) y valida verificación de edad de menores (RF-12-D04). Riesgo nuevo: sin política de retención se viola minimización (Ley 1581 Art.4) y TRD.

**ST-05 — Responsable de Seguridad (MSPI):** decide umbral de incidente "grave" para CSIRT ≤24h (RN-09-D07, A-06); MFA para admins CMS (RN-09-D04, C-07); autorización anti-IDOR (RF-09-D01); inmutabilidad del log (RNF-09-D01/RN-09-D05); rate-limiting (RNF-09-D02). Actor de HU-09-D05/D06/D07.

**ST-06 — Administrador X-Road / equipo interop:** decide manejo de error/timeout/OCSP vencido (RF-10-D01); cola de estampado TSA en migración (RF-10-D02); monitoreo de vencimiento de certificados (RNF-10-D01). Actor de HU-10-D01/D02/D03.

**ST-07 — Funcionarios de dependencias (back-office):** gestiona enrutamiento/traslado por competencia (RF-04-D01), prórroga (RF-04-D03), respuesta y cierre con medición (RF-04-D02/RN-04-D04), subsanación (RF-03-D05), identidad reservada (RN-04-D06) y reconocimiento de SAP (UC-044). Actor de HU-03-D07, HU-04-D01..D05, HU-04-D07.

---

## 4. Ajustes a la Matriz poder-interés (Mendelow) — §2

```
GESTIONAR DE CERCA (alto poder / alto interés) — añadir:
  ST-41-D Admin de agenda [INFERIDO] · ST-43-D Aprobador (SoD) · ST-45-D Admin cumplimiento ITA
  ST-46-D Secretaría Jurídica (alto poder: decide SAP) · ST-48-D Líder funcional de trámites
MANTENER SATISFECHO (alto poder / bajo interés) — añadir:
  ST-47-D Tesorería/Financiera
MANTENER INFORMADO (bajo poder / alto interés) — añadir:
  ST-42-D Editor (SoD)
MONITOREAR (bajo poder / bajo interés) — añadir:
  ST-44-D Representante legal de menor [INFERIDO]
```

Justificación de cuadrantes críticos:
- **ST-46-D Secretaría Jurídica → Gestionar de cerca:** decide qué trámites tienen SAP y su término; su omisión genera efecto jurídico automático de alto impacto.
- **ST-43-D Aprobador vs ST-42-D Editor:** la SoD (RN-09-D02) los separa; Aprobador tiene poder de publicación, Editor produce pero no decide.
- **ST-45-D Admin de cumplimiento ITA:** dueño de alertas de vencimiento (RF-02-D02) y del estado ITA real (cruza P2 "mock vs real").
- **ST-47-D Tesorería:** poder sobre la política de reembolso (P18) y recaudo, interés operativo medio.

---

## 5. Guías de entrevista NUEVAS

### E-11 — Administrador de atención / agenda de citas (ST-41-D)
**Objetivo:** cerrar **P12** (origen del calendario), validar agendamiento y concurrencia de cupos.
1. (**P12**) ¿El agendamiento se hace dentro de la sede o se integra con un sistema de turnos existente? ¿De dónde sale hoy el calendario de disponibilidad?
2. (RF-06-D01) ¿Qué servicios son agendables, en qué franjas y con cuántos cupos? ¿Quién define días no laborables y bloqueos?
3. (RF-06-D03) Hoy, ¿qué pasa si dos ciudadanos piden el último cupo? ¿Hay sobre-reserva?
4. (RF-06-D02) ¿Se permite reprogramar una cita o solo cancelar y volver a pedir?
5. (RF-06-D04) ¿Se mide la inasistencia? ¿Se envían recordatorios? ¿Qué % de no-show estiman?
6. Si se reducen cupos de una franja con citas reservadas, ¿qué debe pasar con las afectadas?
7. (RN-06-D03) ¿Existe la alternativa presencial/telefónica equivalente obligatoria?
8. ¿Qué información de la cita necesita el funcionario que atiende?

### E-12 — Editor y Aprobador de contenidos — SoD (ST-42-D / ST-43-D)
**Objetivo:** validar la SoD (RN-09-D02), el ciclo editorial con estados (RF-12-D01) y el bloqueo de auto-aprobación; junto con E-09 cubre C-07.
*Bloque Editor (ST-42-D):*
1. ¿Vos creás y publicás, o enviás a aprobación? ¿Hoy un editor puede publicar sin revisión?
2. (RF-12-D01) ¿Qué estados pasa un contenido? ¿Cómo te devuelven un rechazo?
3. (RF-07-D01) ¿El CMS te deja publicar un video sin subtítulos? ¿Debería bloquearlo?
4. (RF-01-D02) ¿Gestionás el menú/carrusel? ¿El sistema impide pasar de 7 ítems / 2 niveles?
5. (RNF-12-D01) ¿Te ha pasado que dos personas editen el mismo contenido y se pisen?
*Bloque Aprobador (ST-43-D):*
6. (RN-09-D02) ¿Podés aprobar contenido que vos mismo creaste? ¿Debería estar prohibido?
7. (RF-12-D01) ¿Qué validás antes de aprobar (metadatos de vigencia, fuente única)?
8. (RF-02-D01) Al reemplazar un documento publicado, ¿se conserva la versión anterior y la URL?
9. ¿Qué contenido es el que más urge publicar rápido y dónde está el cuello de botella?

### E-13 — Secretaría Jurídica (ST-46-D)
**Objetivo:** cerrar **P19** (silencio administrativo positivo), validar plazos legales, traslado y notificación CPACA.
1. (**P19**, RN-03-D08) ¿Qué trámites están sujetos a silencio administrativo positivo y con qué término? (Hallazgo web: espectáculos públicos.)
2. (UC-044) Cuando vence el término en un trámite con SAP, ¿qué efecto y qué constancia debe quedar? ¿A quién se notifica?
3. (RN-04-D01) ¿Confirmás los plazos diferenciados por tipo (general 15 / información 10 / consulta 30 / entre autoridades 10) sobre calendario hábil?
4. (RN-04-D03) ¿El traslado por competencia debe efectuarse dentro de 5 días? ¿Cómo se notifica?
5. (RN-04-D05) ¿Qué canales de notificación tienen validez legal? ¿Cuándo aplica la preferencia electrónica?
6. (RN-04-D06) ¿Cómo debe tratarse la identidad reservada del denunciante y el traslado a Procuraduría?
7. ¿Hay trámites con prórroga del término y cuál es su límite legal?

### E-14 — Equipo UX (ST-14 UX Researcher / ST-13 QA)
**Objetivo:** cerrar **P14** (umbral de tasa de éxito) y **A-07** (caracterización de grupos de valor).
1. (**P14**, RN-08-D01) Además de SUS ≥68, ¿qué tasa de éxito de tareas y tiempo en tarea declaran "cumple"?
2. (HU-08-D02) ¿Dónde se almacenan y exportan los resultados SUS para el histórico?
3. (**A-07**) ¿Hay caracterización de los grupos de valor (NNA, étnicos, discapacidad)?

### E-15 (opcional) — Tesorería / Financiera (ST-47-D)
**Objetivo:** cerrar **P18** (reembolso al desistir). Puede ir como bloque anexo a E-10.
1. (**P18**, RN-03-D07) ¿Hay reembolso cuando un trámite pagado se desiste? ¿Con qué política?
2. (RN-03-D02) ¿Cómo se concilia el pago PSE y los estados aprobado/pendiente/rechazado?

---

## 6. AMPLIACIONES a guías existentes (bloque adicional)

**E-04 (Oficial de Protección de Datos, ST-04):**
- (**P10/P17**, RN-TX-D03) ¿Política de retención y purga por categoría (borrador, cita, log de consentimiento)? ¿Cuánto se conserva un borrador a medio diligenciar?
- (RF-12-D04, RN-12-D03) ¿Cómo se implementa la verificación de edad y la autorización del representante legal (ST-44-D)?

**E-05 (Líder TI / X-Road, ST-06):**
- (RF-10-D01) Ante timeout / respuesta vacía / OCSP vencido del par X-Road, ¿qué hace el sistema? ¿Reintenta, encola, deja colgado?
- (RF-10-D02) Durante la migración TSA, ¿los mensajes pendientes de sello se encolan o se pierden?
- (RNF-10-D01) ¿Hay alerta de vencimiento de certificados ONAC/TLS/OCSP?

**E-06 (Funcionario PQRSD/trámites, ST-07):**
- (RF-04-D01, RN-04-D03) ¿Cómo trasladás por competencia una PQRSD ajena? ¿El sistema notifica al ciudadano?
- (RF-04-D03, RN-04-D02) ¿Podés prorrogar el término? ¿El sistema te avisa antes del vencimiento?
- (RF-04-D02, RN-04-D04) Al responder, ¿el sistema mide si fue dentro o fuera del término?
- (RF-03-D05) En trámites, ¿podés pedir subsanación con plazo? ¿Qué pasa si el ciudadano no responde?
- (RN-04-D06) ¿Cómo ves una denuncia con identidad reservada — el sistema te oculta al peticionario?

**E-07 (Control Interno, ST-08):**
- (RF-02-D02) Las alertas de vencimiento de publicaciones obligatorias, ¿quién las recibe? ¿Existe ya un admin de cumplimiento ITA (ST-45-D) diferenciado?

**E-09 (Seguridad/MSPI, ST-05):**
- (**P-A06**, RN-09-D07) ¿Cuál es el umbral local para incidente "grave"/"muy grave" y reporte CSIRT ≤24h?
- (RF-09-D01) ¿Hay control de autorización por recurso (anti-IDOR)?
- (RN-09-D04, C-07) ¿Se exige MFA a los administradores del CMS?
- (RNF-09-D01, RN-09-D05) ¿El log de auditoría es inmutable (append-only / hash)?
- (RNF-09-D02) ¿Hay rate-limiting en login/PQRSD/búsqueda?

**E-02 (G-CIO, ST-02):**
- (**P15/C-08**, RN-TX-D04) ¿Existe o se definirá la matriz trámite ↔ nivel de autenticación exigido?

**E-03 (Admin CMS, ST-03):**
- (**P16**, RN-TX-D05/RNF-TX-D02) ¿Qué contenidos se traducen y cómo se tratan las lenguas étnicas? ¿Fallback al castellano?

---

## 7. Mapa de las 19 preguntas abiertas → entrevista / stakeholder (CLAVE — reemplaza §5 del base)

| # | Pregunta abierta (§8) | Responsable sugerido | ¿Stakeholder existe? | Entrevista que la cubre |
|---|---|---|---|---|
| P1 | Clasificación Decreto 088 (Grupo) | Alcalde / MinTIC | Sí (ST-01/ST-26) | E-01 q2 · E-02 q1 · E-08 q1 |
| P2 | ITA mock vs real | Control Interno / Admin CMS | Sí (ST-08/ST-03/ST-45-D) | E-03 q1 · E-07 q1 |
| P3 | Nº de trámites SUIT y volumen | Líder funcional de trámites | ST-48-D nuevo | E-06 q2 |
| P4 | Contratos/SLA AND/CMS/nube | G-CIO / Contratación | Sí (ST-02/ST-36/ST-37) | E-02 q3 · E-08 q2 · E-10 q1 |
| P5 | SGDEA / PETI / Of. Relación Ciudadano | G-CIO + Of. Relación Ciudadano | Sí (ST-02/ST-10) | E-01 q5 · E-02 · E-03 |
| P6 | Enrutamiento PQRSD auto/manual | Funcionario PQRSD | Sí (ST-07) | E-06 q1 + ampliación |
| P7 | Nube Tier III / DRP-BCP probados | G-CIO / Proveedor nube | Sí (ST-02/ST-37) | E-02 q4/q7 · E-10 q2 |
| P8 | Micrositios/apps por integrar | Admin CMS | Sí (ST-03) | E-03 q4 |
| P9 | Integración PDI/CCD/Auth actual | Líder TI X-Road | Sí (ST-06) | E-05 q2 |
| **P10** | Retención de borradores de trámite | Líder trámites + Protección Datos | ST-48-D + ST-04 | E-04 (ampl.) · E-06 |
| **P11** | Estructura/unicidad del radicado | Gestión Documental | **ST-49-D nuevo** | E-06 (vía SGDEA) |
| **P12** | Agenda propia vs sistema de turnos | Oficina de Atención | **ST-41-D nuevo** | E-11 q1 |
| **P13** | Aportes Participa en sede vs SUCOP | Of. Participación + DNP/SUCOP | **ST-50-D nuevo** | bloque E (Participación) |
| **P14** | Umbral de tasa de éxito usabilidad | Equipo UX | Sí (ST-14) | E-14 |
| **P15** | Matriz trámite ↔ nivel de autenticación | G-CIO + AND | Sí (ST-02/ST-27) + ST-48-D | E-02 (ampl.) · E-08 |
| **P16** | Qué se traduce / lenguas étnicas | Comunicaciones | **ST-51-D nuevo** | E-03 (ampl.) |
| **P17** | Política de retención/purga de datos | Oficial de Protección de Datos | Sí (ST-04) | E-04 (ampl.) |
| **P18** | Política de reembolso al desistir | Tesorería + Líder trámites | **ST-47-D nuevo** + ST-48-D | E-15 |
| **P19** | Trámites con silencio admin. positivo | Secretaría Jurídica | **ST-46-D nuevo** | E-13 q1 |

**Cobertura: 19/19 preguntas abiertas asignadas con responsable + entrevista.** (P13 queda pendiente de DECISIÓN —¿dueño Oficina de Participación o redirección a SUCOP?—, no de asignación.)

---

## 8. Stakeholders adicionales para cerrar brechas P11/P13/P16

| ID | Actor / Rol | Tipo | Explícito/`[INFERIDO]` | Infl. | Interés | Cuadrante | Pregunta que cierra |
|----|-------------|------|------------------------|-------|---------|-----------|------|
| **ST-49-D** | Responsable de Gestión Documental / SGDEA | I | HECHO (§8 P11, RNF-04-D01) | Media | Media | Mantener satisfecho | **P11** |
| **ST-50-D** | Oficina de Participación Ciudadana | I | **[INFERIDO]** (§8 P13) | Media | Alta | Gestionar de cerca | **P13** |
| **ST-51-D** | Oficina de Comunicaciones | I | HECHO (de ST-11 + SAMI) | Media | Media | Mantener informado | **P16** |

---

## 9. Resumen cuantitativo

- **Stakeholders nuevos:** **11 efectivos** (ST-41-D…ST-51-D). Inferidos: 3 (ST-41-D, ST-44-D, ST-50-D).
- **Total tras el delta:** **51 stakeholders** (40 base + 11) · **10 inferidos**.
- **Stakeholders re-caracterizados:** 4 (ST-04, ST-05, ST-06, ST-07) + recomendación de promover ST-17→HECHO/fusionar con ST-48-D.
- **Guías nuevas:** 5 (E-11, E-12, E-13, E-14, E-15 opcional). **Guías ampliadas:** 6 (E-02, E-03, E-04, E-05, E-06, E-09) + nexo E-07.
- **Cobertura de las 19 preguntas abiertas:** **19/19 asignadas** (14 cubiertas por el base+ampliaciones; 5 cerradas creando stakeholder+guía en este delta). 0 sin responsable.

---

## 10. Puntos de inserción en `stakeholders-y-entrevistas.md`

- **§1 (tabla maestra):** añadir ST-41-D…ST-51-D tras ST-40; actualizar contador "40 → 51 · 10 inferidos".
- **§2 (Mendelow):** insertar los actores en sus cuadrantes (§4 de este delta).
- **§3 (fichas):** añadir fichas ST-41-D…ST-51-D + 4 re-caracterizaciones (ST-04/05/06/07).
- **§4 (guías):** añadir E-11..E-15; insertar bloques de ampliación en E-02/03/04/05/06/07/09.
- **§5 (preguntas abiertas):** reemplazar por la tabla del §7 (mapa P1–P19 → entrevista), cobertura 19/19.
- **§6 (resumen):** actualizar cifras (51 stakeholders, ~15 guías, 19/19 preguntas mapeadas).

## Decisiones pendientes para el orquestador
1. **ST-17 vs ST-48-D:** promover ST-17 a HECHO renombrándolo "Líder funcional de trámites" y fusionar (recomendado), o mantener ambos.
2. **E-15 (Tesorería):** guía plena o bloque anexo a E-10 (basta un bloque de 2 preguntas).
