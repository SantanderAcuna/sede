# 01.2 — Estándares de Ingeniería de Requisitos

> Marco académico y normativo de la disciplina de Ingeniería de Requisitos, base metodológica del SRS y de los procesos de elicitación, especificación, validación y verificación del proyecto.

---

## 1. ISO/IEC/IEEE 29148:2018 — Systems and software engineering — Life cycle processes — Requirements engineering

La norma ISO/IEC/IEEE 29148:2018 [1] es el estándar vigente (a 2026) para los procesos de ingeniería de requisitos a lo largo del ciclo de vida. Reemplaza al estándar IEEE 830-1998 [2] como referencia principal.

### 1.1 Estructura del documento de requisitos (Sección 6)

La norma prescribe que un SRS (Software Requirements Specification) debe incluir:

| Sección | Contenido |
|---|---|
| 1. Introducción | Propósito, audiencia, alcance, definiciones |
| 2. Descripción general | Perspectiva del producto, funciones, restricciones, supuestos |
| 3. Requisitos específicos | Funcionales, no funcionales, de interfaz |
| 4. Verificación | Trazabilidad a criterios de aceptación |

### 1.2 Características de un buen requisito (Sección 5.2)

Un requisito bien redactado debe ser:

- **Necesario** (sin él el sistema no satisface una necesidad del usuario).
- **No ambiguo** (una sola interpretación posible).
- **Verificable** (existe un método para comprobar que se cumple).
- **Trazable** (tiene un ID único, se mapea a diseño, código y prueba).
- **Completo** (define toda respuesta del sistema ante cualquier entrada).
- **Consistente** (no contradice otros requisitos).
- **Modificable** (estructurado de forma que se pueda cambiar).
- **Ordenado por importancia/estabilidad.**

---

## 2. IEEE 830-1998 — Recommended Practice for Software Requirements Specifications

Aunque el estándar ISO/IEC/IEEE 29148:2018 [1] lo reemplaza, el IEEE 830-1998 [2] sigue siendo citado por su claridad en la **plantilla SRS** y por la introducción de los conceptos de **requisitos de interfaz, rendimiento y restricciones de diseño**.

> **Decisión:** en este SRS seguimos el esqueleto ISO/IEC/IEEE 29148:2018, citando IEEE 830 cuando la plantilla agrega claridad (especialmente en la sección de requisitos de interfaz).

---

## 3. Roger S. Pressman & Bruce R. Maxim — Software Engineering: A Practitioner's Approach (9ª ed.)

Pressman y Maxim [3] dedican los capítulos 6–8 del libro a la ingeniería de requisitos:

### 3.1 Elicitación (cap. 6)

Técnicas recomendadas y aplicadas en este proyecto:

| Técnica | Aplicación |
|---|---|
| Entrevistas | Con funcionarios de la Alcaldía (Dirección TIC, Atención al Ciudadano, Hacienda) |
| Análisis de documentos | Revisión de PETI 2024-2027, transparencia actual, organigrama |
| Cuestionarios | Encuesta SUS a usuarios externos (módulo usabilidad) |
| Observación | Revisión de portales de Montería y Santa Marta como referencia |
| Talleres / focus groups | Con stakeholders internos y externos |

### 3.2 Especificación (cap. 7)

Pressman recomienda combinar **lenguaje natural estructurado** (para stakeholders) con **notaciones formales** (para desarrollo). En este proyecto:

- **Lenguaje natural estructurado:** plantillas por requisito funcional con secciones fijas (descripción, prioridad, actor, flujo principal, criterios Gherkin).
- **Notaciones formales:** diagramas Mermaid (ER, C4, secuencia, flujo) y OpenAPI 3.1.

### 3.3 Validación y verificación (cap. 8)

- **Validación:** "¿construimos el sistema correcto?" — revisado con stakeholders en talleres.
- **Verificación:** "¿construimos correctamente el sistema?" — ejecutado en CI/CD con pruebas Pest (backend) y Vitest (frontend).

---

## 4. Ian Sommerville — Software Engineering (10ª ed.)

Sommerville [4] presenta un enfoque **orientado a procesos**:

### 4.1 Modelos de proceso

- **Cascada:** secuencial; útil para módulos bien comprendidos.
- **Incremental:** cada incremento entrega valor; **adoptado en este proyecto** con sprints de 2 semanas.
- **Espiral:** iterativo con análisis de riesgo; complementario.
- **Ágil (Scrum/Kanban):** iterativo, con ceremonias; **adoptado para la entrega del Sprint 0 al Sprint 8** según `07-plan-tareas/sprints.md`.

### 4.2 Trazabilidad bidireccional

Sommerville distingue:

- **Trazabilidad hacia adelante (forward):** requisito → diseño → código → prueba.
- **Trazabilidad hacia atrás (backward):** prueba → requisito.

> **Implementado en:** `08-trazabilidad/matriz-requisitos-tareas.md` y `08-trazabilidad/matriz-requisitos-tests.md`.

---

## 5. Karl Wiegers & Joy Beatty — Software Requirements (3ª ed.)

Wiegers y Beatty [5] son la referencia principal para la **redacción de requisitos de alta calidad** y la **gestión de requisitos**.

### 5.1 Plantilla recomendada para un requisito funcional (cap. 7)

| Campo | Descripción |
|---|---|
| Identificador | Único, estable, jerárquico (RF-XX-NNN) |
| Título | Frase corta en imperativo |
| Contexto / precondición | Estado del sistema antes de ejecutar |
| Fuente | Documento, stakeholder, norma |
| Prioridad | MoSCoW |
| Criterios de aceptación | Given / When / Then |
| Trazabilidad | A diseño y pruebas |

> **Aplicado en:** `02-requisitos/requisitos-funcionales.md` con la plantilla completa.

### 5.2 Priorización MoSCoW

Técnica adoptada en este SRS:

| Categoría | Significado | Política |
|---|---|---|
| **Must** | Sin esto el sistema no cumple | Sprint 0–2 obligatorio |
| **Should** | Importante pero hay workaround | Sprint 3–4 |
| **Could** | Deseable, prioridad baja | Sprint 5+ |
| **Won't** | Explícitamente fuera del alcance | Documentado en `09-riesgos/` |

### 5.3 Reglas de negocio (cap. 11)

Wiegers diferencia:

- **Reglas de negocio:** restricciones que el sistema debe respetar (RN-01, RN-02, …).
- **Requisitos:** comportamiento que el sistema debe exhibir (RF-XX-NNN).

> Las RN son **invariantes del dominio**; los RF son **comportamiento del sistema** para satisfacer las RN. Esta distinción se aplica en `02-requisitos/restricciones.md`.

---

## 6. Resumen de aplicación metodológica

| Fase | Estándar / autor | Producto entregable |
|---|---|---|
| Elicitación | Pressman cap. 6 [3], Sommerville cap. 4 [4] | `sede-electronica-doc/_extraccion/` (16 extracciones) |
| Especificación | ISO/IEC/IEEE 29148:2018 [1], Wiegers cap. 7 [5] | `02-requisitos/` con plantilla completa |
| Validación | Pressman cap. 8 [3], Sommerville cap. 4 [4] | Talleres con stakeholders + criterios Gherkin |
| Verificación | Wiegers cap. 15 [5] | `07-plan-tareas/` + pruebas Pest/Vitest |
| Gestión de cambios | Wiegers cap. 13 [5] | ADRs en `03-propuesta/adr.md` |
| Trazabilidad | Sommerville cap. 4 [4], IEEE 830 [2] | `08-trazabilidad/` |

---

## Referencias IEEE (estándares de ingeniería)

[1] International Organization for Standardization, International Electrotechnical Commission, and Institute of Electrical and Electronics Engineers, *ISO/IEC/IEEE 29148:2018 — Systems and software engineering — Life cycle processes — Requirements engineering*, Geneva, Switzerland: ISO, 2018.

[2] Institute of Electrical and Electronics Engineers, *IEEE 830-1998 — IEEE Recommended Practice for Software Requirements Specifications*, New York, NY: IEEE, 1998.

[3] R. S. Pressman and B. R. Maxim, *Software Engineering: A Practitioner's Approach*, 9th ed. New York, NY: McGraw-Hill Education, 2020.

[4] I. Sommerville, *Software Engineering*, 10th ed. Harlow, UK: Pearson Education Limited, 2016.

[5] K. Wiegers and J. Beatty, *Software Requirements*, 3rd ed. Redmond, WA: Microsoft Press, 2013.
