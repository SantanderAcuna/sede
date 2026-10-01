# Módulo 07 — Accesibilidad (WCAG 2.1 AA)

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** cumplimiento obligatorio de **WCAG 2.1 nivel AA** (52 criterios de cumplimiento CC1-CC32 / tabla V8 del Anexo 1 Res. 1519/2020) en todos los contenidos y componentes: alternativas textuales, multimedia (subtítulos, audiodescripción, LSC), estructura semántica, navegación por teclado, contraste, formularios accesibles, barra de accesibilidad y compatibilidad con tecnologías de asistencia.
> **Carácter:** transversal — aplica a todos los módulos. Obligatorio desde el **01/01/2022**.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

La accesibilidad es transversal y de cumplimiento legal vencido (01/01/2022). Beneficia a personas con discapacidad visual, auditiva, motriz, cognitiva y fotosensible, y a usuarios con limitaciones tecnológicas. Se valida con herramientas automáticas (axe-core, Lighthouse, Tawdis, WCAG Contrast Checker, PEAT) **y revisión humana** con lectores de pantalla (NVDA, JAWS, VoiceOver).

## 2. Requisitos Funcionales (RF)

### 2.1 Barra de accesibilidad y preferencias

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-044 / RF-B2-043 / RF-B3-054 | **Barra de accesibilidad** persistente: tamaño de fuente (A / A+ / A++), alto contraste, enlace "Saltar al contenido", enlace al Centro de Relevo (discapacidad auditiva); guarda la preferencia del usuario. | #239,#241,#70,#118 | Must | Activar "Alto contraste" y navegar a otra página → el modo se mantiene activo. |
| RF-B3-022 | Enlace **"Saltar al contenido principal"** como primer elemento tabulable; ancla a `<main id="contenido-principal">`. | #76,#118 | Must | Primer Tab en cualquier página → aparece "Saltar al contenido principal". |

### 2.2 Alternativas textuales y multimedia (Perceptible)

| ID | Descripción (CC) | Fuente | Prio |
|----|------------------|--------|------|
| RF-B1-045 / RF-B2-045 / RF-B3-001 | `alt` descriptivo (≤150 car.) en imágenes funcionales; decorativas con `alt=""` o CSS (CC1). | #17,#70,#76,#84 | Must |
| RF-B1-046 / RF-B3-002/003/045 | Subtítulos (closed caption) en español en 100% de videos nuevos (desde 01/01/2022; excepción en vivo); transcripción de solo-audio; audiodescripción y **LSC** para alocuciones del alcalde, emergencias, seguridad ciudadana y rendición de cuentas (CC2, CC3). | #17,#76,#200 | Must |
| RF-B1-054 / RF-B3-008 | Sin audio automático; controles de pausa/parada para audio >3 s (CC18). | #17,#76 | Must |
| RF-B3-007 | El **color** nunca es el único medio para transmitir información (CC5). | #76 | Must |
| RF-B3-046 | Codificación **UTF-8** declarada (`<meta charset>`) (CC31). | — | Must |

### 2.3 Estructura, idioma y adaptabilidad

| ID | Descripción (CC) | Fuente | Prio |
|----|------------------|--------|------|
| RF-B1-047 / RF-B3-004 | HTML semántico (`header/nav/main/section/aside/article/footer`), jerarquía H1-H6 sin saltos, orden de lectura = DOM (CC8). | #17,#76,#84 | Must |
| RF-B3-047 / RF-B1-047 | Tablas y listas solo para datos semánticos, no maquetación (CC9). | #76,#17 | Must |
| RF-B1-052 / RF-B3-032 | `lang="es"`/`es-CO` en `<html>`; pasajes en otro idioma con su `lang` (CC27). | #17,#76 | Must |
| RF-B3-005/010/012 | Responsive en orientación vertical/horizontal; texto ampliable al **200%** y contenido al **400%** sin scroll horizontal ni pérdida (CC4). | #76,#200 | Must |
| RF-B3-011 | Prohibido usar imágenes de texto (salvo logos); separar contenido y presentación por CSS (CC29). | #76 | Must |
| RF-B3-014 | Espaciado de texto configurable (interlínea ≥1.5×, párrafo ≥2×, letras ≥0.12×, palabras ≥0.16×) (CSS relativo). | #76 | Must |
| RF-B3-035/036 | Componentes repetidos en la misma ubicación; misma función → mismo nombre accesible (CC13, CC7). | #76 | Must |

### 2.4 Operable por teclado y tiempo

| ID | Descripción (CC) | Fuente | Prio |
|----|------------------|--------|------|
| RF-B1-048 / RF-B2-044 / RF-B3-016 | Todos los componentes operables solo por teclado, sin velocidad específica (CC6, CC16, CC32). | #17,#70,#76 | Must |
| RF-B3-017 | Sin trampas de foco; entrar/salir de modales con teclado (ESC/Tab). | #76 | Must |
| RF-B1-048 / RF-B3-024/027 | Orden de foco secuencial = DOM (izq-der, arriba-abajo); **foco visible** con contraste ≥3:1; prohibido `outline:none` sin reemplazo (CC16, CC17). | #17,#76 | Must |
| RF-B3-018 | Atajos de una sola tecla desactivables/reasignables. | #76 | Should |
| RF-B3-019 | Límite de tiempo <20 h: ofrecer desactivar/ajustar/extender (CC19). | #76 | Must |
| RF-B1-051 / RF-B2-046 / RF-B3-020/021 | Controles pausar/parar/ocultar contenido en movimiento; slider en pausa por defecto; **sin destellos >3/seg** (verificable con PEAT); prohibidos GIFs con luces intermitentes (CC19, CC20). | #17,#170,#76,#243 | Must |
| RF-B3-028/029/031 | Alternativa a gestos multidedo; acción en `pointerup`; alternativa a funciones por movimiento del dispositivo. | #76 | Must/Should |
| RF-B3-033/034 | El foco no genera cambios de contexto; cambios solo por envío explícito, no por selección en `<select>` (CC22). | #76 | Must |

### 2.5 Comprensible — formularios y enlaces

| ID | Descripción (CC) | Fuente | Prio |
|----|------------------|--------|------|
| RF-B1-049 / RF-B3-038 | `<label>` asociada (`for`), instrucciones claras y ejemplo de formato (CC24, CC25). | #17,#170,#76,#200 | Must |
| RF-B3-006 | Propósito de cada campo con `type` y `autocomplete` HTML5. | #76 | Must |
| RF-B3-037/039 | Identificación textual de errores (no solo color) + sugerencias de corrección (CC28). | #76,#118 | Must |
| RF-B3-040 | Datos legales/financieros: envío reversible o modal de confirmación (WCAG 3.3.4). | #76 | Must |
| RF-B1-082 / RF-B3-025/048 | Textos de enlace descriptivos (prohibido "Clic aquí"/"Leer más"); vínculos visitados diferenciados (`a:visited`) (CC26, CC36). | #170,#76 | Must/Should |
| RF-B1-053 / RF-B3-026 | Múltiples vías de navegación al mismo contenido: menú, buscador, mapa del sitio, breadcrumb (CC12). | #17,#76 | Must |
| RF-B3-023 | `<title>` descriptivo "Nombre de la página – Nombre del sitio" (CC23). | #76,#200 | Must |

### 2.6 Robusto — ARIA y validación

| ID | Descripción (CC) | Fuente | Prio |
|----|------------------|--------|------|
| RF-B3-041 | HTML válido: etiquetas completas, anidación correcta, IDs únicos, sin atributos duplicados (CC11). | #76 | Must |
| RF-B3-042 | Componentes personalizados (acordeones, tabs, sliders, modales) con ARIA: nombre, función, valor (CC30, CC32). | #76 | Must |
| RF-B3-043 | Mensajes de estado con `role="status"`/`aria-live` sin tomar el foco. | #76 | Must |
| RF-B3-030 | Etiqueta visual = nombre accesible en código (control por voz) (CC7, CC24). | #76 | Must |
| RF-B3-015 | Tooltips descartables con ESC, hover persistente, foco visible (CC19). | #76,#118 | Should |
| RF-B3-044/052 | Mapa del sitio XML en footer; sin vínculos rotos (W3C Link Checker). | #200 | Must |
| RF-B3-049/050/051 | Sin texto justificado en prosa; 60-80 caracteres/línea; sin pop-ups automáticos. | #243 | Should/Must |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-07-D01 | **Bloqueo de publicación de multimedia inaccesible** en el CMS: no permitir publicar un video institucional sin subtítulos (y LSC si es alocución/emergencia/seguridad/rendición de cuentas). Convierte UC-B3-015 en regla bloqueante. | [NORMATIVA] RN-B3-002/003 | Must | Editor sube video sin .SRT → el CMS impide publicar y exige subtítulos. |

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B1-014 / RNF-B2-001 / RNF-B3-001 | Conformidad WCAG | **WCAG 2.1 AA completo** (CC1-CC32 / 52 criterios); 0 violaciones críticas/serias (axe-core, Lighthouse) | #17,#30,#76,#200 |
| RNF-B1-015 | Norma colombiana | NTC 5854 nivel AA mínimo | #156,#165 |
| RNF-B1-016 / RNF-B3-002 | Tecnologías de asistencia | Compatible con NVDA, JAWS, VoiceOver; ≥90% tareas críticas completables | #17,#241,#76 |
| RNF-B1-017 / RNF-B2-002 / RNF-B3-003 | Contraste | ≥4.5:1 texto normal; ≥3:1 texto grande (≥18pt/≥14pt bold); ≥3:1 componentes UI | #17,#170,#76,#118 |
| RNF-B1-018 / RNF-B2-029 | Animaciones | ≤3 destellos/seg; controles de pausa; respeta `prefers-reduced-motion`; movimiento ≤1/3 de pantalla | #17,#170,#60 |
| RNF-B1-019 | Alto contraste | Modo alto contraste altera todos los colores satisfactoriamente | #156,#241 |
| RNF-B3-004 | Zoom | 400% sin doble scroll horizontal ni pérdida | #76,#200 |
| RNF-B3-005 | Tap-target | ≥44×44 px en móvil | #118 |

### 3.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-07-D01 | Accesibilidad responsive | En tablet la barra de accesibilidad está disponible y operable. | [INFERENCIA]+[DOMINIO] |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-021 / RN-B3-001 | WCAG 2.1 AA obligatorio desde 01/01/2022 en todos los procesos de actualización/diseño/rediseño (52 criterios MinTIC). | Res. 1519/2020 Art.3; Ley 1618/2013 Art.16(11) | #17,#30,#76,#200 |
| RN-B3-002 | Subtítulos en 100% de videos nuevos (excepción: en vivo). | Res. 1519/2020 Anexo 1, 1.5 | #200 |
| RN-B3-003 | LSC obligatoria para 4 tipos de contenido (alocución, emergencias, seguridad ciudadana, rendición de cuentas). | Res. 1519/2020 Anexo 1 | #76,#200 |
| RN-B2-026 | Texto subrayado solo para hipervínculos; énfasis con `<em>`/`<strong>`. | WCAG 2.1; W3C | #60,#153,#173 |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-07-D01 | **Bloqueo de publicación de multimedia inaccesible:** el CMS NO permite publicar un video institucional sin subtítulos, ni un contenido de alocución/emergencia/seguridad/rendición de cuentas sin LSC. (Convierte RN-B3-002/003 en regla bloqueante; motiva RF-07-D01.) | Res. 1519/2020 Anexo 1, 1.5; Ley 1618/2013 | [NORMATIVA] |
| RN-07-D02 | **Captcha accesible obligatorio:** todo desafío anti-bot debe ofrecer alternativa accesible (audio/no visual) para no excluir a personas con discapacidad visual; un captcha solo-visual viola WCAG AA. (Resuelve la contradicción Captcha vs A11y.) | WCAG 2.1 AA (1.1.1); Res. 1519/2020 | [NORMATIVA] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B2-011 / UC-B3-016 | Verificar accesibilidad de página | Ingeniero de accesibilidad / QA | Ejecuta axe-core/Lighthouse/Tawdis + Contrast Checker → revisa los 52 criterios → navega con teclado → prueba con NVDA → verifica móvil → documenta hallazgos → corrige antes de producción. | #60,#70,#76,#200 |
| UC-B3-009 | Navegar con lector de pantalla | Ciudadano con discapacidad visual | Activa NVDA/JAWS → primer Tab "Saltar al contenido" → menús con roles/estados ARIA → formulario con validación anunciada → confirmación vía `aria-live`. | #76,#200 |
| UC-B3-010 | Usar la sede con zoom 400% | Ciudadano con baja visión | Configura 400% → contenido visible sin scroll horizontal → botones ≥44×44 px → completa un trámite sin pérdida de funcionalidad. | #76,#200 |
| UC-B3-015 | Publicar video institucional accesible | Administrador de contenidos | Sube video → el sistema verifica subtítulos (SRT) → agrega audiodescripción → si es presidencial/emergencia requiere LSC → publica con recursos de accesibilidad. | #76,#200 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B1-003 / HU-B2-002 / HU-B3-001 | Como ciudadano con discapacidad visual quiero navegar con lector de pantalla de forma autónoma. | **Positivo:** **Dado** que uso NVDA en el formulario PQRSD **cuando** recorro los campos con Tab **entonces** el lector anuncia etiqueta, tipo, formato y errores, y puedo enviar el formulario sin ver la pantalla. | #17,#165,#76,#200 |
| HU-B1-009 / HU-B3-005 | Como ciudadano con movilidad reducida quiero completar formularios solo con teclado. | **Positivo:** **Dado** que navego sin ratón **cuando** uso Tab/Shift+Tab/Enter en cualquier formulario **entonces** recorro y envío el formulario con el foco siempre visible. | #17,#241,#76 |
| HU-B1-015 / HU-B3-002 | Como ciudadano con baja visión quiero alto contraste y ampliar texto al 400% sin perder contenido. | **Positivo:** **Dado** que activo alto contraste y ajusto el zoom al 400% **cuando** navego por la sede **entonces** la preferencia persiste en páginas siguientes, no aparece scroll horizontal y no se pierde funcionalidad. | #17,#239,#76 |
| HU-B1-018 / HU-B3-003 | Como ciudadano sordo quiero subtítulos y LSC en los videos institucionales. | **Positivo:** **Dado** que accedo a un video de rendición de cuentas **cuando** lo reproduzco **entonces** dispongo de subtítulos en español sincronizados e interpretación en LSC. <br> **Negativo:** **Dado** un video institucional sin subtítulos **cuando** el editor intenta publicarlo **entonces** el CMS lo bloquea y exige cargar el archivo de subtítulos antes de publicar. | #17,#76,#200 |
| HU-B3-016 | Como ciudadano con discapacidad cognitiva quiero textos en lenguaje claro. | **Positivo:** **Dado** que accedo a la ficha de un trámite **cuando** la leo **entonces** comprendo qué hace, quién puede solicitarlo y qué necesita, sin necesidad de consultar a un asesor. | #76,#243 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-07-D01 | Como editor quiero que el CMS me impida publicar un video sin subtítulos (y LSC cuando aplica) para no publicar contenido inaccesible. | **Positivo:** *Dado* un video con `.SRT` y, si es rendición de cuentas, ventana LSC, *cuando* publico, *entonces* el CMS lo permite.<br>**Negativo:** *Dado* un video sin subtítulos, *cuando* intento publicarlo, *entonces* el CMS lo bloquea y exige subtítulos. | RF-07-D01 · RN-07-D01 · [NORMATIVA] Res.1519 Anexo 1, 1.5 |
| HU-07-D02 | Como ciudadano que usa tablet quiero la barra de accesibilidad disponible y operable en 768-992 px para ajustar contraste y tamaño en mi dispositivo. | **Positivo:** *Dado* una tablet de 800 px, *cuando* abro la sede, *entonces* la barra de accesibilidad está visible y operable.<br>**Negativo:** *Dado* el breakpoint de tablet, *cuando* uso la barra, *entonces* no se solapa con el contenido ni pierde funciones respecto a desktop. | RNF-07-D01 · [INFERENCIA]+[DOMINIO] |
| HU-07-D03 | Como ciudadano con discapacidad visual quiero una alternativa accesible al captcha para poder enviar formularios sin barreras. | **Positivo:** *Dado* un desafío anti-bot, *cuando* uso lector de pantalla, *entonces* dispongo de alternativa de audio/no visual para resolverlo.<br>**Negativo:** *Dado* un captcha solo visual, *cuando* se audita, *entonces* se marca como no conforme con WCAG AA. | RN-07-D02 · [NORMATIVA] WCAG 2.1 AA 1.1.1 |

## 7. Datos / Entidades del módulo

- **Preferencias de accesibilidad del usuario:** tamaño de fuente (A/A+/A++), alto contraste (persistidas). (#239,#241)
- **Recurso multimedia accesible:** video, subtítulos (SRT), audiodescripción, LSC, transcripción. (#76,#200)

## 8. Integraciones

- **Centro de Relevo** (discapacidad auditiva) enlazado desde la barra de accesibilidad. (#70,#125)
- **MinTIC**: software gratuito de lectura de sitios web a nivel nacional/territorial. (#217)
- **INCI** (Instituto Nacional para Ciegos): colaboró en las Directrices de Accesibilidad. (#200)

## 9. Ambigüedades y preguntas abiertas

- **[A-WCAG / Contradicción 3]** AA obligatorio desde 2022 pero **sin** entidad fiscalizadora, sanción ni umbral de errores tolerables definidos; los validadores automáticos son insuficientes (requiere revisión humana sin protocolo definido). (#76,#84,#200)
- **[#93 verificado]** El "Criterios de aceptación Accesibilidad" (#93) NO es una matriz formal de criterios: es un **GIF animado** (idéntico al de #213) con 3 tips de usabilidad ya cubiertos. Los criterios formales de accesibilidad están en la Res. 1519/2020 Anexo 1 (los 52 CC de este módulo). Ver `../_global/auditoria-cobertura.md` §6.
- **[WCAG 2.1 vs 2.2]** Las guías citan 2.1; WCAG 2.2 es de 2023. (#153,#173)
- **[A-08]** La Guía de Usabilidad escribe el contraste como "4:5:1" (error; debe ser 4.5:1). (#170)
- **[LSC territorial]** ¿La obligación de LSC aplica a la Alcaldía (gobierno territorial) o solo al nivel nacional? (#76,#200)
- **[Barra A11y en tablet]** El Kit UI muestra la barra de accesibilidad solo en desktop (≥992 px); no se define su comportamiento en tablet (768-992 px). (#118)
