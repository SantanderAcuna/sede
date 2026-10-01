# Elicitación — Sede Electrónica de la Alcaldía Distrital de Santa Marta

> **Proyecto:** Sede Electrónica del Distrito de Santa Marta (Magdalena, Colombia) — NIT 891.780.009-4
> **Marco:** Política de Gobierno Digital de Colombia / Portal Único del Estado GOV.CO
> **Fecha de consolidación:** 2026-06-03
> **Fuente:** corpus normativo y técnico OCR (`/tmp/elicit`) — 40+ documentos del Estado colombiano, ya extraídos en 3 catálogos de requisitos y 3 consolidaciones temáticas (Fase 2).
> **Convenciones:** `[HECHO]` = extraído literalmente del texto fuente · `[INFERENCIA]` = derivado lógicamente · `[PENDIENTE]` / `[PREGUNTA ABIERTA]` = ausente, ambiguo o contradictorio en las fuentes. El sufijo `(#NN)` referencia el documento fuente según el `manifest.json` del corpus.

---

## 1. Cómo está organizada esta carpeta

Cada **módulo funcional** de la sede electrónica tiene su propia carpeta con un documento `.md` autocontenido (requisitos RF/RNF/RN, casos de uso, historias de usuario, datos, integraciones y preguntas abiertas de ese módulo).

| # | Módulo | Carpeta | Núcleo |
|---|--------|---------|--------|
| 00 | **Visión general** | `README.md` (este archivo) | Contexto, normativa, usuarios, roles, integraciones, riesgos, trazabilidad |
| 01 | **Estructura e Identidad GOV.CO** | `01-estructura-identidad/` | Top bar, footer, menús, buscador, 404, cookies, políticas, Kit UI, integración/redireccionamiento a GOV.CO |
| 02 | **Transparencia y Acceso a la Información** | `02-transparencia/` | Las 10 subsecciones de Ley 1712, normativa, contratación, planeación, directorio, tributaria |
| 03 | **Servicios y Trámites** | `03-servicios-tramites/` | Catálogo SUIT, trámites en línea (4 etapas), autenticación, pagos, citas, carpeta ciudadana, expediente |
| 04 | **PQRSD** | `04-pqrsd/` | Formulario, anonimato, radicado, seguimiento, plazos, gestión documental |
| 05 | **Participa** | `05-participa/` | Participación ciudadana (4 fases), consultas normativas, micrositios grupos de interés |
| 06 | **Canales de Atención** | `06-canales-atencion/` | Datos de contacto, agendamiento de citas, acceso inclusivo |
| 07 | **Accesibilidad** | `07-accesibilidad/` | WCAG 2.1 AA — 52 criterios, barra de accesibilidad, multimedia, teclado |
| 08 | **Usabilidad** | `08-usabilidad/` | UX, SUS, lenguaje claro, responsive, SEO, validación W3C, código limpio |
| 09 | **Seguridad Digital** | `09-seguridad/` | HTTPS, cabeceras, captcha, MSPI, incidentes, backups, autenticación robusta |
| 10 | **Interoperabilidad** | `10-interoperabilidad/` | X-Road/PDI, SCD, TSA, SUIT/SIGEP/SECOP/SGDEA, no exigir documentos |
| 11 | **Datos Abiertos** | `11-datos-abiertos/` | datos.gov.co, formatos abiertos, metadatos, activos de información |
| 12 | **Gestión de Contenidos y Administración** | `12-gestion-contenidos/` | CMS, roles y permisos, auditoría, archivo (TRD/AGN), tablero ITA, notificaciones, firma electrónica |

> **Nota de taxonomía:** los módulos 08 (Usabilidad) y 12 (Gestión de Contenidos) se añadieron a los 10 inicialmente acordados para no perder requisitos que no encajan en los otros módulos. Algunos requisitos son **transversales** (p. ej. accesibilidad afecta a todos): se documentan en su módulo de origen y se referencian de forma cruzada.

---

## 2. Objetivos del proyecto

- **[HECHO]** Proporcionar a la ciudadanía del Distrito de Santa Marta acceso digital **unificado** a trámites, PQRSD, transparencia, datos abiertos, normatividad y participación ciudadana, cumpliendo Ley 1712/2014, Resolución MinTIC 1519/2020, Decreto 767/2022 y WCAG 2.1 AA. (#239)
- **[HECHO]** Centralizar los servicios ciudadanos digitales del Distrito en un único punto de acceso e **integrar la sede al Portal Único del Estado GOV.CO** (Art. 14-15, Decreto 2106/2019). (#239, #209, #217)
- **[HECHO]** Cumplir los estándares de Gobierno Digital exigidos por MinTIC y subsanar el déficit de cumplimiento ITA. (#239)
- **[HECHO]** Hacer funcionar al Estado como una sola entidad para el ciudadano: no pedir documentos que otra entidad ya posee (interoperabilidad X-Road). (#119, #203)
- **[HECHO]** Digitalizar y automatizar los trámites de la Alcaldía según los plazos del Decreto 088/2022. (#79, #99, #251)
- **[HECHO]** Reducir el tiempo de interacción ciudadano-gobierno (referencia OCDE: Colombia 7,4 h/trámite). (#123)
- **[HECHO]** Garantizar la protección de datos personales en todos los canales (Ley 1581/2012). (#122)

## 3. Problema de negocio

- **[HECHO]** El tablero ITA del sitio actual mostraba **218/412 ítems, 47/100**, pero ese dato era **"mock" (el HTML usa esa palabra)** → **se descarta como línea base; no se usa**. (#239)
- **[HECHO]** Fragmentación de portales, ciudadano como "mensajero" entre entidades, múltiples credenciales y riesgo de suplantación. (#124, #156, #158)
- **[HECHO]** Baja usabilidad y accesibilidad de los sitios del Estado; abandono de trámites. (#170)
- **[DECIDIDO 2026-06-05 / A-03 resuelto]** El nuevo tablero ITA es **interno (no público)**, **arranca en cero** y **valida automáticamente** el cumplimiento de cada contenido al publicarlo (imagen con `alt`, video con subtítulos, metadatos de transparencia, etc.); marca los incumplimientos para corrección manual y se actualiza solo al publicar. **No usa el 47/100 ni datos mock** (RF-B1-078 / RF-12-D05). (#239)

## 4. Contexto y AS-IS

- **[HECHO]** Santa Marta es **Distrito Turístico, Cultural e Histórico** (no solo municipio); clasificado como tier **Alcaldía-Avanzado** (mismo tier que Distrito Capital/Gobernaciones), con los plazos de digitalización **más largos** (Bloque 1: may/2028; 100%: mar/2034; 100% automatizado: abr/2037). **[DECIDIDO 2026-06-05]** se adopta Avanzado por ser Santa Marta un Distrito; la clasificación oficial por entidad no está publicada como dataset abierto en datos.gov.co (verificado), así que el oficio a MinTIC queda como formalidad de confirmación, no bloqueante. (#79, #233, #251)
- **[HECHO]** La sede actual usa un CMS propietario llamado **"SM CMS"** e integra ya: GOV.CO (top bar + enmascaramiento URL), SUIT, SECOP I/II, SIGEP, datos.gov.co, Procuraduría. (#239)
- **[HECHO]** El formulario PQRSD actual limita adjuntos a **PDF/JPG/PNG y 10 MB** → contradice la norma (ver §8 contradicción C-01). (#239)

## 5. Procesos futuros (TO-BE)

- Integración a GOV.CO por **redireccionamiento con enmascaramiento de URL** (proxy MinTIC): `https://www.gov.co/servicios-y-tramites/T{código_SUIT}`. Proceso de **7 pasos** (sede) y **17 semanas** por trámite (metodología AND). (#241, #209, #108)
- Todo trámite en **4 etapas estándar**: Inicio → Hago mi solicitud → Procesan mi solicitud → Respuesta. (#128, #130, #152, #198)
- Trámites nuevos **100% en línea** desde su creación. (#183, #251)
- **SSO/SLO** con GOV.CO vía SCD de Autenticación (OpenID Connect). (#124, #128)
- Exposición de servicios REST vía **X-Road/PDI** para la Carpeta Ciudadana. (#124, #147)
- Arquitectura de referencia de **13 zonas**: presentación/estilo, seguridad, gestión documental (SGDEA), canales de acceso, zona transaccional, CMS, datos y persistencia, analítica, auditoría (logs/correlación-SIEM), BPM, interoperabilidad interna, interoperabilidad externa (X-Road), notificaciones y alertas. (#217)

## 6. Usuarios

Ciudadano (persona natural nacional/extranjera, identificado o anónimo) · Persona jurídica (NIT) · Personas con discapacidad (visual, auditiva, motriz, cognitiva, fotosensible) · Ciudadanos con baja conectividad/alfabetización digital · Grupos de interés (NNA, mujeres, personas con discapacidad, adultos mayores, comunidades étnicas, LGBTIQ+) · Turistas (Distrito turístico) · Abogado/apoderado (trámites jurisdiccionales) · Funcionarios y administradores de la Alcaldía. (#14, #17, #122, #131, #239)

## 7. Roles y actores

| Rol | Función | Fuente |
|-----|---------|--------|
| **Alcaldía de Santa Marta** | Titular/Responsable de la sede; Responsable del Tratamiento de datos | #132, #209, Ley 1581 |
| **Ciudadano (Titular)** | Usuario final; titular de datos; derechos ARCO | #122 |
| **Administrador del sistema / CMS (Maritza R.)** | Gestiona contenidos, usuarios, seguimiento ITA | #239 |
| **G-CIO / Director de TI** | Lidera Gobierno Digital; reporta al Alcalde | Decreto 1083/2015; #165, #258 |
| **Representante legal (Alcalde)** | Responsable institucional de la política GD | #165 |
| **Oficial de Protección de Datos** | Gestiona ARCO y Ley 1581 | Decreto 620/2020; #143 |
| **MinTIC / Dir. Gobierno Digital** | Rector normativo; verifica integración; opera proxy GOV.CO | #241, #209 |
| **AND (Articulador)** | Provee SCD (Interoperabilidad, Autenticación, Carpeta); X-Road | Decreto 620/2020; #124, #143 |
| **DAFP** | Administra SUIT y SIGEP; concepto previo; lineamientos participación | #141, #172 |
| **AGN** | Lineamientos de gestión documental (TRD) | #241 |
| **SIC** | Vigilancia datos personales; RNBD | Ley 1581; #122 |
| **CSIRT-Gobierno / ColCERT** | Receptores de incidentes de seguridad | #201 |
| **Superintendencia Financiera** | Lineamientos de pasarelas de pago | #209 |
| **Registraduría (RNEC)** | Fuente de identidad (ANI, biometría) | #119, #165 |
| **GSE (TSU 01)** | Servicio TSA de estampado cronológico (reemplazó a Certicámara) | #140 |
| **Equipo UX/UI, accesibilidad, QA, dev front/back, X-Road admin** | Diseño, implementación y operación | #60, #119, #170 |

## 8. Contradicciones y ambigüedades críticas (a resolver en diseño)

| Código | Tema | Detalle | Prioridad |
|--------|------|---------|-----------|
| **C-01** | Adjuntos PQRSD | La norma prohíbe restricciones técnicas de formato/tamaño/cantidad (#241), pero la sede actual limita a PDF/JPG/PNG y 10 MB (#239). Anexo 5 (libre) vs. Anexo 5.1 ("la entidad define") también se contradicen. | **ALTA** |
| **C-02** | Disponibilidad | Sede/portal/VU ≥95% (#21, #70, #209) vs. trámites/OPA digitalizados ≥98% (#251, #217) vs. Anexo 1 ≥98%. Diferencia: ~36 h vs ~14 h de caída/mes. Resolución sugerida: aplicar ≥98% (Anexo 1 prevalente). | **ALTA** |
| **C-03** | Disponibilidad SCD | 99.98% vs 99.982% (inconsistencia interna #156). | MEDIA |
| **C-04** | Ubicación PQRSD | ¿menú "Atención y Servicios" (#225, #241) o sección propia (#239)? | ALTA |
| **C-05** | Fecha Decreto 088 | "088 DE 2021" en encabezado vs firma "24 ENE 2022". | MEDIA |
| **X-Road memberClass** | Interop | "CO" (guía 2019, #126) vs "GOB/PRIV" (guía 2020, #124). Verificar con AND. | MEDIA |
| **A-WCAG** | Accesibilidad | AA obligatorio desde 01/01/2022 pero sin entidad fiscalizadora, sanción ni umbral de errores tolerables definidos. | ALTA |
| **HPKP** | Seguridad | Las guías exigen HPKP, pero Chrome lo eliminó en 2018 → falsa seguridad. | MEDIA |
| **Captcha vs A11y** | Seguridad/Accesibilidad | Captcha obligatorio en todos los formularios vs. barrera para discapacidad visual. Requiere captcha accesible. | ALTA |
| **WCAG 2.1 vs 2.2** | Accesibilidad | Las guías citan WCAG 2.1; WCAG 2.2 es de 2023. | MEDIA |
| **SO X-Road** | Interop | Guías citan Ubuntu 18.04 / RHEL7, ya fuera de soporte. | MEDIA |
| **"Criterios" #93/#213** | Accesibilidad/Usabilidad | **RESUELTO**: los PPTX #93/#213 no son la matriz formal de criterios — embeben un GIF idéntico (md5 `9380b2ee…`) con 3 tips de usabilidad ya cubiertos. La matriz formal de criterios de MinTIC no está en el corpus (pedirla aparte). Ver `_global/auditoria-cobertura.md` §6. | ~~ALTA~~ → resuelto |

**Preguntas abiertas globales (lo que aún queda para la mesa con la Alcaldía):** confirmación formal del tier Decreto 088 ante MinTIC *(supuesto adoptado: Avanzado)* · inventario completo de micrositios/apps · formalizar **vinculación/convenio con la AND** (hoy sin contrato). *(Decididos/respondidos el 2026-06-05: ITA desde cero (validador automático), enrutamiento PQRSD híbrido, reembolso, silencio **negativo por defecto** (SAP solo como excepción por norma especial), retención, niveles de auth, 124 trámites SUIT (confirmado en buscador oficial; mayor volumen: impuestos/catastro/tránsito), sin contratos AND/CMS/nube, **SGDEA = Orfeo**, infra **DigitalOcean + Cloudflare**, integración SCD = nada aún, **PETI 2024-2027 vigente (encontrado)**, **Oficina de Atención al Ciudadano confirmada**, **Dirección TIC confirmada**. Ver `_global/contexto-transversal.md` §8.)*

## 9. Plazos legales clave (muchos ya vencidos al 2026)

| Obligación | Plazo | Estado |
|------------|-------|--------|
| WCAG 2.1 AA | 01/01/2022 | **Vencido** |
| Seguridad / Publicación / Datos abiertos (Anexos 2-4 Res. 1519) | 31/03/2021 | **Vencido** |
| Planeación de digitalización | 31/01/2022 | **Vencido** |
| IPv6 en coexistencia IPv4 | 31/12/2020 | **Vencido** |
| Digitalización Bloque 1 (30%) — tier Alcaldía-Avanzado | may/2028 | Vigente |
| Digitalización 100% — tier Alcaldía-Avanzado | mar/2034 | Vigente |
| 100% automatizado — tier Alcaldía-Avanzado | abr/2037 | Vigente |
| Plan de Acción e Informe de Gestión | antes del 31 de enero anual | Recurrente |
| Informes trimestrales PQRSD / inversión | cada 3 meses | Recurrente |
| Informe de Control Interno | cada 6 meses | Recurrente |
| Actualización SUIT tras acto administrativo | ≤ 3 días hábiles | Recurrente |
| Reporte incidente grave al CSIRT | ≤ 24 horas | Recurrente |

## 10. Marco normativo

Ley 1437/2011 (CPACA, sede electrónica) · Ley 1581/2012 (datos personales) · Ley 1712/2014 (transparencia) · Ley 1753/2015 (PND, Art. 45) · Ley 2052/2020 (racionalización de trámites) · Ley 1757/2015 (participación) · Decreto Ley 2106/2019 (antitrámites) · Decreto 620/2020 (SCD) · Decreto 088/2022 (digitalización) · Decreto 1078/2015 (Gobierno Digital) · Decreto 767/2022 · Resolución MinTIC 1519/2020 (Anexos 1-4: accesibilidad, publicación, seguridad, datos abiertos) · Resolución 2893/2020 (Anexo 2/2.1 sede; Anexo 3/3.1 VU; Anexo 4/4.1 transversales; Anexo 5/5.1 trámites) · Resolución 2160/2020 (SCD) · Directivas Presidenciales 02 y 03 de 2019 · WCAG 2.1 AA / NTC 5854 · Manual de Gobierno Digital v7 · Kit UI GOV.CO v9.2 · MSPI.

## 11. Resumen de trazabilidad

| Catálogo | RF | RNF | RN | UC | HU | Total |
|----------|----|----|----|----|----|-------|
| Bundle 1 (lotes 01,09,10,11,16,18) | 100 | 44 | 25 | 15 | 25 | 209 |
| Bundle 2 (29 docs marco GOV.CO) | 98 | 30 | 30 | 17 | 20 | 195 |
| Bundle 3 (lotes 03,04,06,14,15,17 + recuperados) | 155 | 46 | 38 | 20 | 28 | 287 |
| **TOTAL** | **353** | **120** | **93** | **52** | **73** | **691** |

> Los IDs originales (`RF-B1-001`, `RF-B2-001`, `RF-B3-001`, …) se conservan en cada módulo para trazar hacia los catálogos fuente en `/tmp/elicit/out/_req_bundle_{1,2,3}.md` y hacia los documentos originales vía `(#NN)`.

---

### Artefactos en `_global/`
- `contexto-transversal.md` — datos/entidades, integraciones, reportes, riesgos, dependencias, supuestos, contradicciones y preguntas abiertas.
- `auditoria-cobertura.md` — auditoría del crudo (264→87 docs; cobertura; recuperación de los GIF #93/#213).
- `diagramas.md` — **16 diagramas Mermaid** (mapa mental, flujo 4 etapas, flujo PQRSD, ER de datos, casos de uso por actor [D-05a ciudadano y D-05b back-office], arquitectura 13 zonas, secuencia OIDC, secuencia X-Road, estados PQRSD/trámite, integración con el Estado, dependencias entre módulos, roadmap Decreto 088, estados de la cita [D-13], ciclo editorial con SoD [D-14], estados del pago [D-15]).
- `matriz-trazabilidad.md` — **matriz de trazabilidad** requisito ↔ fuente `#NN` ↔ módulo ↔ necesidad ↔ stakeholder ↔ criterio ↔ contradicción/riesgo ↔ estado, + sub-matriz de contradicciones (C-01..C-08), módulo transversal (TX) y sección de huecos de trazabilidad. Ampliada con el delta profundo (+37 RF-D, +18 RNF-D, +54 RN-D, +57 HU-D).
- `casos-uso-detallados.md` — **50 casos de uso** (29 consolidados + 21 secundarios nuevos, incl. UC-042..050 del delta profundo) con precondiciones, postcondiciones, flujo principal, flujos alternos y de excepción (matriz "qué pasa si"), RF/RN involucrados y fuente.
- `{rf-rnf,rn,uc,hu}-delta-profundo.md` — registros de la segunda pasada profunda (delta integrado a los módulos; procedencia por etiqueta `[DOMINIO]/[NORMATIVA]/[WEB]/[INFERENCIA]/[PREGUNTA ABIERTA]`).
- `stakeholders-y-entrevistas.md` — **40 stakeholders** (perfil completo, explícito/inferido), matriz poder-interés (Mendelow) y **10 guías de entrevista** dirigidas a cerrar las preguntas abiertas.

> **Priorización MoSCoW:** en las tablas RF, `Must` = obligación legal/normativa explícita · `Should` = lineamiento de calidad o buena práctica documentada · `Could` = implícito no mandatorio · `Won't` = fuera de alcance por ahora.
