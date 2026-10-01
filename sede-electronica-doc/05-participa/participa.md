# Módulo 05 — Participa (Participación Ciudadana)

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** sección "Participa" estructurada en fases de participación (Ley 1757/2015 y lineamientos DAFP), consulta ciudadana de normas en elaboración (SUCOP), agenda regulatoria y micrositios diferenciados para grupos de interés.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

"Participa" es uno de los 4 ítems obligatorios del menú principal. Habilita los espacios, mecanismos y acciones de participación ciudadana, la consulta de proyectos normativos (integrada con SUCOP del DNP) y micrositios accesibles para grupos de valor del Distrito.

## 2. Requisitos Funcionales (RF)

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-038 / RF-B3-105 | Sección **"Participa"** con espacios, mecanismos y acciones de participación según lineamientos DAFP, estructurada en 4 fases: Diagnóstico, Planeación, Ejecución y Evaluación (Ley 1757/2015). | #21,#239,#241,#132,#209 | Must | En "Participa" el ciudadano encuentra las 4 fases con mecanismos activos y fechas de vigencia (convocatorias, consultas públicas). |
| RF-B1-039 | **Participación en elaboración de normas**: medio digital para aportes a normas en proceso, integración con **SUCOP** (DNP); publicar Agenda Regulatoria. | #7,#225 | Must | Norma en elaboración → el ciudadano radica comentarios vía el formulario habilitado en SUCOP. |
| RF-B1-040 | **Micrositios para grupos de interés**: NNA, mujeres, personas con discapacidad, adultos mayores, comunidades étnicas, LGBTIQ+; en lenguaje claro y accesible. | #239 | Should | Una persona con discapacidad accede a su micrositio y encuentra información relevante en lenguaje claro, con textos alternativos y sin barreras. |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-05-D01 | **CRUD y ciclo de vida de mecanismos de participación y consultas** (crear, abrir, cerrar al vencer la fecha máxima de comentarios, archivar), con cierre automático de la recepción de aportes al expirar. | [DOMINIO]+[NORMATIVA] RF-B1-038/039 describen el render; falta el ciclo de vida | Should | Consulta con fecha límite vencida → el formulario de aportes se deshabilita y queda en estado "Cerrada". |
| RF-05-D02 | **Publicación del resultado/cierre de la participación** (consolidado de aportes recibidos y respuesta de la entidad), por trazabilidad del Art. 2.1.2.1.14 Decreto 1081 (rendición). | [NORMATIVA]+[DOMINIO] | Could | Cerrada una consulta normativa → se publica el documento de observaciones recibidas y cómo se incorporaron. |

**Pregunta abierta:** ¿Los aportes se reciben **dentro de la sede** o íntegramente en SUCOP? Determina si hay CRUD propio o solo redirección. Responsable: Oficina de Participación + DNP/SUCOP.

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| (aplican transversales) | Accesibilidad | WCAG 2.1 AA en micrositios y formularios de aportes (ver módulo 07) | #17,#239 |
| RNF-B1-038 | Lenguaje claro | Contenidos de micrositios en lenguaje claro (Guía DNP) | #225 |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-025 (fase2) | La sección "Participa" debe publicarse conforme a lineamientos del DAFP con espacios de diagnóstico, formulación, implementación, evaluación y seguimiento. | Res. 2893/2020 Anexo 2, 4.1.2.3 | #241 |
| RN-B1-039 (apoyo) | Habilitar consulta pública de proyectos de norma con fecha máxima de comentarios; integración con SUCOP. | Ley 1712/2014; lineamientos DNP | #7,#225 |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-05-D01 | **Cierre automático por vencimiento:** al expirar la fecha máxima de comentarios de una consulta/proyecto normativo, la recepción de aportes se deshabilita y el mecanismo pasa a "Cerrada"; no se aceptan aportes extemporáneos. (Motiva RF-05-D01; opera RN-B1-039.) | Ley 1712/2014; lineamientos DNP/SUCOP | [NORMATIVA]+[DOMINIO] |
| RN-05-D02 | **Publicación del resultado del proceso participativo:** cerrada una consulta normativa, se publica el consolidado de observaciones recibidas y cómo se incorporaron (o por qué no), por trazabilidad de la rendición. (Motiva RF-05-D02.) | Decreto 1081/2015 Art.2.1.2.1.14; Ley 1757/2015 | [NORMATIVA] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B1-009 | Participar en consulta ciudadana de norma | Ciudadano | "Participa" → norma en elaboración con plazo → lee el proyecto → "Aportar comentarios" → redirección a SUCOP → radica comentarios → confirmación. | #7,#225 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B1-016 | Como ciudadano quiero participar en la consulta de una nueva norma en línea para influir en las decisiones. | **Positivo:** **Dado** que existe una norma con consulta abierta en "Participa" **cuando** el ciudadano la selecciona **entonces** puede ver el proyecto, acceder a "Aportar comentario" y ser dirigido a SUCOP para radicar su aporte. <br> **Negativo:** **Dado** una consulta cuya fecha límite ya venció **cuando** el ciudadano intenta aportar por URL directa **entonces** el sistema rechaza el aporte extemporáneo e informa que la consulta está cerrada. | #7,#225 |
| HU-B3-027 | Como ciudadano quiero participar en consultas y espacios de participación desde la sede. | **Positivo:** **Dado** que el menú "Participa" tiene una consulta activa **cuando** el ciudadano registra su opinión **entonces** el sistema confirma su participación. <br> **Negativo:** **Dado** una consulta cerrada **cuando** el ciudadano intenta aportar por URL directa **entonces** el sistema rechaza el aporte extemporáneo e informa que la consulta está cerrada. | #132,#209 |
| HU-B1-040 (apoyo) | Como persona de un grupo de interés quiero un micrositio accesible con información para mi condición. | **Positivo:** **Dado** que el ciudadano pertenece a un grupo de interés (NNA, mujeres, discapacidad, adultos mayores, comunidades étnicas, LGBTIQ+) **cuando** accede al micrositio correspondiente **entonces** encuentra información en lenguaje claro, con textos alternativos y sin barreras de accesibilidad. | #239 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-05-D01 | Como administrador de participación quiero abrir, cerrar al vencer y archivar consultas para que no se reciban aportes extemporáneos. | **Positivo:** Dado una consulta con fecha límite, cuando esta vence, entonces el formulario de aportes se deshabilita y el estado pasa a "Cerrada".<br>**Negativo:** Dado una consulta cerrada, cuando un ciudadano intenta aportar por URL directa, entonces el sistema rechaza el aporte extemporáneo. | RF-05-D01 · RN-05-D01 · [NORMATIVA]+[DOMINIO] |
| HU-05-D02 | Como ciudadano quiero ver el consolidado de observaciones recibidas y cómo se incorporaron para verificar que mi participación tuvo efecto. | **Positivo:** Dado una consulta normativa cerrada, cuando la entidad publica el cierre, entonces veo las observaciones y la respuesta de la entidad (incorporadas o no, con motivo).<br>**Negativo:** Dado una consulta cerrada sin documento de resultado, cuando se evalúa la rendición, entonces el sistema alerta el pendiente al responsable. | RF-05-D02 · RN-05-D02 · [NORMATIVA] Decreto 1081 Art.2.1.2.1.14 |

## 7. Datos / Entidades del módulo

- **Proyecto normativo en consulta:** texto, fecha de publicación, fecha máxima de comentarios, enlace a SUCOP. (#7,#225)
- **Mecanismo/espacio de participación:** fase (Diagnóstico/Planeación/Ejecución/Evaluación), descripción, vigencia. (#239,#241)
- **Micrositio de grupo de interés:** grupo (NNA, mujeres, discapacidad, adultos mayores, étnicas, LGBTIQ+), contenido accesible. (#239)

## 8. Integraciones

- **SUCOP (DNP):** comentarios a proyectos normativos. (#225)
- **Transparencia / Normativa:** la Agenda Regulatoria y los proyectos de norma se enlazan desde Transparencia (fuente única). (#225)

## 9. Ambigüedades y preguntas abiertas

- ✅ **RESUELTA (2026-06-05):** existe la **Oficina de Atención al Ciudadano** / **Atención al Ciudadano y Participación Social** (organigrama oficial `/dependencias`; correo `atencionalciudadano@santamarta.gov.co`, radicación L-V 8:00–17:00). Asume la función del Art. 17 Ley 2052/2020. (#23)
- **[A-07]** Caracterización de grupos de interés no definida normativamente. (#225)
- **[PREGUNTA ABIERTA]** ¿Qué mecanismos de participación específicos del Distrito de Santa Marta deben publicarse y cuál es su calendario? (#239)
