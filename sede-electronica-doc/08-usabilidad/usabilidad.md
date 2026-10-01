# Módulo 08 — Usabilidad y Experiencia de Usuario

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** diseño centrado en el usuario (DCU), navegación consistente, migas de pan, URLs limpias, formularios usables (componentes Kit UI), responsive, confirmaciones, lenguaje claro, SEO, validación W3C, encuesta SUS y pruebas de usuario.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

Calidad de experiencia de uso. Complementa Accesibilidad (módulo 07) e Identidad/Kit UI (módulo 01). Su métrica de referencia es la **escala SUS** (objetivo ≥68). Incluye los componentes de formulario del Kit UI y las prácticas de DCU de la Guía de Usabilidad de MinTIC (ISO 9241-11).

## 2. Requisitos Funcionales (RF)

### 2.1 Navegación, URLs y confirmaciones

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-080 / RF-B2-038 | **Migas de pan** en todas las páginas internas, coherentes con la jerarquía, sección actual marcada. | #170,#60,#70 | Must | En una subsección, el breadcrumb muestra Inicio > Transparencia > Normativa > Resoluciones. |
| RF-B1-081 / RF-B2-073 / RF-B3-144 | **URLs limpias**: sin caracteres especiales, en castellano, jerárquicas, descriptivas, SEO-friendly. | #170,#60,#243 | Should/Must | La resolución 001/2025 tiene URL `/transparencia/normativa/resolucion-001-2025`. |
| RF-B1-082 / RF-B2-042 | **Vínculos visitados** diferenciados (color, preferible púrpura); textos de enlace descriptivos. | #170,#60 | Must | Enlace visitado aparece en color distinto al no visitado. |
| RF-B2-072 | **Navegación global consistente** (mismo menú, orden y nombres en todas las páginas). | #60,#153,#173 | Must | El menú principal tiene los mismos ítems en el mismo orden en todas las páginas. |
| RF-B2-074 | El botón "atrás" del navegador nunca deja de funcionar dentro de la sede. | #60,#153,#173 | Must | Tras navegar varias páginas, "atrás" regresa correctamente sin perder sesión. |
| RF-B2-071 | **Página de inicio orientada a tareas** del usuario (no a intereses institucionales): los 3 trámites más solicitados a ≤2 clics. | #60,#153,#173 | Must | En el primer scroll del home, los 3 trámites más solicitados son accesibles. |
| RF-B1-083 / RF-B2-080 / RF-B3-145 | **Páginas de confirmación** para toda acción relevante (PQRSD, trámite, pago): acción completada, nº de referencia, próximos pasos, tiempo estimado. | #170,#60,#243 | Must | PQRSD enviada → confirmación con "Su solicitud fue radicada con el número XXXX. Recibirá respuesta en N días hábiles." |
| RF-B2-077 | Procesos largos subdivididos en **pasos numerados** (stepper). | #60,#153,#173 | Must | Trámite de 5 pasos → "Paso 1 de 5" con pasos pendientes identificables. |
| RF-B1-086 / RF-B2-078 / RF-B3-051 | **Sin pop-ups** no solicitados; modales solo en contexto, uno a la vez. | #170,#60 | Must | El home no muestra pop-ups publicitarios ni modales no solicitados, salvo el banner de cookies. |

### 2.2 Formularios usables (componentes Kit UI)

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-084 / RF-B2-024/025/026 / RF-B3-143 | Formularios con **ejemplos de formato**, campos obligatorios marcados (asterisco), etiquetas arriba del campo, **validación dinámica en línea** antes del envío. | #170,#241,#60,#129,#153 | Must | Campo "Teléfono" enfocado → placeholder "Ej: 3001234567"; campo inválido al perder foco → error inmediato. |
| RF-B3-077 | **Carga de archivos** (explorador o arrastrar-soltar) con tipos aceptados, tamaño máximo, nombre y tamaño del archivo cargado. | #118 | Must | Adjuntar un PDF de 2 MB → "documento.pdf (2 MB)" confirmado. |
| RF-B3-078 | **Desplegables con filtro** de búsqueda (máx 5 visibles, `aria-describedby`). | #118 | Must | Desplegable de 50 municipios, escribir "Bogot" → filtra a "Bogotá D.C.". |
| RF-B3-079 | **Entradas de texto** con estados Default/Active/Focus/Disabled/Invalid/Valid; contraseña con ojo mostrar/ocultar; autocomplete. | #118 | Must | Clic en el ícono de ojo muestra la contraseña en texto plano temporalmente. |
| RF-B3-080 | **Checkboxes/interruptores/radios** con `<label>` y `for`. | #118 | Must | Clic en la etiqueta "Queja" marca el checkbox correspondiente. |

### 2.3 Buscador, código limpio, SEO y mejora continua

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-006 / RF-B3-140/141 | Buscador con autocompletado (≤10), corrección ortográfica; resultados con fecha, categoría, título, extracto, autor, miniatura. *(estructura en módulo 01)* | #170,#243 | Must/Should | Escribir "licncia" → sugiere "licencia de construcción". |
| RF-B1-085 / RF-B2-079 / RF-B3-034 | **Diseño responsive**: desktop (≥992/1024 px), tablet (768-992 px), móvil (<768 px); sin scroll horizontal. | #7,#170,#241,#118 | Must | En smartphone de 375 px, el menú de transparencia es legible sin scroll horizontal ni contenido cortado. |
| RF-B1-089 / RF-B2-075 / RF-B3-138/143 | **Código HTML/CSS válido W3C** (validator.w3.org, jigsaw); CSS separado del HTML; sin tags ni vínculos rotos. | #170,#241,#60,#201 | Must | El validador W3C no reporta errores críticos en ninguna página. |
| RF-B1-076 / RF-B2-076 | Títulos y encabezados semánticos (H1-H6) en lenguaje claro; logo arriba a la izquierda enlazado a inicio. | #60,#153,#173 | Must | El lector de pantalla reconoce el H1 como título principal y los H2/H3 como subsecciones. |
| RF-B1-088 / RF-B2-082 | **SEO**: aparecer en los primeros 10 resultados para frases clave; títulos, meta-descripción, palabras clave, sitemap XML. | #170,#60,#153 | Should | Buscar "PQRSD Alcaldía Santa Marta" en Google → la sede aparece entre los primeros 5. |
| RF-B2-081 / RF-B3-050 | Texto del cuerpo alineado a la izquierda (no justificado), 60-80 caracteres por línea. | #60,#153,#173,#243 | Should | Cada línea del párrafo tiene 60-80 caracteres y está alineada a la izquierda. |
| RF-B1-087 / RF-B2-083 | **Encuesta SUS** (10 ítems Likert 1-5) accesible desde la sede; cálculo de puntaje; publicar resultados y plan de mejora (objetivo ≥68). | #21,#32,#190 | Should | Al finalizar un trámite, aparece la encuesta SUS; el sistema calcula y almacena el puntaje. |
| RF-B3-146/147 | **Lenguaje claro** en todos los contenidos (siglas explicadas, guía de voz y tono); evaluación periódica con pruebas de usuario (3-5 participantes/ronda) y evaluaciones heurísticas. | #76,#243 | Must/Should | Tras cada versión mayor, el equipo aplica las pruebas y genera un informe con hallazgos priorizados. |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-08-D01 | **Criterio cuantitativo de aceptación de usabilidad** para declarar "cumple": además del SUS ≥68, definir tasa de éxito de tareas (≥X%) y tiempo en tarea. Resuelve A-14 del propio §9. | [PREGUNTA ABIERTA]+[DOMINIO] | Should | **[PREGUNTA ABIERTA]** umbral de tasa de éxito a definir con Equipo UX. |

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B1-030 | Usabilidad — SUS | Puntaje SUS promedio ≥68 (escala 0-100) | #32 |
| RNF-B1-031 / RNF-B2-018 | Usabilidad — texto | 60-80 caracteres por línea | #170,#60 |
| RNF-B1-032 / RNF-B2-020 / RNF-B3-035 | Usabilidad — diseño | Resolución base 1024×768 sin scroll horizontal; responsive hasta 320 px | #170,#60,#243 |
| RNF-B2-019 | Usabilidad — buscador | Ancho mínimo del buscador ≈27 caracteres (~200 px) | #60,#153,#173 |
| RNF-B2-007 / RNF-B3-010 | Rendimiento | FCP ≤2.5 s en 4G; páginas internas ≤1 s | #60,#131,#243 |
| RNF-B2-021 / RNF-B3-038 | SEO / URLs | ≤posición 10 en Google para ≥3 de 5 frases clave; ≥95% URLs semánticas | #60,#153,#173 |
| RNF-B1-043 / RNF-B3-042/043 | Mantenibilidad / código | Clean Code; HTML/CSS válido W3C; cobertura de pruebas unitarias ≥70%; Git + CI/CD | #170,#165,#201 |
| RNF-B3-036 | Usabilidad — menú | ≤7 ítems en menú principal; ≤10 en submenús | #132,#118 |
| RNF-B3-037 | Lenguaje claro | Índice Fernández-Huerta ≥60 en descripciones de trámites | #243,#132 |

### 3.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-08-D01 | Persistencia de resultados | Cerrada una ronda SUS → resultados almacenados y exportables (CSV) con histórico comparable. | [DOMINIO] |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B2-026 | Texto subrayado solo para hipervínculos; énfasis con `<em>`/`<strong>`. | WCAG 2.1; W3C | #60,#153,#173 |
| RN-B1-038 / RN-B3-037 (apoyo) | Contenidos en lenguaje claro (Guía de Lenguaje Claro del DNP). | Guía Lenguaje Claro DNP | #225,#243 |
| RN-B2-029 (fase2) | Todo el código HTML/CSS cumple estándares W3C. | Guía Usabilidad MinTIC 2.13 | #170 |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-08-D01 | **Criterio cuantitativo de "cumple" en usabilidad:** además de SUS ≥68, una tarea crítica solo se declara conforme si supera un umbral de tasa de éxito y tiempo en tarea. (Motiva RF-08-D01; resuelve A-14.) **[PREGUNTA ABIERTA]** umbral de tasa de éxito a definir con Equipo UX. | Guía de Usabilidad MinTIC; benchmark SUS | [PREGUNTA ABIERTA]+[DOMINIO] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B1-013 / UC-B2-014 | Aplicar encuesta SUS | Ciudadano, Equipo UX | Tras un trámite → encuesta SUS de 10 ítems Likert 1-5 → cálculo automático del puntaje → almacenamiento → el equipo compara con el benchmark 68 y dispara mejoras si <68. | #32,#190 |
| UC-B2-017 | Actualizar contenido del portal | Editor, Administrador | Crea/modifica contenido en lenguaje claro (Guía DNP) → agrega `alt` → verifica enlaces → publica → el administrador verifica que no hay vínculos rotos y que pasa la validación W3C. | #60,#70,#153,#173 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B1-001 | Como ciudadano quiero encontrar la sede como primer resultado en Google. | **Positivo:** **Dado** que busco "Alcaldía Santa Marta" en Google **cuando** reviso los resultados orgánicos **entonces** la URL oficial de la sede aparece entre los primeros 5 resultados. | #170,#39 |
| HU-B1-010 / HU-B2-009 / HU-B3-004 | Como ciudadano quiero que la sede se vea y funcione bien en mi celular. | **Positivo:** **Dado** que accedo desde un smartphone de 360-390 px **cuando** navego y uso formularios **entonces** campos, botones y textos son legibles, el FCP es inferior a 2.5 s, no hay scroll horizontal y los botones miden ≥44×44 px. | #7,#170,#241,#118 |
| HU-B2-012 / HU-B3-016 | Como ciudadano quiero información en lenguaje claro, no jurídico. | **Positivo:** **Dado** que accedo a la ficha de un trámite **cuando** la leo **entonces** en menos de 2 minutos entiendo qué necesita, cómo solicitarlo, cuánto cuesta, cuánto tarda y qué obtiene. | #60,#70,#128 |
| HU-B1-017 / HU-B2-017 | Como ciudadano quiero el buscador para encontrar documentos sin navegar el menú. | **Positivo:** **Dado** que escribo "informe de gestión 2024" en el buscador **cuando** ejecuto la búsqueda **entonces** el primer resultado es el documento relevante con fecha y categoría visibles. | #225,#241 |
| HU-B2-019 | Como equipo UX quiero medir usabilidad con SUS para justificar mejoras. | **Positivo:** **Dado** que cierra una iteración de desarrollo **cuando** aplico la encuesta SUS a 10 usuarios **entonces** el sistema calcula el puntaje y, si es inferior a 68, el equipo presenta un plan de mejora en el siguiente sprint. | #190 |
| HU-B3-026 | Como ciudadano con datos móviles lentos quiero páginas que carguen rápido. | **Positivo:** **Dado** que accedo a la sede desde una conexión 3G **cuando** cargo la página de inicio **entonces** el FCP es inferior a 3 s. | #131,#243 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-08-D01 | Como equipo UX quiero declarar "cumple" una tarea solo si supera SUS ≥68 y un umbral de tasa de éxito/tiempo para medir usabilidad de forma objetiva. | **Positivo:** *Dado* una tarea crítica, *cuando* SUS ≥68 y la tasa de éxito ≥ umbral, *entonces* se declara conforme.<br>**Negativo:** *Dado* SUS 70 pero tasa de éxito por debajo del umbral, *cuando* se evalúa, *entonces* NO se declara conforme y se abre plan de mejora (**[PREGUNTA ABIERTA]** umbral). | RF-08-D01 · RN-08-D01 · A-14 · [PREGUNTA ABIERTA]+[DOMINIO] |
| HU-08-D02 | Como equipo UX quiero almacenar y exportar los resultados SUS para comparar histórico y sustentar el plan de mejora. | **Positivo:** *Dado* una ronda SUS cerrada, *cuando* la finalizo, *entonces* los resultados quedan almacenados y exportables a CSV con histórico comparable.<br>**Negativo:** *Dado* una exportación, *cuando* falla el almacenamiento, *entonces* se notifica el error sin perder los datos de la ronda. | RNF-08-D01 · [DOMINIO] |

## 7. Datos / Entidades del módulo

- **Evaluación SUS:** 10 ítems Likert 1-5, puntaje (benchmark 68), promedio vs. benchmark. (#190)
- **Arquetipo / proto-persona:** nombre ficticio, edad, ubicación, nivel digital, necesidades, motivaciones, frustraciones, escenarios. (#60,#153,#182)
- **Historial de búsquedas** y analítica de uso. (#118,#153)

## 8. Integraciones

- **Google Analytics / Search Console / Hotjar**: analítica de uso, palabras clave, comportamiento, tasa de abandono (mejora continua). (#153,#173)
- **Validadores W3C** (HTML/CSS, Link Checker): calidad de código. (#170)
- **Kit UI v9.2 / CDN GOV.CO**: componentes de formulario (módulo 01).

## 9. Ambigüedades y preguntas abiertas

- **[A-02]** La Guía de Usabilidad mezcla "se debe/se recomienda/es importante" sin jerarquía → no queda claro qué es obligatorio (sancionable) y qué es recomendación. (#170)
- **[#213 verificado]** El "Criterios de aceptación Usabilidad" (#213) es un **GIF animado** ("Conoce algunos tips de usabilidad…") con 3 tips, todos ya cubiertos: vínculos visitados (RF-B1-082), lenguaje claro y siglas explicadas (RNF-B1-038, RF-B3-146). No es una matriz formal de criterios. Ver `../_global/auditoria-cobertura.md` §6.
- **[A-14]** No hay criterios cuantitativos mínimos de usabilidad (tasa de éxito de tareas, SUS mínimo) para declarar "cumple". (#153,#173)
- **[Design Thinking]** La guía permite omitir etapas del Design Thinking, lo que podría saltarse las pruebas con usuarios que garantizan la usabilidad real. (#153,#173)
