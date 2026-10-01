# Stakeholders y Guías de Entrevista — Sede Electrónica Alcaldía Distrital de Santa Marta

> Fases 3 y 4 de la elicitación. Marco: Política de Gobierno Digital de Colombia / Portal Único GOV.CO · Alcaldía Distrital de Santa Marta (NIT 891.780.009-4).
> **Convenciones:** `[HECHO]` = el actor figura explícitamente en una fuente del corpus · `[INFERIDO]` = derivado lógicamente, requiere validación · `(#NN)` = documento fuente.
> **Cierra:** restricción 4 (explícito vs. inferido), restricción 29 (perfil completo) y restricción 30 (entrevistas). Matriz: poder-interés de Mendelow.
> Ver `../README.md` §6-7, `contexto-transversal.md` §8 (preguntas abiertas), `casos-uso-detallados.md`.

---

## 1. Tabla maestra de stakeholders

Tipos: **I** interno · **E** externo/aliado · **R** regulador/rector · **P** proveedor · **C** ciudadano-usuario · **S** sistema/entidad de integración.

| ID | Actor / Rol | Tipo | Explícito / `[INFERIDO]` | Influencia | Interés | Cuadrante | Fuente |
|----|-------------|------|--------------------------|------------|---------|-----------|--------|
| ST-01 | Alcalde / Representante legal | I | HECHO | Alta | Alta | Gestionar de cerca | #165 |
| ST-02 | G-CIO / Director de TIC | I | HECHO | Alta | Alta | Gestionar de cerca | #165,#258,#119 |
| ST-03 | Administrador del CMS / sede (Maritza R.) | I | HECHO | Media | Alta | Gestionar de cerca | #239,#70,#144 |
| ST-04 | Oficial de Protección de Datos | I | HECHO | Media | Alta | Gestionar de cerca | #143,#122 |
| ST-05 | Responsable de Seguridad de la Información (MSPI) | I | HECHO | Media | Alta | Gestionar de cerca | #165 |
| ST-06 | Administrador X-Road / equipo TI interop | I | HECHO | Media | Alta | Gestionar de cerca | #126,#140,#203 |
| ST-07 | Funcionarios de dependencias (PQRSD/trámites) | I | HECHO | Media | Alta | Gestionar de cerca | #239,#131,#225 |
| ST-08 | Oficina / Jefe de Control Interno | I | HECHO | Media | Media | Mantener satisfecho | #165,#141,#172 |
| ST-09 | Comité Institucional de Gestión y Desempeño | I | HECHO | Alta | Media | Mantener satisfecho | #165 |
| ST-10 | Oficina de Relación con el Ciudadano (**Atención al Ciudadano**) | I | **HECHO** | Media | Alta | Gestionar de cerca | #23 (Art.17 Ley 2052/2020) · `/dependencias` 2026-06-05 |
| ST-11 | Equipo UX/UI · arq. información · UX Writer · visual · periodista · SEO | I | HECHO | Baja | Alta | Mantener informado | #60,#170,#118 |
| ST-12 | Ingeniero de accesibilidad | I | HECHO | Baja | Alta | Mantener informado | #60,#153,#170 |
| ST-13 | Analista QA | I | HECHO | Baja | Alta | Mantener informado | #60,#170 |
| ST-14 | UX Researcher / Investigador de usuario | I | HECHO | Baja | Media | Mantener informado | #60,#182 |
| ST-15 | Desarrollador front-end / back-end | I | HECHO | Baja | Alta | Mantener informado | #170,#124 |
| ST-16 | Arquitecto de solución / DevOps | I | **INFERIDO** | Media | Alta | Gestionar de cerca | #217,#156 |
| ST-17 | Líder funcional de trámites / Product Owner | I | HECHO | Media | Alta | Gestionar de cerca | #59 · RF-03-D03/D06 · RNF-03-D02 · RN-TX-D03/D04 |
| ST-18 | Mesa de ayuda / soporte al ciudadano | I | **INFERIDO** | Baja | Media | Monitorear | #182 |
| ST-19 | Ciudadano persona natural (identificado/anónimo) | C | HECHO | Baja | Alta | Mantener informado | #122,#132,#239 |
| ST-20 | Persona con discapacidad (visual/auditiva/motriz/cognitiva/fotosensible) | C | HECHO | Baja | Alta | Mantener informado | #17,#76,#200 |
| ST-21 | Adultos mayores / baja alfabetización / baja conectividad | C | HECHO | Baja | Media | Mantener informado | #76,#183,#258 |
| ST-22 | Grupos de interés (NNA, mujeres, étnicas, LGBTIQ+) | C | HECHO | Baja | Media | Mantener informado | #239,#122 |
| ST-23 | Turistas nacionales y extranjeros | C | **INFERIDO** | Baja | Media | Monitorear | #150,#195 |
| ST-24 | Persona jurídica (NIT) | C | HECHO | Baja | Media | Mantener informado | #14,#118,#124 |
| ST-25 | Abogado / apoderado (trámites jurisdiccionales) | C | HECHO | Baja | Media | Mantener informado | #131 |
| ST-26 | MinTIC / Dirección de Gobierno Digital | R | HECHO | Alta | Alta | Gestionar de cerca | #241,#209,#128 |
| ST-27 | AND — Agencia Nacional Digital (Articulador SCD) | R/E | HECHO | Alta | Alta | Gestionar de cerca | #124,#143,#147,#108 |
| ST-28 | DAFP — Función Pública (SUIT/SIGEP) | R | HECHO | Media | Media | Mantener satisfecho | #141,#172,#183 |
| ST-29 | AGN — Archivo General de la Nación | R | HECHO | Media | Media | Mantener satisfecho | #241,#131 |
| ST-30 | SIC — Superintendencia de Industria y Comercio | R | HECHO | Alta | Media | Mantener satisfecho | #122 |
| ST-31 | CSIRT-Gobierno / ColCERT | R | HECHO | Media | Media | Mantener satisfecho | #201 |
| ST-32 | Superintendencia Financiera | R | HECHO | Media | Baja | Monitorear | #209,#125 |
| ST-33 | Registraduría Nacional (ANI/SIRC/ABIS) | S/R | HECHO | Media | Media | Mantener satisfecho | #119,#165 |
| ST-34 | GSE — Gestión de Seguridad Electrónica (TSA/TSU 01) | P/S | HECHO | Media | Baja | Monitorear | #140 |
| ST-35 | CA acreditada ante ONAC | P/S | HECHO | Baja | Baja | Monitorear | #124,#119 |
| ST-36 | Proveedor del SM CMS | P | HECHO | Alta | Media | Mantener satisfecho | #239 |
| ST-37 | Proveedor de nube / hosting (Encargado) | P | HECHO (rol) / INFERIDO (identidad) | Alta | Media | Mantener satisfecho | #122,#156,#251 |
| ST-38 | Procuraduría General de la Nación | S/R | HECHO | Baja | Baja | Monitorear | #239 |
| ST-39 | INCI — Instituto Nacional para Ciegos | E | HECHO | Baja | Baja | Monitorear | #200 |
| ST-40 | Sistemas del Estado integrados (SUIT, SECOP, SIGEP, datos.gov.co, SGDEA, SUIN, SUCOP, KOGUI, RUNT/RUAF/RUT, CCD, SAMI) | S | HECHO | Variable | n/a | Monitorear | contexto §2 |
| ST-41-D | Administrador de atención / agenda de citas | I | **[INFERIDO]** | Media | Alta | Gestionar de cerca | RF-06-D01/D02/D03/D04 · RN-06-D01/D02 · UC-047 · HU-06-D01/D04 |
| ST-42-D | Editor de contenidos (crea/edita) — rol SoD | I | HECHO | Baja | Alta | Mantener informado | RF-01-D02, RF-02-D01, RF-07-D01, RF-12-D01 · RN-12-D01, RN-09-D02 · UC-048 · HU-01-D02, HU-02-D01, HU-07-D01, HU-12-D01 |
| ST-43-D | Aprobador / Publicador de contenidos — rol SoD | I | HECHO | Media | Alta | Gestionar de cerca | RF-09-D03, RF-12-D01 · RN-09-D02, RN-12-D01 · UC-048 E1 · HU-09-D03, HU-12-D01 |
| ST-44-D | Representante legal de menor (titular NNA) | C | **[INFERIDO]** | Baja | Media | Monitorear | RF-12-D04 · RN-12-D03 · UC-006 E6 · HU-12-D04 |
| ST-45-D | Administrador de cumplimiento ITA / transparencia | I | HECHO | Media | Alta | Gestionar de cerca | RF-02-D01/D02/D03 · RN-02-D01/D02/D04 · UC-050 · HU-02-D02 |
| ST-46-D | Secretaría Jurídica (silencio administrativo positivo) | I | HECHO | Alta | Alta | Gestionar de cerca | RN-03-D08 · UC-044 · HU-03-D09 |
| ST-47-D | Tesorería / Financiera distrital (reembolsos, recaudo) | I | HECHO | Media | Media | Mantener satisfecho | RF-03-D01/D02/D07 · RN-03-D02/D03/D07 · UC-007, UC-043 E2 · HU-03-D02/D03/D08 |
| ST-49-D | Responsable de Gestión Documental / SGDEA | I | HECHO | Media | Media | Mantener satisfecho | RNF-04-D01 · §8 P11 |
| ST-50-D | Oficina de Participación Ciudadana | I | **[INFERIDO]** | Media | Alta | Gestionar de cerca | §8 P13 |
| ST-51-D | Oficina de Comunicaciones | I | HECHO | Media | Media | Mantener informado | de ST-11 + SAMI · §8 P16 |

**50 stakeholders** · **7 inferidos** (ST-16, ST-18, ST-23, ST-41-D, ST-44-D, ST-50-D, identidad de ST-37). *(ST-10 confirmado [HECHO] el 2026-06-05.)*

---

## 2. Matriz poder-interés (Mendelow)

```
        ALTO PODER
  Mantener satisfecho            │   Gestionar de cerca
  ───────────────────           │   ──────────────────
  ST-08 Control Interno         │   ST-01 Alcalde
  ST-09 Comité Gestión          │   ST-02 G-CIO
  ST-28 DAFP                    │   ST-03 Admin CMS (Maritza R.)
  ST-29 AGN                     │   ST-04 Oficial de Datos
  ST-30 SIC                     │   ST-05 Resp. Seguridad MSPI
  ST-31 CSIRT/ColCERT           │   ST-06 Líder TI X-Road
  ST-33 Registraduría           │   ST-07 Func. PQRSD/trámites
  ST-36 Proveedor SM CMS        │   ST-10 Of. Relación Ciudadano [HECHO]
  ST-37 Proveedor nube          │   ST-16 Arquitecto/DevOps [INFERIDO]
  ST-47-D Tesorería/Financiera  │   ST-17 Líder funcional trámites/PO
  ST-49-D Gestión Documental    │   ST-26 MinTIC · ST-27 AND
                                │   ST-41-D Admin agenda [INFERIDO]
                                │   ST-43-D Aprobador (SoD)
                                │   ST-45-D Admin cumplimiento ITA
                                │   ST-46-D Secretaría Jurídica
                                │   ST-50-D Of. Participación [INFERIDO]
  ─────────────────────────────────────────────────────────────  → INTERÉS
  Monitorear                    │   Mantener informado
  ──────────                    │   ──────────────────
  ST-18 Mesa de ayuda           │   ST-11 Equipo UX/UI/contenidos
  ST-23 Turistas                │   ST-12 Ing. accesibilidad
  ST-32 Superfinanciera         │   ST-13 QA · ST-14 UX Researcher
  ST-34 GSE (TSA)               │   ST-15 Dev front/back
  ST-35 CA ONAC                 │   ST-19 Ciudadano
  ST-38 Procuraduría            │   ST-20 Persona con discapacidad
  ST-39 INCI                    │   ST-21 Adultos mayores/baja conect.
  ST-40 Sistemas del Estado     │   ST-22 Grupos de interés
  ST-44-D Rep. legal menor      │   ST-24 Persona jurídica · ST-25 Abogado
        BAJO PODER              │   ST-42-D Editor (SoD)
                                │   ST-51-D Of. Comunicaciones
```

> ST-19 a ST-25 (ciudadanía): bajo poder formal pero **alto interés y criticidad de valor** — razón de ser de la sede; se gestionan vía investigación de usuario (ST-14) y la Oficina de Relación con el Ciudadano (ST-10).

---

## 3. Fichas de perfil completo

### ST-01 — Alcalde / Representante legal · HECHO (#165)
Responsable institucional de la Política de Gobierno Digital y Responsable del Tratamiento. **Intereses:** cumplimiento legal, reputación, evitar sanciones, mostrar resultados. **Influencia:** Alta (presupuesto + "voluntad política", factor de éxito #59). **Preocupaciones:** plazos vencidos (WCAG 2022, seguridad 2021), cumplimiento ITA real (línea base mock 47/100 descartada; se construye desde cero), RNBD.

### ST-02 — G-CIO / Director de TIC · HECHO (#165,#258,#119)
Lidera Gobierno Digital; reporta directo al Alcalde; responsable de la integración a GOV.CO y certificados digitales. **Intereses:** viabilidad técnica, presupuesto, metodología AND. **Influencia/Interés:** Alta/Alto. **Riesgos:** saturación de TI (#108), dependencia SM CMS sin DRP/BCP, SO X-Road obsoletos.

### ST-03 — Administrador del CMS (Maritza R.) · HECHO (#239,#70,#144)
Gestiona contenidos del SM CMS, usuarios internos, tablero ITA, gestión documental y PQRSD a nivel de sitio. **Intereses:** CMS usable, separación de funciones, trazabilidad por log. **Riesgos:** el tablero ITA anterior era "mock" (descartado 2026-06-05; el nuevo valida automáticamente desde cero), roles del SM CMS no documentados.

### ST-04 — Oficial de Protección de Datos · HECHO (#143,#122)
Gestiona ARCO, autorizaciones, RNBD, PIGDP, evaluación de impacto. **Riesgos:** no inscribir RNBD → investigación disciplinaria; nube extranjera sin declaración SIC (art. 26).

**Decisiones que ahora toma (delta):** decide períodos de retención/purga por categoría (borrador, cita, log de consentimiento) según RN-TX-D03 / RNF-TX-D03 (§8 P10/P17); valida la verificación de edad y la autorización del representante legal para datos de NNA (RF-12-D04, RN-12-D03). Riesgo nuevo: sin política de retención se viola minimización (Ley 1581 Art.4) y TRD.

### ST-05 — Responsable de Seguridad de la Información (MSPI) · HECHO (#165)
Lidera el MSPI; reporta incidentes al CSIRT ≤24 h. **Riesgos:** HPKP obsoleto, SO sin parche, sin umbral de incidente "grave" (A-06), SIEM no nombrado.

**Decisiones que ahora toma (delta):** decide umbral de incidente "grave" para CSIRT ≤24h (RN-09-D07, A-06); exige MFA para admins CMS (RN-09-D04, C-07); autoriza control anti-IDOR (RF-09-D01); define inmutabilidad del log de auditoría (RNF-09-D01/RN-09-D05); establece rate-limiting en login/PQRSD/búsqueda (RNF-09-D02). Actor de HU-09-D05/D06/D07.

### ST-06 — Administrador X-Road / equipo TI interop · HECHO (#126,#140,#203)
Despliega y opera el servidor de seguridad X-Road; migra TSA; expone servicios REST CCD. **Riesgos:** memberClass "CO" vs "GOB/PRIV", SO obsoletos, cambio TSA sin comunicar.

**Decisiones que ahora toma (delta):** decide manejo de error/timeout/OCSP vencido del par X-Road (RF-10-D01); gestiona la cola de estampado TSA durante la migración (RF-10-D02); define monitoreo de vencimiento de certificados ONAC/TLS/OCSP (RNF-10-D01). Actor de HU-10-D01/D02/D03.

### ST-07 — Funcionarios de dependencias (PQRSD/trámites) · HECHO (#239,#131,#225)
Destinatarios de PQRSD; gestionan estado de trámites, providencias, firma electrónica. **Riesgos:** enrutamiento manual no escalable (A-11), RBAC funcional no mapeado, sistemas legados.

**Decisiones que ahora toma (delta):** gestiona enrutamiento/traslado por competencia (RF-04-D01, RN-04-D03); solicita prórroga del término (RF-04-D03, RN-04-D02); responde y cierra con medición de plazo (RF-04-D02, RN-04-D04); gestiona subsanación con plazo en trámites (RF-03-D05); opera identidad reservada del denunciante (RN-04-D06) y reconoce SAP (UC-044). Actor de HU-03-D07, HU-04-D01..D05, HU-04-D07.

### ST-08 — Oficina/Jefe de Control Interno · HECHO (#165,#141,#172) — Mantener satisfecho
Auditoría; informe de control interno cada 6 meses (Decreto 2106 art.156). **Riesgo:** el tablero ITA anterior era "mock" (descartado 2026-06-05); el nuevo valida automáticamente desde cero, lo que sanea sus informes.

### ST-09 — Comité Institucional de Gestión y Desempeño · HECHO (#165) — Mantener satisfecho
Orienta la implementación de Gobierno Digital. **Riesgo:** priorización desalineada con plazos del Decreto 088.

### ST-10 — Oficina de Relación con el Ciudadano · **HECHO** (#23, Art.17 Ley 2052/2020; confirmado en `/dependencias` 2026-06-05)
Existe formalmente como **Oficina de Atención al Ciudadano** / **Atención al Ciudadano y Participación Social** (organigrama oficial; correo `atencionalciudadano@santamarta.gov.co`, radicación L-V 8:00–17:00). Lidera atención y experiencia ciudadana; asume la función del Art.17 Ley 2052/2020. *(Resta confirmar su rango directivo formal con Secretaría General / Despacho.)*

### ST-11 a ST-15 — Equipo de diseño y desarrollo · HECHO (#60,#170,#118,#124,#182,#153)
UX/UI, arquitecto de información, UX Writer, visual, periodista, SEO, ingeniero de accesibilidad, QA, UX Researcher, devs. **Mantener informado.** **Riesgos:** A-02 (debe/recomienda), sin criterios cuantitativos de usabilidad, A-WCAG, captcha vs accesibilidad, A-07 (caracterización de grupos de valor).

### ST-16 — Arquitecto de solución / DevOps · **INFERIDO** (#217,#156)
Diseña/opera la arquitectura de 13 zonas; nube/Tier III, IPv6, SIEM, BPM. **Pregunta abierta:** SIEM no nombrado; nube Tier III vs datacenter propio; DRP/BCP no probados.

### ST-17 — Líder funcional de trámites / Product Owner · HECHO (#59 · RF-03-D03/D06 · RNF-03-D02 · RN-TX-D03/D04)
Las lecciones aprendidas exigen actores funcionales/jurídicos/comunicaciones desde el inicio. Confirmado por los deltas como responsable de priorizar y gestionar la digitalización de trámites. **Responsabilidades (delta):** define el número de trámites SUIT y su volumen (§8 P3); establece la matriz trámite ↔ nivel de autenticación exigido (§8 P15, RF-03-D03/D06, RNF-03-D02); define política de retención de borradores de trámite (§8 P10, RN-TX-D03); valida la política de reembolso al desistir junto con ST-47-D (§8 P18). Actor de: RF-03-D03/D06 · RNF-03-D02 · RN-TX-D03/D04.

### ST-18 — Mesa de ayuda / soporte · **INFERIDO** (#182)
Atención personalizada (necesidad ciudadana). **Pregunta abierta:** ¿existe mesa de ayuda multicanal?

### ST-19 — Ciudadano persona natural · HECHO (#122,#132,#239)
Titular de datos (ARCO); radica PQRSD (incl. anónima), realiza trámites, paga. **Necesidades (#182):** información completa, optimización, atención personalizada, tiempos cortos, transparencia de datos. **Riesgos:** abandono de trámites, barreras de captcha, C-01.

### ST-20 — Persona con discapacidad · HECHO (#17,#76,#200)
Necesita lector de pantalla, magnificador, teclado, barra de accesibilidad, Centro de Relevo, captcha accesible. **Riesgos:** captcha como barrera, AA sin fiscalización.

### ST-21 — Adultos mayores / baja conectividad · HECHO (#76,#183,#258)
Necesitan lenguaje claro, alternativa presencial (Art.45 par.1 Ley 1753), bajo consumo de datos. **Riesgo:** brecha digital sin regulación.

### ST-22 — Grupos de interés (NNA, mujeres, étnicas, LGBTIQ+) · HECHO (#239,#122)
Micrositios diferenciados (módulo 05), verificación de edad NNA, datos sensibles. **Riesgo:** A-07; datos de NNA proscritos salvo públicos.

### ST-23 — Turistas · **INFERIDO** (#150,#195) — Monitorear. Registro Nacional de Turismo, autenticación de extranjeros (Migración Colombia).
### ST-24 — Persona jurídica (NIT) · HECHO (#14,#118,#124). Trámites empresariales, autenticación por NIT.
### ST-25 — Abogado/apoderado · HECHO (#131). Trámites jurisdiccionales; validación contra URNA; perfil diferenciado.

### ST-26 — MinTIC / Dir. Gobierno Digital · HECHO (#241,#209,#128) — Gestionar de cerca
Rector normativo; opera el proxy GOV.CO; verifica requisitos antes de aprobar la integración; gestiona CSIRT. **Riesgo:** incumplir guías impide la integración.

### ST-27 — AND (Articulador) · HECHO (#124,#143,#147,#108) — Gestionar de cerca
Provee los SCD (X-Road, OIDC, CCD) en exclusiva; anclaje de configuración; metodología de 16-17 semanas; pruebas de seguridad (semana 16). **Riesgo:** SLA/disponibilidad SCD (C-02/C-03).

### ST-41-D — Administrador de atención / agenda de citas · **[INFERIDO]** (RF-06-D01; §8 P12)
Gestiona el calendario de disponibilidad y los cupos de atención; administra la agenda de citas presenciales y virtuales de la sede. **Intereses:** sistema de agendamiento integrado, control de concurrencia de cupos, recordatorios automáticos. **Influencia:** Media. **Interés:** Alta. **Cuadrante:** Gestionar de cerca. Inferido porque RF-06-D01 exige agendamiento pero ninguna fuente nombra al responsable. **Pregunta abierta:** §8 P12 — ¿el agendamiento se hace dentro de la sede o se integra con sistema de turnos existente? Actor de: RF-06-D01/D02/D03/D04 · RN-06-D01/D02 · UC-047 · HU-06-D01/D04.

### ST-42-D — Editor de contenidos (crea/edita) — rol SoD · HECHO (desdobla ST-03/ST-11)
Rol diferenciado del Administrador CMS por separación de funciones (SoD, RN-09-D02): crea y edita contenidos pero NO puede publicar sin revisión del Aprobador. **Intereses:** flujo editorial ágil, claridad de estados, retroalimentación de rechazos. **Influencia:** Baja. **Interés:** Alta. **Cuadrante:** Mantener informado. Actor de: RF-01-D02, RF-02-D01, RF-07-D01, RF-12-D01 · RN-12-D01, RN-09-D02 · UC-048 · HU-01-D02, HU-02-D01, HU-07-D01, HU-12-D01.

### ST-43-D — Aprobador / Publicador de contenidos — rol SoD · HECHO (desdobla ST-03)
Rol con poder de publicación; revisa y aprueba (o rechaza) contenidos enviados por el Editor. Tiene poder sobre la visibilidad pública de la información institucional. **Intereses:** calidad de los contenidos, trazabilidad de aprobaciones, bloqueo de auto-aprobación. **Influencia:** Media. **Interés:** Alta. **Cuadrante:** Gestionar de cerca. Actor de: RF-09-D03, RF-12-D01 · RN-09-D02, RN-12-D01 · UC-048 E1 · HU-09-D03, HU-12-D01.

### ST-44-D — Representante legal de menor (titular NNA) · **[INFERIDO]** (RF-12-D04; Ley 1581 Art.7)
Actor con interacción real (autoriza el tratamiento de datos del menor); distinto de ST-22 (NNA como sujeto). **Intereses:** garantía de privacidad del menor, proceso de verificación de edad, autorización de representante. **Influencia:** Baja. **Interés:** Media. **Cuadrante:** Monitorear. Inferido porque ninguna fuente lo nombra como persona, pero RF-12-D04 lo exige. Actor de: RF-12-D04 · RN-12-D03 · UC-006 E6 · HU-12-D04.

### ST-45-D — Administrador de cumplimiento ITA / transparencia · HECHO (rol diferenciado de ST-03/ST-08)
Dueño de las alertas de vencimiento de publicaciones obligatorias (RF-02-D02) y de la **operación del tablero ITA interno de validación automática** (RF-B1-078/RF-12-D05; arranca en cero, sin mock — §8 P2 resuelta 2026-06-05). Rol diferenciado del Admin CMS y Control Interno por especialización en transparencia activa. **Intereses:** que el validador ITA cubra todos los criterios al publicar, alertas de vencimiento, corrección de incumplimientos marcados. **Influencia:** Media. **Interés:** Alta. **Cuadrante:** Gestionar de cerca. Actor de: RF-02-D01/D02/D03 · RN-02-D01/D02/D04 · UC-050 · HU-02-D02.

### ST-46-D — Secretaría Jurídica (silencio administrativo positivo) · HECHO (§8 P19, RN-03-D08)
Decide qué trámites tienen silencio administrativo positivo (SAP) y su término legal. Su omisión genera efectos jurídicos automáticos de alto impacto. **Intereses:** correcta configuración de SAP en la sede, plazos CPACA, validez legal de notificaciones. **Influencia:** Alta. **Interés:** Alta. **Cuadrante:** Gestionar de cerca. **Pregunta abierta:** §8 P19 — ¿qué trámites tienen SAP y con qué término? Actor de: RN-03-D08 · UC-044 · HU-03-D09.

### ST-47-D — Tesorería / Financiera distrital (reembolsos, recaudo) · HECHO (§8 P18, RN-03-D07)
Tiene poder sobre la política de reembolso al desistir (§8 P18) y sobre la conciliación del recaudo distrital. **Intereses:** integridad de la conciliación PSE, política de reembolso, estados de pago. **Influencia:** Media. **Interés:** Media. **Cuadrante:** Mantener satisfecho. Actor de: RF-03-D01/D02/D07 · RN-03-D02/D03/D07 · UC-007, UC-043 E2 · HU-03-D02/D03/D08.

### ST-49-D — Responsable de Gestión Documental / SGDEA · HECHO (§8 P11, RNF-04-D01)
Gestiona el SGDEA institucional y define la estructura/unicidad del número de radicado (§8 P11). **Intereses:** conformidad TRD, integración expediente electrónico, unicidad del radicado. **Influencia:** Media. **Interés:** Media. **Cuadrante:** Mantener satisfecho. Actor de: RNF-04-D01 · §8 P11.

### ST-50-D — Oficina de Participación Ciudadana · **[INFERIDO]** (§8 P13)
Responsable de los aportes en la sección "Participa" de la sede; relacionada con SUCOP (DNP). **Intereses:** integración con SUCOP, consultas públicas, mecanismos de participación. **Influencia:** Media. **Interés:** Alta. **Cuadrante:** Gestionar de cerca. **Pregunta abierta:** §8 P13 — ¿los aportes "Participa" se gestionan en la sede o se redirigen a SUCOP? (**Decisión pendiente:** dueño Oficina de Participación o redirección a SUCOP.) Actor de: §8 P13.

### ST-51-D — Oficina de Comunicaciones · HECHO (de ST-11 + SAMI · §8 P16)
Diferenciado de ST-11 (equipo UX/UI); responsable de comunicaciones institucionales, lenguas étnicas y SAMI. **Intereses:** definición de qué contenidos se traducen, tratamiento de lenguas étnicas, fallback al castellano. **Influencia:** Media. **Interés:** Media. **Cuadrante:** Mantener informado. **Pregunta abierta:** §8 P16 — ¿qué se traduce y cómo se tratan las lenguas étnicas? Actor de: RN-TX-D05, RNF-TX-D02 · §8 P16.

---

### ST-28 a ST-40 — Reguladores, proveedores y sistemas
- **ST-28 DAFP** (#141,#172,#183): SUIT/SIGEP, concepto previo ≤30 días, informe bianual.
- **ST-29 AGN** (#241,#131): TRD, expediente electrónico, preservación.
- **ST-30 SIC** (#122): RNBD, transferencias internacionales, poder sancionador.
- **ST-31 CSIRT/ColCERT** (#201): incidentes graves ≤24 h.
- **ST-32 Superfinanciera** (#209,#125): pasarelas de pago.
- **ST-33 Registraduría (ANI/SIRC/ABIS)** (#119,#165): identidad/biometría.
- **ST-34 GSE (TSA TSU 01)** (#140): estampado cronológico; certificados hasta 2030-01-12.
- **ST-35 CA ONAC** (#124,#119): certificados X-Road.
- **ST-36 Proveedor SM CMS** (#239): CMS propietario; **alto poder** por dependencia tecnológica sin DRP/BCP (riesgo #10).
- **ST-37 Proveedor de nube** (#122,#156): Encargado del Tratamiento; cláusulas art. 18; riesgo nube extranjera/Tier III.
- **ST-38 Procuraduría** (#239): identidad reservada en PQRSD.
- **ST-39 INCI** (#200): coautor de las directrices de accesibilidad.
- **ST-40 Sistemas del Estado** (contexto §2): SUIT, SECOP, SIGEP, datos.gov.co, SGDEA, SUIN, SUCOP, KOGUI, RUNT/RUAF/RUT, CCD, SAMI.

---

## 4. Guías de entrevista de elicitación (a medida)

> Dirigidas a **cerrar las preguntas abiertas** (contexto-transversal §8) y **validar contradicciones**. Cierres: **PA-1** clasificación Decreto 088 · **PA-2** ITA validador automático desde cero (resuelto 2026-06-05) · **PA-3** trámites/volumen · **PA-4** SLA AND/SM CMS/nube · **PA-5** SGDEA/PETI/Oficina Relación Ciudadano · **PA-6** enrutamiento PQRSD · **PA-7** nube Tier III/DRP-BCP · **PA-8** micrositios/apps · **PA-9** integración PDI/CCD/Auth.

### E-01 — Alcalde / Representante legal (ST-01)
**Objetivo:** voluntad política, presupuesto, apetito de riesgo legal, prioridades; cerrar PA-1 a nivel decisión.
1. Plazos legales vencidos (WCAG 2022, seguridad 2021, IPv6 2020): ¿cómo gestionar la exposición disciplinaria y de imagen mientras se subsana?
2. (PA-1) ¿Hay acto/comunicación de MinTIC/DAFP que confirme la clasificación de Santa Marta en el Decreto 088 (tier Avanzado/Intermedio/Básico — **Avanzado adoptado 2026-06-05**, confirmación formal pendiente)?
3. (PA-4) ¿Disponibilidad presupuestal y vigencias futuras para SM CMS, nube e implementación de SCD?
4. (ST-09) ¿El Comité Institucional de Gestión y Desempeño sesiona sobre Gobierno Digital? ¿Quién decide la priorización?
5. (PA-5) ✅ **RESUELTA (2026-06-05):** existe la **Oficina de Atención al Ciudadano** / **Atención al Ciudadano y Participación Social** (organigrama `/dependencias`); asume la función del Art.17 Ley 2052. *(Resta confirmar su rango directivo formal.)*
6. (valor) ¿Qué es "éxito" en 12 meses: cobertura ITA, nº de trámites en línea, satisfacción ciudadana?

### E-02 — G-CIO / Director de TIC (ST-02)
**Objetivo:** capacidad técnica, arquitectura, contratos, cronograma; cerrar PA-1, PA-4, PA-7, PA-9.
1. (PA-1) ¿Clasificación oficial en el Decreto 088 y desde qué fecha corre el reloj de los bloques?
2. (PA-9) ¿Punto real de integración a GOV.CO, X-Road/PDI, Autenticación y CCD? ¿Servidor X-Road en QA/Preprod/Prod?
3. (PA-4) ¿Contratos y SLA con AND, proveedor del SM CMS y nube?
4. (PA-7, C-02) Arquitectura de 13 zonas: ¿nube MRAE o datacenter propio? ¿SIEM operando? ¿Disponibilidad real 95% o 98%?
5. (riesgo #9) ¿Con cuántos trámites en paralelo opera el equipo sin saturarse (metodología ~17 semanas/trámite)?
6. (equipo) ¿Competencias internas Linux/redes/certificados/REST para X-Road, o tercerización?
7. (riesgo #10, PA-7) ¿DRP/BCP documentado y **probado** del SM CMS y la sede? ¿Último ejercicio?
8. (SO) ¿Sobre qué SO corre/correrá X-Road (Ubuntu 18.04/RHEL7 están fuera de soporte)?

**Ampliación (delta):** (**P15/C-08**, RN-TX-D04) ¿Existe o se definirá la matriz trámite ↔ nivel de autenticación exigido?

### E-03 — Administrador del CMS (Maritza R.) (ST-03)
**Objetivo:** validar dato ITA, roles del SM CMS, operación de contenidos; cerrar PA-2, PA-5 (roles), PA-8.
1. (PA-2) **Resuelto 2026-06-05: el ITA arranca en cero con validación automática (sin mock; no se usa el 47/100).** ¿Qué criterios ITA se validan al publicar cada tipo de contenido y cuáles bloquean la publicación vs. solo se marcan para corrección?
2. (roles) ¿Qué roles existen y cómo se separan crear / aprobar / publicar? ¿Un editor publica sin aprobación?
3. (auditoría) ¿El CMS registra en log todas las acciones y permite ver el historial?
4. (PA-8) ¿Qué micrositios/apps/subdominios independientes existen que deban integrarse?
5. (C-04) El formulario PQRSD, ¿está en "Atención y Servicios" o en sección propia?
6. (dolor) ¿Qué es lo más difícil/lento en el SM CMS? ¿Necesitás TI para publicar?
7. (metadatos) ¿Valida metadatos obligatorios (vigencia de norma) antes de publicar?

**Ampliación (delta):** (**P16**, RN-TX-D05/RNF-TX-D02) ¿Qué contenidos se traducen y cómo se tratan las lenguas étnicas? ¿Fallback al castellano?

### E-04 — Oficial de Protección de Datos (ST-04)
**Objetivo:** Ley 1581, RNBD, ARCO, encargados; cerrar PA-4 (nube), riesgos #12/#13.
1. (RNBD) ¿Bases de datos inscritas en el RNBD ante la SIC? ¿Última actualización?
2. (ARCO) ¿Cómo se gestionan hoy (módulo o vía PQRSD)? ¿Se cumplen 10/15 días hábiles?
3. (consentimiento) ¿Casillas no pre-marcadas y log de consentimiento (versión, fecha, IP)?
4. (PA-4, riesgo #12) ¿Proveedor de nube con cláusulas art. 18? ¿Datos en Colombia o exterior? ¿Declaración SIC (art. 26)?
5. (sensibles/NNA) ¿Consentimiento diferenciado de datos sensibles y verificación de edad NNA?
6. (PIGDP) ¿Existe el PIGDP y la evaluación de impacto para auditoría?

**Ampliación (delta):**
- (**P10/P17**, RN-TX-D03) ¿Política de retención y purga por categoría (borrador, cita, log de consentimiento)? ¿Cuánto se conserva un borrador a medio diligenciar?
- (RF-12-D04, RN-12-D03) ¿Cómo se implementa la verificación de edad y la autorización del representante legal (ST-44-D)?

### E-05 — Líder TI / Administrador X-Road (ST-06)
**Objetivo:** cerrar PA-9, validar memberClass, TSA, LCI.
1. (memberClass) ¿"CO" o "GOB/PRIV" para Santa Marta? ¿Confirmado por la AND?
2. (PA-9) ¿Qué ambientes X-Road y qué servicios REST de la CCD están expuestos?
3. (LCI) ¿Servicios certificados en nivel 3 del LCI? Si no, ¿qué falta?
4. (riesgo #4) ¿TSA apunta a GSE (`tsa.gse.com.co`), se retiró Certicámara, y la regla de firewall está activa?
5. (SO) ¿Sobre qué SO corre el servidor y su estado de soporte?
6. (seguridad) ¿Interfaz de administración (puerto 4000) y accesos internos aislados de internet?

**Ampliación (delta):**
- (RF-10-D01) Ante timeout / respuesta vacía / OCSP vencido del par X-Road, ¿qué hace el sistema? ¿Reintenta, encola, deja colgado?
- (RF-10-D02) Durante la migración TSA, ¿los mensajes pendientes de sello se encolan o se pierden?
- (RNF-10-D01) ¿Hay alerta de vencimiento de certificados ONAC/TLS/OCSP?

### E-06 — Funcionario responsable de PQRSD/trámites (ST-07)
**Objetivo:** cerrar PA-3, PA-6; validar C-01/C-04.
1. (PA-6, A-11) ¿La PQRSD se enruta **automáticamente** o se asigna **manualmente**? ¿Cuánto tarda?
2. (PA-3) ¿Trámites y PQRSD de **mayor volumen mensual**? ¿Proporción presencial vs. digital?
3. (C-01) La restricción de adjuntos (PDF/JPG/PNG ≤10 MB), ¿responde a una necesidad real o solo a una limitación técnica? ¿Qué reciben en la práctica?
4. (proceso) ¿Cómo se controla el cumplimiento de plazos? ¿El sistema alerta vencimientos?
5. (firma/expediente) ¿Firmás documentos electrónicamente? ¿Las respuestas quedan en SGDEA?
6. (permisos) ¿Ves PQRSD de otras dependencias o solo las tuyas? ¿Hay traslados?
7. (dolor) ¿Qué genera más reprocesos hoy?

**Ampliación (delta):**
- (RF-04-D01, RN-04-D03) ¿Cómo trasladás por competencia una PQRSD ajena? ¿El sistema notifica al ciudadano?
- (RF-04-D03, RN-04-D02) ¿Podés prorrogar el término? ¿El sistema te avisa antes del vencimiento?
- (RF-04-D02, RN-04-D04) Al responder, ¿el sistema mide si fue dentro o fuera del término?
- (RF-03-D05) En trámites, ¿podés pedir subsanación con plazo? ¿Qué pasa si el ciudadano no responde?
- (RN-04-D06) ¿Cómo ves una denuncia con identidad reservada — el sistema te oculta al peticionario?

### E-07 — Jefe / Oficina de Control Interno (ST-08)
**Objetivo:** veracidad ITA, evidencias, trazabilidad; refuerza PA-2.
1. (PA-2) **Decidido (2026-06-05): el ITA arranca en cero con tablero interno de validación automática (no se usa el 47/100/mock).** ¿Qué criterios ITA deben validarse al publicar cada tipo de contenido (imagen→`alt`, video→subtítulos, transparencia→metadatos, etc.)? ¿Qué criterios bloquean la publicación y cuáles solo se marcan para corrección?
2. (auditoría) ¿Qué logs/bitácoras necesitan que el sistema garantice?
3. (control) ¿Cómo aseguran la publicación del informe semestral? ¿Manual o automatizable?
4. (riesgos) ¿Riesgos críticos del proyecto que el diseño debe mitigar?
5. (FURAG) ¿Resultados FURAG recientes sobre Gobierno Digital?

**Ampliación (delta):** (RF-02-D02) Las alertas de vencimiento de publicaciones obligatorias, ¿quién las recibe? ¿Existe ya un admin de cumplimiento ITA (ST-45-D) diferenciado?

### E-08 — Enlace MinTIC / AND (ST-26 / ST-27)
**Objetivo:** cerrar PA-1, PA-4, PA-9 desde la fuente normativa; validar memberClass y disponibilidad SCD.
1. (PA-1) ¿Clasificación oficial de Santa Marta en el Decreto 088 y plazos vinculantes?
2. (PA-4, C-02/C-03) ¿SLA de disponibilidad de los SCD (99.98% o 99.982%)? ¿95% o 98% a los trámites?
3. (memberClass) ¿`memberClass` vigente para entidades territoriales?
4. (proceso) ¿Requisitos mínimos que verifica MinTIC antes de aprobar el enmascaramiento? ¿Tiempos?
5. (acompañamiento) ¿Cronograma de la metodología de 16-17 semanas para Santa Marta? ¿Pruebas de seguridad (semana 16)?
6. (LSC/LCI territorial) ¿La certificación LCI nivel 3 y la obligación de LSC aplican igual a entidades territoriales?

### E-09 — Responsable de Seguridad / MSPI (ST-05) — complementaria
**Objetivo:** cerrar riesgos #5, #6, #11, #16; validar SIEM e incidentes.
1. (SIEM, #16) ¿Existe un SIEM operando para la zona de auditoría? ¿Cuál?
2. (incidentes, A-06) ¿Cómo se define hoy "grave"/"muy grave" para el reporte ≤24 h? ¿Hay umbral?
3. (HPKP) Las guías exigen HPKP (obsoleto): ¿qué cabeceras de seguridad están implementadas?
4. (captcha) ¿El captcha actual es accesible para discapacidad visual?
5. (pentest) ¿Última prueba de vulnerabilidades/pentest y brechas abiertas?

**Ampliación (delta):**
- (**P-A06**, RN-09-D07) ¿Cuál es el umbral local para incidente "grave"/"muy grave" y reporte CSIRT ≤24h?
- (RF-09-D01) ¿Hay control de autorización por recurso (anti-IDOR)?
- (RN-09-D04, C-07) ¿Se exige MFA a los administradores del CMS?
- (RNF-09-D01, RN-09-D05) ¿El log de auditoría es inmutable (append-only / hash)?
- (RNF-09-D02) ¿Hay rate-limiting en login/PQRSD/búsqueda?

### E-10 — Proveedor del SM CMS y de nube (ST-36 / ST-37) — complementaria
**Objetivo:** cerrar PA-4, PA-7, riesgo #10.
1. (PA-4) ¿Alcance y SLA del SM CMS y la nube (disponibilidad, soporte, RTO/RPO)?
2. (PA-7, #10) ¿Existe DRP/BCP del SM CMS? ¿Último ejercicio y resultado?
3. (lock-in) ¿Capacidad de exportación/portabilidad de contenidos y datos para migrar?
4. (nube) ¿Datos en Colombia o exterior? ¿Datacenter Tier III? (insumo para ST-04/declaración SIC)
5. (cláusulas) ¿El contrato incorpora las obligaciones del art. 18 (Encargado)?

**Bloque Tesorería/Financiera (ST-47-D):**
1. (**P18**, RN-03-D07) ¿Hay reembolso cuando un trámite pagado se desiste? ¿Con qué política?
2. (RN-03-D02) ¿Cómo se concilia el pago PSE y los estados aprobado/pendiente/rechazado?

---

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

---

## 5. Preguntas abiertas — mapa P1–P19 (cobertura 19/19)

| # | Pregunta abierta (§8) | Responsable sugerido | ¿Stakeholder existe? | Entrevista que la cubre |
|---|---|---|---|---|
| P1 | Clasificación Decreto 088 (Grupo) | Alcalde / MinTIC | Sí (ST-01/ST-26) | E-01 q2 · E-02 q1 · E-08 q1 |
| P2 | ITA: validador automático desde cero (✅ resuelto 2026-06-05; sin mock) | Control Interno / Admin CMS | Sí (ST-08/ST-03/ST-45-D) | E-03 q1 · E-07 q1 (criterios a validar) |
| P3 | Nº de trámites SUIT y volumen | Líder funcional de trámites | ST-17 | E-06 q2 |
| P4 | Contratos/SLA AND/CMS/nube | G-CIO / Contratación | Sí (ST-02/ST-36/ST-37) | E-02 q3 · E-08 q2 · E-10 q1 |
| P5 | SGDEA / PETI / Of. Relación Ciudadano | G-CIO + Of. Relación Ciudadano | Sí (ST-02/ST-10) | E-01 q5 · E-02 · E-03 |
| P6 | Enrutamiento PQRSD auto/manual | Funcionario PQRSD | Sí (ST-07) | E-06 q1 + ampliación |
| P7 | Nube Tier III / DRP-BCP probados | G-CIO / Proveedor nube | Sí (ST-02/ST-37) | E-02 q4/q7 · E-10 q2 |
| P8 | Micrositios/apps por integrar | Admin CMS | Sí (ST-03) | E-03 q4 |
| P9 | Integración PDI/CCD/Auth actual | Líder TI X-Road | Sí (ST-06) | E-05 q2 |
| **P10** | Retención de borradores de trámite | Líder trámites + Protección Datos | ST-17 + ST-04 | E-04 (ampl.) · E-06 |
| **P11** | Estructura/unicidad del radicado | Gestión Documental | **ST-49-D nuevo** | E-06 (vía SGDEA) |
| **P12** | Agenda propia vs sistema de turnos | Oficina de Atención | **ST-41-D nuevo** | E-11 q1 |
| **P13** | Aportes Participa en sede vs SUCOP | Of. Participación + DNP/SUCOP | **ST-50-D nuevo** | bloque E (Participación) |
| **P14** | Umbral de tasa de éxito usabilidad | Equipo UX | Sí (ST-14) | E-14 |
| **P15** | Matriz trámite ↔ nivel de autenticación | G-CIO + AND | Sí (ST-02/ST-27) + ST-17 | E-02 (ampl.) · E-08 |
| **P16** | Qué se traduce / lenguas étnicas | Comunicaciones | **ST-51-D nuevo** | E-03 (ampl.) |
| **P17** | Política de retención/purga de datos | Oficial de Protección de Datos | Sí (ST-04) | E-04 (ampl.) |
| **P18** | Política de reembolso al desistir | Tesorería + Líder trámites | **ST-47-D nuevo** + ST-17 | E-10 (bloque Tesorería) |
| **P19** | Trámites con silencio admin. positivo | Secretaría Jurídica | **ST-46-D nuevo** | E-13 q1 |

**Cobertura: 19/19 preguntas abiertas asignadas con responsable + entrevista.** (P13 queda pendiente de DECISIÓN — ¿dueño Oficina de Participación o redirección a SUCOP? — no de asignación.)

---

## 6. Resumen

- **50 stakeholders** catalogados (7 inferidos marcados: ST-16, ST-18, ST-23, ST-41-D, ST-44-D, ST-50-D, identidad de ST-37; ST-10 confirmado [HECHO] el 2026-06-05), con tipo, influencia, interés, cuadrante poder-interés y fuente.
- **Matriz poder-interés** completa (Mendelow, 4 cuadrantes) actualizada con 10 nuevos actores del delta.
- **14 guías de entrevista** (E-01..E-14): 8 stakeholders clave + 2 complementarias (Seguridad/MSPI, proveedores) + 4 nuevas (E-11 Admin agenda, E-12 Editor/Aprobador SoD, E-13 Secretaría Jurídica, E-14 UX); guías E-02..E-07/E-09/E-10 ampliadas con bloques delta. Bloque Tesorería (ST-47-D) integrado al final de E-10.
- **Preguntas abiertas: 19/19 asignadas** con responsable + entrevista (mapa P1–P19, §5).
- Cierra restricciones de auditoría **4**, **29** y **30**.
