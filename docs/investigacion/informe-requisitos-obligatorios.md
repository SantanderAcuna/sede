# Informe de requisitos obligatorios — Sede Electrónica (expediente `docs/`)

> **Analista de requisitos.** Fuentes leídas completas: `docs/GUIA Maestra Sede Electronica Colombia.md` (3462 líneas), `docs/Sección 1 · Marco normativo.md`, `docs/Sección 2 · Diseño.md`, `docs/Sección 3 · Accesibilidad.md`, `docs/Sección 4 · Seguridad.md`, `docs/Sección 5 · Funcionalidad.md`, `docs/Sección 6 · Implementación paso a paso.md`, `docs/adr/README.md`, más `docs/trazabilidad.md` y `docs/despliegue-secretos.md` (presentes en `docs/` y necesarios para el inventario).
>
> **Nota de estructura:** la *Guía Maestra* es una **concatenación literal** de las secciones 1–6 (su §1 = `Sección 1`, su §2 = `Sección 2`, etc.) más tres apéndices (A glosario, B lista maestra, C referencias) que **no existen** en los archivos sueltos. Cuando una afirmación solo aparece en la Guía, se cita como `Guía Maestra, Apéndice X`.
>
> Todo dato no documentado se marca **no consta**. Ningún requisito de este informe fue inventado.

---

## 1. Marco normativo aplicable

| Norma (número y año) | Qué obliga | Artículo / anexo relevante | ¿Bloqueante? |
|---|---|---|---|
| **Decreto Ley 2106 de 2019** | Una sola sede electrónica por autoridad; integrar todos los portales, sitios web, plataformas, ventanillas únicas, aplicaciones y soluciones previas; garantizar seis atributos de calidad (calidad, seguridad, disponibilidad, accesibilidad, neutralidad, interoperabilidad); publicar canales digitales oficiales; integrar al Portal Único GOV.CO; expedientes electrónicos con integridad, disponibilidad y autenticidad; pagos electrónicos; no exigir documentos que reposen en otra entidad; desmaterializar certificados. | arts. 1, 2, 8, 9, 10 (+pars. 1–3), 13, 14 (inc. 1–3), 15 (+pars. 1–2), 16 (+par.), 17, 18, 19, 147 | **Sí** — es la norma habilitante (`Sección 1 · §1.2`, `§1.3`) |
| **Resolución MinTIC 1519 de 2020** (24-ago-2020, DO 51.521) | Estándares y directrices para publicar la información de la Ley 1712/2014; acceso a información pública, accesibilidad web, seguridad digital y datos abiertos. | Anexo 1 (WCAG 2.1 AA), Anexo 2 (transparencia y divulgación), Anexo 3 (HTTPS, cabeceras, BCP/DRP 7×24×365), Anexo 4 (datos abiertos, federación a datos.gov.co). Art. 3 (WCAG 2.1 AA obligatorio **desde 1-ene-2022**) | **Sí** (`Sección 1 · §1.5.1`; `Sección 3 · §3.1.1`) |
| **Resolución MinTIC 2893 de 2020** | Estandarizar ventanillas únicas, portales transversales y unificar la imagen de las sedes; guía técnica de sede electrónica y de integración a GOV.CO; define los seis atributos y los niveles de integración. | Anexo 1 (guía técnica de sede), Anexo 2 (integración al Portal Único, v1 dic-2020) | **Sí** para la integración a GOV.CO (`Sección 1 · §1.5.1`, `§1.5.3`) |
| **Resolución MinTIC 500 de 2021** | Adopta el MSPI como habilitador de la Política de Gobierno Digital; gestión de incidentes de seguridad digital; étapas prevención / protección y detección / respuesta y comunicación / recuperación y aprendizaje. | art. 5 + Anexo 1 (catálogo ISO 27001:2022 Anexo A), art. 9 (roles y clasificación de incidentes), art. 17 | **Sí** (`Sección 4 · §4.5.3`, `§4.6.1`) |
| **Resolución MinTIC 2239 de 2024** | Política General de Seguridad y Privacidad de la Información del MinTIC/FUTIC; el enlace con autoridades es el representante legal. | art. 4 (citado) | Referencia de rol; **no consta** que obligue directamente a la entidad territorial (`Sección 4 · §4.6.4`, N-06) |
| **Ley 1712 de 2014** | Transparencia y acceso a la información pública: información veraz, oportuna, accesible y reutilizable. | arts. 1, 2, 5, 17 (sujetos obligados, art. 5 corregido por Decreto 1494/2015) | **Sí** — define el contenido del módulo de Transparencia (`Sección 3 · §3.1.1`, `Sección 1 · §1.5.1`) |
| **Decreto 1081 de 2015** (DUR Sector TIC) | Faculta a MinTIC para expedir lineamientos de accesibilidad web. | art. 2.1.1.2.2.2 | **Sí** como habilitante de la Res. 1519/2020 (`Sección 3 · §3.1.1`) |
| **Decreto 1494 de 2015** | Corrige el art. 5 de la Ley 1712/2014: fija los sujetos obligados (aplica a la entidad). | art. 1 | **Sí** — determina el alcance (`Sección 3 · §3.1.3`) |
| **Ley 1581 de 2012** (Habeas Data) | Autorización previa, expresa e informada; política de tratamiento; derechos del titular (conocer, actualizar, rectificar, suprimir, revocar); medidas de seguridad; inscripción en el RNBD; reporte de incidentes; régimen sancionatorio. | arts. 8, 9, 11 (caducidad), 13, 17 lit. b y g, 18 lit. b, 19, 23 (multas hasta 2.000 SMLMV), 25 (RNBD) | **Sí** (`Sección 4 · §4.5.1`, `§4.5.4`) |
| **Decreto 1377 de 2013** | Reglamenta parcialmente la Ley 1581/2012 (autorización, aviso de privacidad, política de tratamiento). | art. 4 lit. f, art. 13 | **Sí** (`Sección 4 · §4.5.1`) |
| **Ley 527 de 1999** | Validez jurídica de mensajes de datos y firma electrónica (equivalencia funcional). | citada globalmente | **Sí** para el módulo de firma (`Sección 5 · §5.4` INT-05) |
| **Decreto 2364 de 2012** | Reglamenta la firma electrónica y las entidades de certificación. | — | **Sí** para firma (INT-05) |
| **Ley 1437 de 2011 (CPACA), art. 56 sustituido por Ley 2080 de 2021** | Notificación electrónica de actos administrativos con acuse de recibo. | art. 56 | **Sí** para el módulo de notificaciones (`Sección 5 · §5.2.5`, FUN-039) |
| **Decreto 491 de 2020** | Notificaciones electrónicas. | — | **Sí** para notificaciones (INT-06, INT-07) |
| **Ley 1755 de 2011** | Derecho de petición; términos de respuesta a PQRSD. | art. 14 | **Sí** — fija el plazo de 15 días hábiles (`Sección 5 · §5.6.2`) |
| **Ley 2052 de 2020** | Racionalización de trámites, automatización, trámites 100 % en línea, carpeta ciudadana digital, estampilla electrónica. Plazo general 12 meses desde promulgación. | arts. 5, 6, 8, 12, 13 | **Sí** (`Sección 1 · §1.5.1`) |
| **Decreto 088 de 2022** (adiciona Título 20 al Decreto 1078/2015) | Plazos específicos de digitalización por tipo de autoridad. **Verificado en el expediente: alcaldías avanzadas, gobernaciones y distritos → hasta mayo/2028 (77 meses).** | Título 20 | **Sí** — es el cronograma aplicable a un distrito (`Sección 1 · §1.5.1`, `§1.5.2.4`) |
| **Decreto 1263 de 2022** (adiciona Título 22 al Decreto 1078/2015) | Lineamientos y estándares de Transformación Digital Pública; arquitectura de los servicios ciudadanos digitales. | Título 22 | **Sí** para interoperabilidad (`Sección 1 · §1.5.1`) |
| **Decreto 1078 de 2015** | DUR del sector TIC: base reglamentaria donde se adicionan los Títulos 17, 20 y 22. | — | **Sí** (`Sección 1 · §1.5.1`) |
| **Decreto 1875 de 2015, art. 2.2.17.1.2** | Ámbito de aplicación (referencia al PPT diap. 7). | art. 2.2.17.1.2 | Citado; **no consta** su contenido en el expediente. Ambigüedad: el art. 2.2.17.x corresponde al Decreto 1078/2015 (`Sección 5 · Referencias`) |
| **Ley 594 de 2000** (Ley General de Archivos) | Preservación documental; directrices del AGN. | — | **Sí** para el módulo de expedientes (INT-08, FUN-054) |
| **Ley 1273 de 2009** | Delitos informáticos; canal de reporte CSIRT. | — | **Sí** para incidentes (INT-14) |
| **CONPES 3701 de 2011** | Lineamientos de seguridad digital y defensa cibernética. | — | Citado como marco (`Sección 5 · Referencias`, INT-14) |
| **Ley 393 de 1997** | Acción de cumplimiento: cualquier persona puede exigir judicialmente el cumplimiento de la Ley 1712/2014. | arts. 1–5 | Riesgo por incumplimiento (`Sección 3 · §3.1.5`) |
| **Circular Externa 04 de 2019 (SIC)** | Tratamiento de datos personales en sistemas de información interoperables: documentar cada flujo y verificar base legal. | — | **Sí** para interoperabilidad (`Sección 4 · §4.5.5`) |
| **Resolución 0518 de 2020 (SFC)** | Reglas del canal de pago (PSE). | — | **Sí** para pagos (INT-03) |
| **Ley 2069 de 2020** | Economía digital; alternativa de botón de pagos/adquirentes. | — | **Sí** para pagos (INT-04) |
| **Ley 1753 de 2015, art. 133** | Reporte mensual al Comité Institucional de Gestión y Desempeño. | art. 133 | **Sí** — reporte mensual (`Sección 5 · §5.6.5`) |
| **Constitución Política** | arts. 15 (intimidad/habeas data), 20 (información), 209 (función administrativa), 269 (responsabilidad de servidores); arts. 365, 47, 48 (acceso a servicios públicos → daño antijurídico). | arts. 15, 20, 209, 269, 365, 47, 48 | Marco superior (`Sección 4 · Referencias N-09`, `Sección 3 · §3.1.5`) |
| **Directiva Presidencial "Colombia Potencia de la Vida" (31-may-2023)** | Uso de los colores ministeriales. | — | **Sí** para la paleta (`Sección 2 · Referencias`) |
| **Directiva 03 de 2019** | Cumplimiento adicional para entidades nacionales de la rama ejecutiva. | — | Citada en FUN-047; **no consta** su detalle ni si aplica a un distrito |
| **Estándares técnicos (no normativos colombianos pero exigidos por el expediente)** | WCAG 2.1 AA (W3C, jun-2018); WCAG 2.2 (5-oct-2023, recomendado); OWASP Top 10:2021 + Secure Headers + HTTP Headers Cheat Sheet; NIST SP 800-63B-4 (AAL2, contraseñas, revisión 4 vigente 2025-08-01); NIST SP 800-61r2; NIST SP 800-34; RFC 6797 (HSTS), RFC 6265 (cookies), RFC 8446 (TLS 1.3), RFC 7034 (XFO); ISO/IEC 27001:2022 (alineamiento, **no** certificación obligatoria); CIS Docker Benchmark; SLSA L3 | — | Según la fuente (`Sección 3 · §3.1.1`; `Sección 4 · §4.5.2`, `Referencias T-01…T-10`, `Guía Maestra Apéndice C.3`) |

---

## 2. Inventario de criterios de aceptación

### 2.1 Marco normativo — obligaciones derivadas `O-01…O-14`

Definidas en `Sección 1 · §1.4` (tabla obligación → requisito → implementación).

| ID | Enunciado | Fuente |
|---|---|---|
| O-01 | Integrar todos los activos digitales previos en un único dominio canónico por autoridad. | art. 14 inc. 1 |
| O-02 | Garantizar los seis atributos de calidad con checklist de aceptación por release. | art. 14 inc. 2 |
| O-03 | Publicar la sección "Atención al ciudadano" con PQRS, agendamiento de citas, horarios y canales. | art. 14 inc. 3 |
| O-04 | Integrar los Servicios Ciudadanos Digitales: autenticación digital, carpeta ciudadana, interoperabilidad. | arts. 9 y 10 |
| O-05 | Cruce oficioso en formularios: pre-relleno desde SCD; no pedir lo verificable. | art. 10 inc. 4 |
| O-06 | Implementar el mecanismo de redirección a/desde GOV.CO verificado por MinTIC (guía Res. 2893/2020 Anexo 2). | art. 15 inc. 3 |
| O-07 | SGDEA con integridad, disponibilidad y autenticidad: hash, firma digital y timestamp por documento. | art. 16 inc. 1 |
| O-08 | Pasarela de pagos integrada (PSE / botón de pagos) con conciliación diaria. | art. 17 |
| O-09 | Validación de identidad con la Registraduría (cédula digital / biometría). | art. 13 |
| O-10 | Emisión de certificados con verificación pública (QR + URL en línea + firma digital). | art. 19 |
| O-11 | Auditorías de accesibilidad automatizadas (axe-core / pa11y) en CI y auditoría manual semestral. | art. 14 inc. 2 → Res. 1519 Anexo 1 |
| O-12 | Modelo MSPI aplicado: diagnóstico, planificación, implementación, evaluación y mejora continua. | art. 16 par. |
| O-13 | Módulo de privacidad y derechos ARCO: registro de tratamientos, política, canal ARCO. | art. 10 inc. 3 → Ley 1581/2012 |
| O-14 | API REST documentada en OpenAPI 3.1 con contratos versionados y catálogo de servicios publicado. | art. 14 inc. 2 |

### 2.2 Requisitos técnicos `RT-xx` y marco normativo `RN-xx`

**Hallazgo:** `RN-01…RN-03` y `RT-01…RT-05` **nunca se definen en el expediente**; solo se referencian (`Sección 6 · §6.1`, `§6.2`, `§6.3`, `§6.5`, `§6.9`) y `docs/trazabilidad.md` los lista como criterios. Contenido reconstruible **solo** por su uso:

| ID | Uso documentado (no definición) | Fuente |
|---|---|---|
| RN-01 | Se usa como equivalente de "art. 14 DL 2106/2019" y de "5-10 trámites completos en v1". | `Sección 6 · §6.2` paso 0.2, `§6.3` paso 1.1, `§6.9` |
| RN-02 | Se usa para "firmar el acta de inicio con el sponsor". | `Sección 6 · §6.2` paso 0.4 |
| RN-03 | Mencionado en el criterio de salida de la Fase 1 ("RN-01/02/03 cubiertos") sin más definición. | `Sección 6 · §6.1` |
| RT-01 | Servicios Ciudadanos Digitales / autenticación vía Carpeta Ciudadana Digital. | `Sección 6 · §6.3` paso 1.4, `§6.5` S1 |
| RT-02 | **Dos significados distintos:** "Firma electrónica avanzada (escenario v2)" y "TLS 1.2/1.3". | `Sección 6 · §6.9` vs `docs/trazabilidad.md` |
| RT-03 | Sin definición; `docs/trazabilidad.md` lo marca **pendiente**. | `docs/trazabilidad.md` |
| RT-04 | "Cumplimiento" (pipeline, entornos, pruebas de carga, go-live). | `Sección 6 · §6.3` paso 1.5, `§6.7`, `§6.8` |
| RT-05 | Resolución 500/2021 (respuesta a incidentes, capacitación, handover). | `Sección 6 · §6.6` paso 4.6, `§6.8` |

> Colisión de códigos: `RN-0x` (marco normativo) coexiste con `SEG-002-RN-0x` ("requisito de red", `Sección 4 · §4.2.2`). Ver §9.

### 2.3 Criterios de diseño `CAG-01…CAG-34` (con umbral exacto)

Fuente: `Sección 2 · §2.4`. Severidad: 🔴 bloqueante · 🟠 mayor · 🟡 menor.

| ID | Enunciado resumido (umbral) | Sev. |
|---|---|---|
| CAG-01 | Los controladores del carrusel no se solapan ni se confunden con imágenes/fondos. | 🔴 |
| CAG-02 | Carrusel con indicadores de posición + flechas + controles reproducir/pausa (todos obligatorios). | 🔴 |
| CAG-03 | Carrusel: contraste ≥ **4.5:1** entre contenido y controles. | 🔴 |
| CAG-04 | Carrusel: imágenes con `alt`, `aria-label` o `aria-labelledby`. | 🟠 |
| CAG-05 | Barra superior: logo GOV.CO de **24 × 136 px**, color **#0943B5**, enlaza a `https://www.gov.co/home/`. | 🔴 |
| CAG-06 | Botón de cambio de idioma **24 × 24 px**, alineado a la derecha, `aria-label` corto, idioma persistente. | 🟠 |
| CAG-07 | Barra de accesibilidad con Aumentar letra · Reducir letra · Contraste; **oculta entre 768 y 992 px**. | 🔴 |
| CAG-08 | Cabecera: "Saltar al contenido principal" (`sr-only sr-only-focusable` → `#contenido-principal`); obligatorio en Sedes y Trámites. | 🔴 |
| CAG-09 | Menú: **máx. 7 ítems principales** y **máx. 4 secciones internas** por ítem. | 🔴 |
| CAG-10 | Menú: `aria-label` en el menú principal, navegación completa por teclado, contraste de foco **≥ 4.5:1**. | 🔴 |
| CAG-11 | Miga de pan en todas las secciones excepto Home; versión invertida solo sobre fondo oscuro. | 🔴 |
| CAG-12 | Pie: contraste mínimo **4.5:1**; logo autoridad + GOV.CO + Colombia-CO (+ "Colombia, potencia de la vida" cuando aplique). | 🔴 |
| CAG-13 | Botones: `<button type="button">` con `aria-label` si solo llevan ícono; estados `:hover` y `:focus` distintos. | 🔴 |
| CAG-14 | Botones deshabilitados: atributo `disabled` y fuera del orden de tabulación. | 🟠 |
| CAG-15 | Buscador: placeholder claro, permitir borrar el contenido, navegable por teclado. | 🟠 |
| CAG-16 | Campos: asterisco `*` para obligatorios + leyenda inicial; `<label for>`. | 🟠 |
| CAG-17 | Entradas de texto: borde con ratio mínimo de contraste; 6 estados (Default/Active/Focus/Disabled/Valid/Invalid); `autocomplete`. | 🟠 |
| CAG-18 | Desplegable-lista: **máx. 5 elementos visibles** antes de scroll. | 🟡 |
| CAG-19 | Calendario con el estilo de la sección Desplegables. | 🟡 |
| CAG-20 | Línea de avance: contraste de foco **≥ 4.5:1**; permitir saltar pasos o bloquearlos según obligatoriedad. | 🟠 |
| CAG-21 | Alerta modal: cierra con **Esc** y clic fuera; no anidar; **máx. 1 modal simultáneo**. | 🔴 |
| CAG-22 | Toast: duración suficiente para lectura + cierre automático programado por la entidad. | 🟠 |
| CAG-23 | Paginación: tamaño mínimo **44 × 44 px** en móvil; `aria-current` en la página activa. | 🟠 |
| CAG-24 | Tablas: números alineados a la derecha; sin scroll horizontal en responsive. | 🟡 |
| CAG-25 | Acordeón: `aria-expanded` obligatorio; **no abrir automáticamente** con foco; jerarquía de encabezados. | 🔴 |
| CAG-26 | Tarjeta de información: encerrada en `<a>` o `<button>`; **máx. 2 palabras** en el título (CTA). | 🟠 |
| CAG-27 | Tipografía: nunca justificar; **45–75 caracteres** por línea; unidades relativas. | 🟡 |
| CAG-28 | Color: contraste **4.5:1** (texto normal) y **3:1** (texto grande). | 🔴 |
| CAG-29 | Indicador de carga: si **> 10 s**, informar el progreso al usuario. | 🟠 |
| CAG-30 | Volver arriba en la esquina inferior derecha, con foco visible al tabular. | 🟡 |
| CAG-31 | Galería de aplicaciones: recorrido por teclado izquierda→derecha y arriba→abajo; abre con `Enter`, cierra con `Esc`. | 🟠 |
| CAG-32 | Todos los componentes: cumplir **Resolución 1519 de 2020** y **WCAG 2.1 AA**. | 🔴 |
| CAG-33 | Construir sobre **Bootstrap 5.0.2** y los tokens `--govcolor-*` de `src/all.css`. | 🔴 |
| CAG-34 | Servir el frontend desde el **CDN oficial v5** o desde copia local del repo. | 🟠 |

Reglas duras adicionales de paleta/tipografía (no numeradas como CAG): RF-PAL-01…05 (contraste 4.5:1 / 3:1; sustitución por `#4C4C4C` si el color institucional no cumple), RF-TIP-01…05 (45–75 caracteres, nunca justificar, unidades relativas), RF-ICO-01…05 (íconos múltiplos de 4 px, escalables, accesibles por teclado) — `Sección 2 · §2.2.1`, `§2.2.2`, `§2.2.4`.

### 2.4 Accesibilidad `ACC-xxx`

**Hallazgo crítico:** la `Sección 3` **no asigna ningún código `ACC-NNN`**; usa la numeración WCAG. Los códigos `ACC-*` solo aparecen referenciados en `Sección 6` (`ACC-001…ACC-008`) y en `docs/trazabilidad.md` (ACC-001, 002, 003, 004, 007, 008), **sin enunciado definido**. La Guía Maestra, Apéndice B, declara "`ACC-NN` (WCAG) — 49 [F] + 75 [A]" = 124 criterios, cifra que no coincide con ninguna lista del expediente.

Códigos `ACC-*` con único uso documentado:

| ID | Uso documentado | Fuente |
|---|---|---|
| ACC-001 | Contraste, foco visible, tamaños táctiles (auditoría WCAG + checklist de mockups). | `Sección 6 · §6.4` paso 2.7, `§6.6` paso 4.1, `§6.5` S8 |
| ACC-002 | Barra de accesibilidad (Aumentar/Reducir/Contraste). | `Sección 6 · §6.4` paso 2.4 |
| ACC-003 | Auditoría WCAG 2.2 (foco no oculto, arrastrar alternativo, reautenticación, **objetivo táctil 24×24 px**). | `Sección 6 · §6.6` paso 4.2 |
| ACC-004 | Contraste y foco (junto a ACC-001). | `Sección 6 · §6.4` paso 2.7, `§6.6` paso 4.1 |
| ACC-007 | Soporte multi-idioma (**Lengua de señas, lenguas indígenas**). | `Sección 6 · §6.9` (Should). Nota: en `§6.9` aparece como "ACC-007" y en `§6.5` S8 se citan "ACC-001…ACC-008"; ACC-005 y ACC-006 no se mencionan en ninguna parte |
| ACC-008 | Cierre de la Fase 3 (accesibilidad lista para auditoría). | `Sección 6 · §6.5` S8 |

**Criterios de accesibilidad realmente especificados** (nivel y umbral), `Sección 3 · §3.3.1` y `§3.3.2`:

| Criterio WCAG | Nivel | Umbral / exigencia clave |
|---|---|---|
| 1.1.1 Non-text Content | A | `alt` obligatorio; en imágenes informativas máximo **150 caracteres** (`§3.3.1`, `§3.4.3`) |
| 1.2.1 Audio-only / Video-only (pregrabado) | A | Transcripción |
| 1.2.2 Captions (pregrabado) | A | Subtítulos `.vtt` |
| 1.2.3 Audio Description or Media Alternative | A | Audiodescripción o alternativa |
| 1.2.4 Captions (Live) | AA | Subtítulos en vivo |
| 1.2.5 Audio Description (pregrabado) | AA | Pista `<track kind="descriptions">` |
| 1.3.1 Info and Relationships | A | Estructura programática (encabezados, listas, `th scope`) |
| 1.3.2 Meaningful Sequence | A | Orden DOM = orden visual |
| 1.3.3 Sensory Characteristics | A | No referirse solo a forma/posición/color |
| 1.3.4 Orientation | AA | No bloquear portrait ni landscape |
| 1.3.5 Identify Input Purpose | AA | `autocomplete` con tokens estándar |
| 1.4.1 Use of Color | A | Color no como único medio |
| 1.4.2 Audio Control | A | Control de pausa si hay audio autoplay > 3 s |
| 1.4.3 Contrast (Minimum) | AA | **4.5:1** texto normal; **3:1** texto grande |
| 1.4.4 Resize Text | AA | Texto agrandable al **200 %** |
| 1.4.5 Images of Text | AA | Evitar texto en imágenes |
| 1.4.10 Reflow | AA | Sin scroll bidimensional a **320 CSS px**; zoom **400 %** sin pérdida |
| 1.4.11 Non-text Contrast | AA | Componentes UI y gráficos **≥ 3:1** |
| 1.4.12 Text Spacing | AA | Soporta `line-height:1.5; letter-spacing:0.12em; word-spacing:0.16em` |
| 1.4.13 Content on Hover or Focus | AA | Tooltip descartable, hoverable y persistente |
| 2.1.1 Keyboard | A | Toda funcionalidad operable por teclado |
| 2.1.2 No Keyboard Trap | A | El foco entra y sale (escape con `Esc`) |
| 2.1.4 Character Key Shortcuts | AA | Atajos de un carácter configurables/desactivables |
| 2.2.1 Timing Adjustable | A | Sesión de 30 min con extensión (2 veces) |
| 2.2.2 Pause, Stop, Hide | A | Carrusel pausa en hover y focus |
| 2.3.1 Three Flashes | A | Sin flashes > 3 Hz |
| 2.4.1 Bypass Blocks | A | Primer enlace: "Saltar al contenido principal" |
| 2.4.2 Page Titled | A | `<title>` descriptivo por página |
| 2.4.3 Focus Order | A | Orden de foco = orden de lectura |
| 2.4.4 Link Purpose (In Context) | A | Sin "Ver más"/"Clic aquí" aislados |
| 2.4.5 Multiple Ways | AA | ≥ 2 vías de localización (menú + buscador + miga) |
| 2.4.6 Headings and Labels | AA | Un solo `h1`, sin saltos de nivel |
| 2.4.7 Focus Visible | AA | `:focus-visible` con outline **≥ 2 CSS px** |
| 2.5.1 Pointer Gestures | A | Alternativa a gestos complejos |
| 2.5.2 Pointer Cancellation | A | Disparo en `up`, no en `down` |
| 2.5.3 Label in Name | A | Texto visible incluido en el nombre accesible |
| 2.5.4 Motion Actuation | A | Alternativa al movimiento del dispositivo |
| 3.1.1 Language of Page | A | `lang="es"` (o `es-CO`) en `<html>` |
| 3.1.2 Language of Parts | AA | `lang` en fragmentos en otro idioma |
| 3.2.1 On Focus | A | El foco no cambia contexto |
| 3.2.2 On Input | A | El input no cambia contexto |
| 3.2.3 Consistent Navigation | AA | Menú en el mismo orden y posición |
| 3.2.4 Consistent Identification | AA | Mismo ícono/etiqueta para la misma función |
| 3.3.1 Error Identification | A | Error en texto, no solo en color |
| 3.3.2 Labels or Instructions | A | `<label for>` o `aria-label` + instrucciones |
| 3.3.3 Error Suggestion | AA | Sugerencia concreta de corrección |
| 3.3.4 Error Prevention (Legal, Financial, Data) | AA | Confirmación/reversión/revisión |
| 4.1.1 Parsing | A | HTML5 válido (obsoleto en WCAG 2.2) |
| 4.1.2 Name, Role, Value | A | Componentes con nombre, rol y valor programáticos |
| 4.1.3 Status Messages | AA | `aria-live="polite"`/`assertive` en toasts |
| **WCAG 2.2 — recomendado** | 2.4.11 AA, 2.5.7 AA, **2.5.8 AA (objetivo 24×24 CSS px)**, 3.2.6 A, 3.3.7 A, 3.3.8 AA (+ 2.4.12/2.4.13/3.3.9 AAA) | `§3.1.4` |
| **WCAG AAA — aspiracional, no obligatorio** | 1.4.6 (**7:1** normal / **4.5:1** grande), **2.5.5 (44×44 px)**, 2.2.6 (avisar 20 s antes), etc. | `§3.3.3` |

### 2.5 Funcionalidad `FUN-001…FUN-060`

Fuente: `Sección 5 · §5.3.1` (literales del PDF) y `§5.3.2` (derivados del PPT; **[A]** = inferencia técnica/normativa del autor del expediente).

| ID | Enunciado en una línea | Origen |
|---|---|---|
| FUN-001 | El buscador debe buscar **dentro de la Sede**; prohibido un buscador de Google. | PDF c.1 (Crítica) |
| FUN-002 | Los vínculos no deben estar rotos y deben direccionar a las páginas correctas. | PDF c.2 (Alta) |
| FUN-003 | Los enlaces a páginas externas deben abrirse en nueva pestaña (`target="_blank"` + `rel="noopener noreferrer"`). | PDF c.3 (Media) |
| FUN-004 | Todo formulario que capture datos personales requiere aviso de privacidad, autorización de tratamiento y **Captcha**. | PDF c.4 (Crítica) |
| FUN-005 | Los campos obligatorios deben cumplirlo y los vínculos de políticas deben enlazar a (A) Términos y condiciones, (B) Privacidad y tratamiento de datos, (C) Derechos de autor/uso de contenidos. | PDF c.5 (Alta) |
| FUN-006 | Si el usuario no diligencia los campos, debe presentarse el mensaje de error correspondiente. | PDF c.6 (Alta) |
| FUN-007 | Dirección electrónica pública estable (no IP) que identifique unívocamente la sede. | PPT 8 |
| FUN-008 | Contenidos en castellano; otros idiomas opcionales conforme a Ley 1712/2014. | PPT 8 |
| FUN-009 | Titularidad, administración y gestión a cargo de la entidad (no de un tercero sin acto administrativo). | PPT 8 |
| FUN-010 | Barra superior obligatoria: logo gov.co, enlace al portal gov.co y enlaces de traducción. | PPT 9 |
| FUN-011 | Encabezado: logo de la entidad enlazado a inicio, buscador general y enlace de inicio de sesión (opcional). | PPT 9 |
| FUN-012 | Menú principal con **máximo 7 opciones** y desplegables de **máximo 2 niveles**. | PPT 10 |
| FUN-013 | Tres secciones obligatorias: Transparencia y acceso a la información pública, Servicios a la Ciudadanía y Participa. | PPT 10 |
| FUN-014 | Pie de página con logo gov.co, marca país CO–Colombia, datos completos de la entidad, redes sociales, mapa del sitio y enlace a políticas. | PPT 14 |
| FUN-015 | Enlazar las **tres** políticas obligatorias: Términos y condiciones, Privacidad y tratamiento de datos, Derechos de autor/uso de contenidos. | PPT 15 |
| FUN-016 | Transparencia debe garantizar integridad, calidad, accesibilidad y disponibilidad de la información. | PPT 11 |
| FUN-017 | Transparencia debe tener **buscador propio** dentro de la sección. | PPT 11 |
| FUN-018 | Formatos publicados accesibles y descargables/uso sin restricciones. | PPT 11 |
| FUN-019 | Toda información lleva **fecha de publicación** y se ordena del más reciente al más antiguo. | PPT 11 |
| FUN-020 | Evitar la duplicidad de información entre secciones. | PPT 11 |
| FUN-021 | Por cada trámite indicar modalidad (en línea/parcialmente en línea/presencial), costo (gratuito/con costo) y tiempo de solución. | PPT 12 |
| FUN-022 | El catálogo de trámites debe incluir consulta, criterios de búsqueda y paginación. | PPT 12 |
| FUN-023 | Los trámites en línea o parcialmente en línea deben direccionar a **gov.co** para su ejecución. | PPT 12 |
| FUN-024 | Mecanismos de consulta del estado del trámite por radicado. | PPT 12 |
| FUN-025 | Acceso a Trámites, OPAs, consulta de acceso a información pública, ventanillas únicas, canales de atención y PQRSD. | PPT 12 |
| FUN-026 | Noticias más relevantes en la página principal + enlace al archivo histórico. | PPT 13 |
| FUN-027 | Portales de programas transversales enlazados desde la sede. | PPT 13 |
| FUN-028 | Noticias con lenguaje claro, accesibilidad y usabilidad. | PPT 13 |
| FUN-029 | Todo formulario con datos personales: aviso de privacidad visible + casilla de autorización (Ley 1581/2012). | PDF c.4 + [A] |
| FUN-030 | Los formularios deben incluir Captcha. | PDF c.4 |
| FUN-031 | Campos obligatorios marcados visualmente (asterisco + "obligatorio") y validados en cliente y servidor. | PDF c.5 |
| FUN-032 | Ante campos vacíos o con error de formato, mensaje claro asociado al campo y accesible (`aria-describedby`). | PDF c.6 |
| FUN-033 | Enlaces a Términos y condiciones, Privacidad y Derechos de autor presentes en todos los formularios donde aplique. | PDF c.5 |
| FUN-034 | Sistema de gestión de cookies que permita aceptar, denegar o revocar el consentimiento. | PPT 15 |
| FUN-035 | Consulta del estado de trámites/solicitudes mediante radicado. | PPT 12 |
| FUN-036 | Descarga de documentos asociados a cada trámite (radicado, respuesta, actos administrativos). | [A] |
| FUN-037 | Notificación al ciudadano en al menos un canal (correo) ante radicación, cambio de estado y respuesta final. | PPT 14 |
| FUN-038 | Bandeja de notificaciones interna accesible desde el panel del ciudadano. | [A] |
| FUN-039 | Actos administrativos con notificación legal enviados por **correo certificado** o equivalente con acuse (Ley 1437/2011 art. 56, sustituido por Ley 2080/2021). | [A] |
| FUN-040 | Trámites con costo: integrar pasarela certificada (PSE / botón de pagos); **nunca** capturar medios de pago directamente. | [A] PCI-DSS |
| FUN-041 | El comprobante de pago debe quedar almacenado y ser consultable desde el panel del ciudadano. | [A] |
| FUN-042 | Conciliar el estado del pago con la pasarela mediante **webhook idempotente**. | [A] |
| FUN-043 | El panel muestra el listado de todos los trámites del ciudadano autenticado con su estado actual. | [A] |
| FUN-044 | El panel permite actualizar datos de contacto (correo, celular) y gestionar la suscripción a notificaciones. | [A] |
| FUN-045 | El panel permite descarga masiva de documentos y radicados en formatos abiertos (CSV/JSON/PDF). | [A] |
| FUN-046 | El panel muestra historial de notificaciones recibidas con acuse. | [A] |
| FUN-047 | Cumplir la guía de diseño del Kit UI 9.2 (entidades nacionales de la rama ejecutiva, además, Directiva 03/2019). | PPT 17 |
| FUN-048 | Lenguaje claro en todos los contenidos visibles al ciudadano. | PPT 17 |
| FUN-049 | Estilos separados del contenido (CSS externo, sin estilos inline críticos). | PPT 17 |
| FUN-050 | Independencia del navegador; operable en las últimas 2 versiones de Chrome, Firefox, Edge y Safari. | PPT 17 |
| FUN-051 | Versión responsive adecuada en dispositivos móviles. | PPT 17 |
| FUN-052 | Encuesta de usabilidad visible para el ciudadano. | PPT 17 |
| FUN-053 | Disponibilidad de la Sede **igual o superior al 95 %**. | PPT 19 |
| FUN-054 | Mecanismos de preservación documental definidos por el **AGN**. | PPT 19 |
| FUN-055 | Planes de contingencia ante vulnerabilidades. | PPT 20 |
| FUN-056 | Plan de respaldo y copias de seguridad + sistema de control de versiones. | PPT 20 |
| FUN-057 | Manejo de errores que no exponga información técnica sensible al ciudadano. | PPT 20 |
| FUN-058 | Información publicada actualizada, veraz, oportuna y completa. | PPT 19 |
| FUN-059 | Archivos de libre uso, bajo licencia abierta, sin restricciones legales y en formatos de datos abiertos. | PPT 19 |
| FUN-060 | Certificado SSL vigente y HTTPS forzado en todas las páginas. | PPT 20 |

Grupos funcionales obligatorios B1–B6 (`§5.1.1`): B1 contenido e información institucional · B2 trámites, OPAs y servicios · B3 acceso a la información pública (Ley 1712) · B4 participación ciudadana · B5 PQRSD · B6 ventanillas únicas y canales de atención.

### 2.6 Seguridad `SEG-001…SEG-018`

Fuente: `Sección 4 · §4.3` (SEG-001 a SEG-012, texto literal del PDF MinTIC/AND 2022) y `§4.3.1` (SEG-013 a SEG-018, marcados `[A]` = derivados de OWASP/MSPI/NIST).

| ID | Enunciado en una línea | Severidad |
|---|---|---|
| SEG-001 | Certificado **SSL** debidamente instalado y configurado. | Crítica |
| SEG-002 | Validar frecuentemente el uso y la exposición de **puertos abiertos** a internet, correctamente filtrados. | Alta |
| SEG-003 | Control tipo **CAPTCHA** en todos los formularios donde se capturan datos de la ciudadanía. | Alta |
| SEG-004 | Control de **tasa de reintentos por login fallido** para evitar fuerza bruta y DDoS. | Crítica |
| SEG-005 | Validar los flags **HttpOnly** y **Secure** en el uso de cookies. | Crítica |
| SEG-006 | Sección en el footer con la documentación de cumplimiento de **cinco políticas**: (a) Términos y condiciones de uso, (b) Seguridad y Privacidad, (c) Protección y tratamiento de datos personales, (d) Uso de Cookies, (e) Derechos de Autor y uso sobre contenidos. | Alta |
| SEG-007 | Deshabilitar métodos HTTP peligrosos: **PUT, DELETE, TRACE, OPTIONS**. | Alta |
| SEG-008 | Restringir la **escritura de archivos** en el servidor web (solo lectura). | Alta |
| SEG-009 | **Sanitización de parámetros de entrada** (etiquetas, saltos de línea, espacios, caracteres especiales). | Crítica |
| SEG-010 | **Sanitización de caracteres especiales** (secuencia de escape de variables en el código). | Crítica |
| SEG-011 | **Mensajes de error genéricos** que no revelen tecnología, excepciones ni parámetros. | Media |
| SEG-012 | **Cabeceras de seguridad**: CSP, X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, HSTS, HPKP, Referrer-Policy, Feature-Policy, y cookies `secure` + `HttpOnly`. | Alta |
| SEG-013 `[A]` | Logs centralizados con correlación y retención mínima de **12 meses** (autenticación, acceso a datos personales, cambios de configuración). | Alta |
| SEG-014 `[A]` | Copias de seguridad cifradas con **prueba de restauración trimestral** (RPO ≤ 24 h, RTO ≤ 4 h). | Alta |
| SEG-015 `[A]` | Escaneo de vulnerabilidades automatizado en CI (**SCA + SAST + DAST**) antes de cada despliegue a producción. | Alta |
| SEG-016 `[A]` | **MFA obligatoria** para funcionarios con acceso a datos personales o funciones administrativas. | Crítica |
| SEG-017 `[A]` | Imagen base de contenedor **firmada (cosign)** y **SBOM** publicado por release. | Media |
| SEG-018 `[A]` | Endpoints `/api/health` y `/api/ready` sin datos sensibles, accesibles **solo desde la red interna**. | Media |

Requisitos técnicos por capa derivados (no son criterios de aceptación pero son exigibles): `SEG-001-RT-01…05`, `SEG-002-RN-01…03`, `SEG-003-RA-01`, `SEG-004-RA-01…06`, `SEG-009-RA-01/04`, `SEG-010-RA-01…03`, `SEG-011-RA-01…04`, `SEG-007-RH-01`, `SEG-008-RH-01…03`, `SEG-006-RC-01…03`, `SEG-BAK-01…06` (`Sección 4 · §4.2`).

---

## 3. Entidad titular

### 3.1 Campos exigidos por FUN-014 (pie de página)

Transcripción literal de los campos exigidos (`Sección 5 · §5.3.2.1`, FUN-014):

1. logo gov.co;
2. marca país **CO–Colombia**;
3. **datos completos de la entidad**: **nombre, dirección, código postal, teléfono, línea gratuita, línea anticorrupción, correo de atención al usuario, correo de notificaciones judiciales**;
4. **redes sociales**;
5. **mapa del sitio**;
6. **enlace a políticas**.

Y en la cabecera (FUN-010, FUN-011, CAG-05, CAG-06): logo gov.co + enlace al portal gov.co (+ enlaces de traducción), logo de la entidad enlazado a inicio, buscador general, enlace de inicio de sesión (opcional) y botón de idioma 24 × 24 px.

Versión del pie según número de sedes: **A = 1 a 3 sedes · B = más de 3 sedes** (`Sección 2 · §2.3.1`; `Sección 6 · §6.12.5` regla 6).

### 3.2 Las cinco políticas obligatorias del pie (SEG-006)

Texto literal de SEG-006 (`Sección 4 · §4.3`):

> a. Términos y condiciones de uso. b. Seguridad y Privacidad. c. Protección y tratamiento de datos personales. d. Uso de Cookies. e. Derechos de Autor y uso sobre contenidos.

Reglas asociadas (`Sección 4 · §4.2.8`): cada enlace debe resolver a un documento PDF/HTML accesible y versionado, con **fecha de última actualización** y **acto administrativo de adopción** (SEG-006-RC-02); la política de tratamiento debe cumplir los arts. 17 y 18 de la Ley 1581/2012 y estar inscrita en el **RNBD** ante la SIC (SEG-006-RC-03). Verificación: los 5 enlaces deben responder **HTTP 200** (`§4.7.8`).

> **Contradicción documentada:** FUN-015 (y FUN-005 / FUN-033) exigen solo **tres** políticas (Términos, Privacidad y tratamiento de datos, Derechos de autor), mientras SEG-006 exige **cinco**. Ver §9.

### 3.3 Datos de la Alcaldía Distrital de Santa Marta (ADR-0006)

Entidad titular: **Alcaldía Distrital de Santa Marta (Distrito Turístico, Cultural e Histórico)**. Datos centralizados en `backend/config/entidad.php`, expuestos por `GET /api/v1/entidad`, con los valores por ratificar en la clave `datos_por_confirmar` (`docs/adr/README.md`, ADR-0006).

| Campo FUN-014 | Valor documentado en ADR-0006 | Observación |
|---|---|---|
| Nombre | Alcaldía Distrital de Santa Marta (Distrito Turístico, Cultural e Histórico) | — |
| NIT | 891780009 | No es campo de FUN-014; documentado igualmente |
| Dirección | Calle 14 No. 2-49, Palacio Municipal | — |
| Código postal | **no consta** | Exigido por FUN-014 |
| Teléfono / PBX | (+57) 605 420 9600 (PBX); línea de atención (+57) 605 4351719 | — |
| Línea gratuita | 01-8000-955-532 | — |
| Línea anticorrupción | **no consta** | Exigido por FUN-014 |
| Correo de atención al usuario | atencionalciudadano@santamarta.gov.co | — |
| Correo de notificaciones judiciales | notificacionesalcaldiadistrital@santamarta.gov.co | — |
| Redes sociales | **no consta** en el expediente | Exigido por FUN-014 |
| Mapa del sitio | Existe la vista `MapaSitioView.vue` en la implementación (`docs/trazabilidad.md`, FUN-014), pero el expediente no fija su contenido | — |
| Horario de atención | L–V 8:00–12:00 y 14:00–18:00 | Dato de `santamarta.gov.co` citado en ADR-0006 |

Fuente declarada: `santamarta.gov.co` (pie de página y página de localización física) — `docs/adr/README.md`, ADR-0006, «Evidencia».

---

## 4. Módulos funcionales del CMS / sede

Base: bloques B1–B6 (`Sección 5 · §5.1.1`), los 60 criterios FUN, el mapa de páginas (`Sección 2 · §2.5`), las 6 políticas `[A]` de `Sección 5 · §5.5`, los ADR y `docs/trazabilidad.md`. Se indica expresamente cuando un módulo **no consta** en el expediente.

### M1. Contenido e información institucional (B1)
- **Qué exige:** páginas estáticas, noticias, misión/visión, estructura orgánica, normatividad interna (`§5.1.1` B1).
- **Atributos/reglas:** flujo editorial **borrador → revisión → programado → publicado → archivado**; **versionado con restauración**; biblioteca de medios; taxonomías; metadatos; publicación programada; registro de auditoría; publicación exige **ficha completa** y fija la **próxima revisión** (`FUN-058`; `docs/adr/README.md` ADR-0009; `docs/trazabilidad.md` FUN-058 `ContenidoVigenciaTest`).
- **Calidad:** contenido versionado, metadatos, control de cambios, métricas de calidad de datos (atributo *Calidad*, `Sección 1 · §1.2.7`).

### M2. Trámites y servicios (B2) — catálogo y ficha
- **Atributos obligatorios:** los **seis atributos** de FUN-021/§5.1.3 (ver §5 de este informe).
- **Reglas:** consulta, criterios de búsqueda y **paginación** (FUN-022); los trámites en línea/parcialmente en línea **direccionan a gov.co** (FUN-023); mecanismos de consulta del estado **por radicado** (FUN-024); catálogo navegable con ficha, requisitos y botón "Iniciar trámite" (`§5.1.1` B2); ficha con **línea de avance** horizontal y vertical + pestañas + área de servicio (`Sección 2 · §2.5`, CAG-20); métrica de conformidad: **0 trámites sin modalidad/costo/tiempo declarados** (`§5.6.4`).
- **Evidencia de implementación:** `GET /tramites`, `GET /tramites/{slug}`, `GET /categorias-tramite` (`docs/trazabilidad.md`).

### M3. PQRSD (B5)
- **Qué exige:** formulario **tipificado**, radicado, consulta de estado, notificación (`§5.1.1` B5); acceso desde la sección de Servicios a la Ciudadanía (FUN-025).
- **Atributos/reglas:** aviso de privacidad visible + **casilla de autorización** obligatoria (FUN-004, FUN-029); **Captcha** no reutilizable (FUN-030, SEG-003); campos obligatorios marcados y validados en cliente y servidor (FUN-031); errores asociados al campo con `aria-describedby`/`aria-invalid` (FUN-032); enlaces a las políticas (FUN-033); **acuse inmediato ≤ 1 minuto** y respuesta **≤ 15 días hábiles** (`§5.6.2`).
- **Evidencia de implementación:** `POST /pqrsd`, `GET /pqrsd/tipos`, `GET /pqrsd/{numero}`, `GET /mis-pqrsd` (`docs/trazabilidad.md`).

### M4. Radicación y expedientes electrónicos
- **Qué exige:** conformar **expedientes electrónicos** por cada trámite/procedimiento, con **integridad, disponibilidad y autenticidad** (art. 16 DL 2106, O-07); comunicaciones oficiales de entrada y salida con tratamiento archivístico; aplicar **TRD/TVD del AGN**; firma digital + **timestamp**; cadena de custodia auditable con logs inmutables (`Sección 1 · §1.2.9`).
- **Reglas:** numeración de radicado (UUID) y consulta pública; verificación pública de certificados por **QR + URL** (art. 19, O-10); preservación AGN (FUN-054); retención descontada **desde el cierre** del expediente, con serie documental obligatoria y disposición final (`docs/trazabilidad.md` FUN-054, `ExpedientePreservacionTest`).
- **Evidencia de implementación:** `POST /radicados`, `GET /mis-radicados`, `GET /mis-documentos`, `GET /verificar/{codigo}`, `GET /seguimiento`.

### M5. Notificaciones
- **Canales y obligatoriedad (`§5.2.5`):** correo electrónico **obligatorio**; SMS **recomendado**; push **opcional**; **correo certificado con acuse obligatorio para actos administrativos** (INT-06, FUN-039); **bandeja interna obligatoria** en el panel (FUN-038).
- **Disparadores:** radicación, cambio de estado, respuesta final (FUN-037).
- **Reglas:** notificación por correo **≤ 2 minutos** desde el evento y cambio de estado visible en panel **≤ 5 minutos** (`§5.6.2`); webhook de acuse **idempotente** (no duplicar constancia); constancia consultable por el titular sin acceso de terceros; el ciudadano **no puede desactivar los canales obligatorios** (`docs/trazabilidad.md` FUN-037/038 `PanelCiudadanoTest`); preferencias de canal y horario (FUN-044).

### M6. Pagos
- **Qué exige:** habilitar medios de pago electrónicos para las tarifas de trámites (art. 17, O-08); la sede **no procesa pagos directamente**: integra con pasarela certificada y **nunca** captura datos de tarjeta (FUN-040, PCI-DSS).
- **Atributos/reglas:** orden de pago con **referencia** y monto; **el valor lo determina el catálogo y no la petición**; webhook **idempotente** (FUN-042); **comprobante almacenado y consultable** (FUN-041); confirmación visible en el panel; callback `estado=PAGADO` al tramitador; conciliación diaria contra el sistema contable (`Sección 1 · §1.2.10`, O-08); **un trámite gratuito no genera orden de pago** (`docs/trazabilidad.md` FUN-040).
- **Evidencia de implementación:** `POST /pagos`, `GET /mis-pagos`, `GET /pagos/{referencia}`, `POST /webhooks/pse`.

### M7. Transparencia y acceso a la información pública (B3)
- **Qué exige:** garantizar integridad, calidad, accesibilidad y disponibilidad de la información (FUN-016); **buscador propio de la sección** (FUN-017) que ordene del más reciente al más antiguo (FUN-019); formatos accesibles y descargables **sin restricciones** (FUN-018); **evitar duplicidad** entre secciones (FUN-020); sección obligatoria visible (FUN-013).
- **Atributos/reglas:** fecha de publicación obligatoria; solo contenido **publicado y vigente**; **Declaración de Conformidad de Accesibilidad Web (WCAG 2.1 AA)** como contenido obligatorio del botón de transparencia (Res. 1519/2020 Anexo 1, numeral 9.3, `Sección 3 · §3.6.3`); estándares de divulgación de la Res. 1519/2020.
- **Evidencia de implementación:** `GET /transparencia`, `GET /contenidos/{tipo}`, `GET /contenidos/{tipo}/{slug}`.

### M8. Participación ciudadana (B4)
- **Qué exige:** noticias (destacadas en inicio + archivo histórico, FUN-026), **portales de programas transversales** (FUN-027), encuestas, consultas y control social (`§5.1.1` B4); noticias con lenguaje claro, accesibilidad y usabilidad (FUN-028).
- **Evidencia de implementación:** `GET /participacion/portales`; vistas `ParticipaView`, `NoticiasView`.

### M9. Datos abiertos
- **Qué exige:** los archivos dispuestos deben permitir **libre uso, bajo licencia abierta, sin restricciones legales y en formatos de datos abiertos** (FUN-059); federación al portal **datos.gov.co** y planificación de datasets (Res. 1519/2020 Anexo 4; decisión de proyecto en `Sección 1 · §1.5.2.6`); descarga masiva en CSV/JSON/PDF (FUN-045); formatos accesibles y descargables (FUN-018).
- **Reglas:** licencia declarada por la entidad y **la licencia del contenido manda sobre la de la entidad** (`docs/trazabilidad.md` FUN-059).

### M10. Panel de autogestión del ciudadano ("Carpeta ciudadana" / "Mi Sede")
Componentes mínimos (`Sección 5 · §5.5.1`): Resumen · Mis Trámites · Documentos y radicados · Bandeja de notificaciones · Gestión de datos personales y consentimiento · Preferencias de notificación (canal y horario) · Historial de pagos · Mis PQRSD · Opciones de accesibilidad (idioma, contraste, texto).
Reglas de privacidad (`§5.5.3`): **portabilidad** de todos sus datos en formato abierto (Ley 1581/2012 art. 13 y Decreto 1377/2013); **supresión** cuando no exista obligación legal de conservarlos; todo cambio de datos de contacto **re-autentica (MFA)** y deja **trazabilidad** (quién, cuándo, desde qué IP); respeto estricto de la **finalidad** declarada.

### M11. Usuarios, roles y auditoría
- **Qué exige el expediente:** **MFA obligatoria** para funcionarios con acceso a datos personales o funciones administrativas (SEG-016, severidad Crítica); bloqueo de cuenta tras fallos (SEG-004); **segregación de funciones**, registros de auditoría y rotación de credenciales (`Sección 4 · §4.1.3`); logs centralizados con retención ≥ 12 meses (SEG-013); los logs **no contienen** datos personales en claro ni secretos (SEG-011-RA-04); solo funcionarios activos administran la sede y una cuenta de funcionario nunca se vincula a una identidad ciudadana (`docs/trazabilidad.md` SEG-016, O-04).
- **Qué aporta la implementación, no el expediente:** roles y permisos con Spatie, panel `/admin`, flujo editorial, registro de auditoría (`docs/adr/README.md` ADR-0009). El expediente **no define** una taxonomía de roles ni permisos: **no consta**.

### M12. Menús y navegación
- **Atributos/reglas:** máximo **7 ítems principales**; máximo **4 secciones internas** por ítem (CAG-09) o "**máximo 2 niveles**" (FUN-012 / §5.1.2); `aria-label` en el menú principal; navegación completa por teclado; contraste de foco ≥ 4.5:1 (CAG-10); mismo orden y posición en todas las páginas (WCAG 3.2.3); menú principal + buscador + miga de pan como **múltiples vías** (WCAG 2.4.5).
- **Evidencia de implementación:** `GET /menus/{ubicacion}`.

### M13. Bloques de inicio
- **El expediente no define un tipo de contenido "bloques".** Lo que exige es la **presencia de ítems visuales obligatorios** en la página principal (`§5.1.2`): barra superior con logo gov.co y enlaces de traducción; encabezado con logo, buscador y login; menú ≤ 7 opciones; secciones Transparencia, Servicios a la Ciudadanía y Participa; pie de página con datos y políticas.
- **Componentes del Home** (`Sección 2 · §2.5`): Carrusel · Tarjeta de información · Buscador · Galería de aplicaciones · Botones · Etiquetas; el carrusel puede ser "sin texto" o "múltiple" (Kit UI pág. 22).
- **Reglas:** el carrusel es el componente más reglado (CAG-01 a CAG-04); las tarjetas se encierran en `<a>`/`<button>` con título CTA de máximo 2 palabras (CAG-26).
- **`GET /bloques`** aparece únicamente en `docs/trazabilidad.md` (FUN-013, CAG-26): es una decisión de implementación, no un requisito del expediente.

### M14. SEO y metadatos
- **No consta en el expediente.** Lo único documentado es: `meta description` en el snippet de página (`Sección 6 · §6.12.6`), "metadatos SEO" como capacidad del CMS (`docs/adr/README.md` ADR-0009), `sitemap.xml` como alcance de auditoría de accesibilidad (`Sección 3 · §3.6.3`), `<title>` descriptivo por página (WCAG 2.4.2) y **gestor de redirecciones 301** (ADR-0009). No hay requisito de posicionamiento, canónicas, `robots.txt`, datos estructurados ni Open Graph en el expediente.

### M15. Canales de atención, sedes y atención al ciudadano (B6)
- **Qué exige:** identificar los **canales digitales oficiales** de recepción de solicitudes y de información (art. 14 inc. 3, O-03); canales presencial/virtual, agendamiento de citas, chat/CallCenter y correo de atención (`§5.1.1` B6); módulos *¿Cómo fue tu experiencia?* y *¿Tienes dudas sobre este trámite?* solo en Trámites y servicios (`Sección 2 · §2.3.2`, componente 12); encuesta de usabilidad visible (FUN-052); versión A/B del pie según número de sedes.
- **Evidencia de implementación:** `GET /canales-atencion`, `GET /sedes`, `GET /dependencias`.

### M16. Buscador interno
- **Regla dura:** la búsqueda debe ocurrir **dentro de la Sede**; **prohibido** un buscador de Google (FUN-001, severidad Crítica) y los resultados no deben estar rotos (FUN-002); buscador en top bar, cabecera, trámite y tabla; variantes básica y **predictiva** (autocompletar + historial) (`Sección 2 · §2.3.2`, componente 14; `§2.5`); la búsqueda de Transparencia debe ser **dentro de la propia sección** y ordenada del más reciente al más antiguo (FUN-017/FUN-019); al consultar por radicado sin autenticación se exige captcha **para evitar enumeración** (`§5.2.4`).

---

## 5. Atributos obligatorios de un trámite (FUN-021 / §5.1.3)

`Sección 5 · §5.1.3` — «Composición mínima de la sección "Servicios a la Ciudadanía"» — transcripción textual:

> Por cada trámite publicado (PPT diap. 12), la Sede debe indicar **explícitamente los seis atributos** del catálogo. Si falta cualquiera de ellos, el ítem no cumple criterio de aceptación:

| Atributo | Tipo de valor | Ejemplo |
|---|---|---|
| **Modalidad** | `EN_LINEA` \| `PARCIALMENTE_EN_LINEA` \| `PRESENCIAL` | EN_LINEA |
| **Tiene costo** | `GRATUITO` \| `CON_COSTO` | CON_COSTO |
| **Tiempo de solución** | Duración estimada (días hábiles) | 15 |
| **Canal de inicio** | URL gov.co o botón interno | https://gov.co/tramites/X |
| **Mecanismo de consulta de estado** | URL/endpoint de seguimiento | /seguimiento?radicado=… |
| **Documentos / requisitos** | Lista descargable en PDF | reqs.pdf |

**Nota de fidelidad:** el texto de FUN-021 (`§5.3.2.3`) solo nombra **tres** de los seis —"modalidad (en línea, parcialmente en línea, presencial), costo (gratuito/con costo) y tiempo de solución"—; los otros tres provienen de `§5.1.3` (PPT diap. 12). La métrica de conformidad de `§5.6.4` también usa solo los tres primeros ("Trámites del catálogo sin modalidad/costo/tiempo declarados = **0**"). Ver §9.

---

## 6. Integraciones externas obligatorias (14)

Fuente: `Sección 5 · §5.4`. Las marcadas **(a confirmar)** lo están **en la propia tabla del expediente**, que advierte: «deben validarse con el proveedor durante la fase de implementación; el PPT y PDF no especifican la versión del contrato».

| # | Integración | Para qué sirve | ¿"(a confirmar)"? | ¿Credenciales? |
|---|---|---|---|---|
| INT-01 | Autenticación ciudadana (gov.co / Cédula Digital) | Login del ciudadano para trámites en línea, perfil único y firma | Sí — «OIDC/OAuth2 contra `id.gov.co` (**a confirmar versión**)» | Sí: cliente OIDC y secreto contra el IdP. `docs/despliegue-secretos.md` no los lista; ADR-0005 declara que **no existen credenciales productivas** en el entorno |
| INT-02 | Gov.co — Portal Único del Estado | Los trámites en línea y parcialmente en línea deben direccionar a gov.co | Sí — «modelo de integración por iframe/redirect (**a confirmar**)» | **no consta** |
| INT-03 | PSE — Pagos Seguros en Línea | Pasarela de pagos para trámites con costo | Sí — «documentación en pse.com.co (**a confirmar versión**)» | Sí: `SEDE_PAGOS_SECRETO`, que «lo entrega la pasarela» y autentica sus notificaciones (`docs/despliegue-secretos.md`) |
| INT-04 | Botón de pagos (otros adquirentes) | Alternativa a PSE: tarjetas, wallets | Sí — «**a confirmar adquirente** — Ej. PlaceToPay, PayU, Mercado Pago» | Sí, del adquirente (mismo secreto de pasarela) |
| INT-05 | Firma electrónica (AND / Certicámara / GSE) | Firma de documentos cuando el trámite lo exija | Sí — «API SOAP/REST por proveedor (**a confirmar**)» | Sí: certificado/llave del prestador. **No consta** en el expediente qué credencial |
| INT-06 | Correo certificado | Notificación de actos administrativos con acuse de recibo | Sí — «4-72 / Certicámara / correo con acuse (**a confirmar**)» | Sí: `SEDE_CORREO_CERTIFICADO_SECRETO`, «lo entrega el operador de correo certificado» (`docs/despliegue-secretos.md`) |
| INT-07 | Notificaciones electrónicas (correo, SMS, push) | Comunicación de eventos del trámite | Parcial — «SMTP/SES/SendGrid para correo; proveedor SMS (**a confirmar**)» | Sí para SMS; correo por SMTP/SES |
| INT-08 | Archivo General de la Nación — preservación documental | Preservación de la información pública | Sí — «Directrices técnicas AGN (**a confirmar versión**)» | **no consta** |
| INT-09 | Sistema de PQRSD | Recepción, radicación, seguimiento y respuesta a PQRSD | Sí — «API propia o gov.co (**a confirmar**)» | **no consta** |
| INT-10 | Captcha / anti-bot | Protección de formularios contra envíos automatizados | Sí — «reCAPTCHA v3 / hCaptcha / Turnstile (**a confirmar**)» | Sí: clave de sitio y clave secreta del proveedor |
| INT-11 | Buscador interno indexado | Búsqueda dentro de la Sede (prohibido Google) | Sí — «PostgreSQL FTS, ElasticSearch u OpenSearch (**a confirmar**)» | **no consta** |
| INT-12 | Gestor de cookies / consent management | Aceptar, denegar o revocar cookies | Sí — «Cookiebot / OneTrust / implementación propia (**a confirmar**)» | **no consta** |
| INT-13 | Kit UI 9.2 — Gov.co | Componentes UI oficiales de la Sede | **No** (no lleva marca); referencia: `gitlab.com/govco/layout-govco/-/tree/v5` | No requiere credenciales (repo público / CDN) |
| INT-14 | CSIRT / equipo de respuesta a incidentes | Reporte y atención de incidentes de seguridad | Sí — «Canal CSIRT nacional: `https://www.csirt.gov.co` (**a confirmar**)» | **no consta** |

> **Nota:** son **14** integraciones (INT-01…INT-14) y **13** llevan marca "(a confirmar)" en su columna de contrato/API. Solo dos credenciales de tercero están nombradas en el expediente (`SEDE_PAGOS_SECRETO`, `SEDE_CORREO_CERTIFICADO_SECRETO`, en `docs/despliegue-secretos.md`), y ese mismo documento advierte que **no se crearon** porque su valor es un acuerdo con un tercero.

---

## 7. Reglas de negocio, plazos y umbrales

### 7.1 Plazos legales y de respuesta

| Regla | Valor | Fuente |
|---|---|---|
| Acuse de radicación de PQRSD | ≤ **1 minuto** tras el envío | `Sección 5 · §5.6.2` |
| Respuesta a PQRSD de interés general | ≤ **15 días hábiles** | `Sección 5 · §5.6.2` (Ley 1755/2011 art. 14) |
| Respuesta a PQRSD de interés particular | ≤ **15 días hábiles** | `Sección 5 · §5.6.2` (Ley 1755/2011 art. 14) |
| Cambio de estado visible en el panel | ≤ **5 minutos** desde la emisión del tramitador | `Sección 5 · §5.6.2` `[A]` |
| Notificación por correo electrónico | ≤ **2 minutos** desde el evento | `Sección 5 · §5.6.2` `[A]` |
| Notificación a titulares por incidente (Muy Grave) | ≤ **15 días hábiles** | `Sección 4 · §4.6.5` (Ley 1581 art. 17 lit. g) |
| Notificación a titulares por incidente (Grave) | ≤ **30 días hábiles** | `Sección 4 · §4.6.5` |
| Atención de derechos ARCO | ≤ **15 días hábiles** | `Sección 4 · §4.7.8` |
| Integración de ventanillas únicas existentes a GOV.CO | **6 meses** desde la vigencia del DL 2106 (antes del 22-may-2020) | `Sección 1 · §1.2.8` |
| Integración de portales transversales | **6 meses** desde que MinTIC fije condiciones | `Sección 1 · §1.2.8` |
| Digitalización de trámites por tipo de autoridad | Alcaldías avanzadas, gobernaciones y **distritos: hasta mayo/2028 (77 meses)** | `Sección 1 · §1.5.1` (Decreto 088/2022) |
| Plazo general de la Ley 2052/2020 | **12 meses** desde la promulgación | `Sección 1 · §1.5.1` |

### 7.2 Disponibilidad, continuidad y respaldo

| Regla | Valor | Fuente |
|---|---|---|
| Disponibilidad mensual de la Sede | **≥ 95 %** | `Sección 5 · §5.6.1` (FUN-053, PPT 19) |
| SLA ≥ 99 % en horario hábil | Atributo *Disponibilidad* del art. 14 inc. 2 | `Sección 1 · §1.2.7`, `§1.3` |
| Criterio de salida de la Fase 6 | **SLA ≥ 99 % mensual** el primer trimestre | `Sección 6 · §6.8` |
| MTBF | ≥ **720 horas** `[A]` | `Sección 5 · §5.6.1` |
| MTTR | ≤ **30 minutos** `[A]` | `Sección 5 · §5.6.1` |
| RPO | ≤ **1 hora** `[A]` en `§5.6.1` vs ≤ **24 h** en `SEG-BAK-04` | `Sección 5 · §5.6.1` y `Sección 4 · §4.2.9` |
| RTO | ≤ **4 horas** | `Sección 5 · §5.6.1`; `Sección 4 · §4.2.9` |
| Copia de seguridad | **Diaria** automatizada de BD; **incremental cada 6 h** en SLA alto | `Sección 4 · §4.2.9` SEG-BAK-01 |
| Retención de copias | ≥ **30 días** en caliente, ≥ **1 año** en archivo cifrado | `Sección 4 · §4.2.9` SEG-BAK-02 |
| Cifrado de copias | **AES-256** en reposo, llaves en KMS separado | `Sección 4 · §4.2.9` SEG-BAK-03 |
| Prueba de restauración | **Trimestral** documentada (y «en los últimos 90 días» firmada por el CISO) | `Sección 4 · §4.2.9`, `§4.7.9` |
| Almacenamiento off-site | Copias **semanales** | `Sección 4 · §4.2.9` SEG-BAK-05 |
| Inmutabilidad de backups | WORM / object-lock durante la ventana de retención | `Sección 4 · §4.2.9` SEG-BAK-06 |
| DR drill | **Anual** | `Sección 4 · §4.7.11` |
| Carga de la página principal | ≤ **2,5 s en P95** (4G simulado) | `Sección 5 · §5.6.2` |
| Prueba de carga | ≥ **500 usuarios concurrentes** en homepage | `Sección 6 · §6.7` paso 5.3 |

### 7.3 Seguridad: límites numéricos

| Regla | Valor | Fuente |
|---|---|---|
| Rate-limit de login | **5 fallos en 5 min → HTTP 429**; **10 fallos/día → cuenta bloqueada** | `Sección 4 · §4.3` SEG-004, `§4.7.3` |
| Backoff | Exponencial; alerta al CISO si el patrón es distribuido | `Sección 4 · §4.2.3` SEG-004-RA-02 |
| Contraseñas | Mínimo **8 caracteres** (15 recomendado para single-factor); sin reglas de composición; verificación contra listas de comprometidas | `Sección 4 · §4.2.3` SEG-004-RA-03 (NIST SP 800-63B-4 §5.1.1) |
| Sesión | Expiración **absoluta ≤ 30 min** para datos sensibles y **≤ 8 h** para operaciones estándar; `§5.2.1` añade «expiración **deslizante ≤ 30 min de inactividad**» | `Sección 4 · §4.2.3` SEG-004-RA-05; `Sección 5 · §5.2.1` |
| Cookies | `HttpOnly` + `Secure` + `SameSite=Lax`/`Strict`; nombre sin revelar tecnología | `Sección 4 · §4.2.3`, `§4.7.4` |
| TLS | Mínimo **1.2**, recomendado 1.3; expiración máxima **90 días**; SSL Labs **A o A+**; securityheaders.com **grado A o superior** | `Sección 4 · §4.2.1`, `§4.7.1` |
| HSTS | `max-age ≥ 31536000` (1 año) en SEG-001-RT-04 vs `max-age=63072000` (2 años) en el catálogo de cabeceras | `Sección 4 · §4.2.1` y `§4.2.7` |
| Inventario de puertos | **Trimestral** (SEG-002-RN-01) vs escaneo `nmap` **mensual** y «últimos 90 días» | `Sección 4 · §4.2.2`, `§4.3`, `§4.7.2` |
| Logs | ≥ **12 meses en caliente** y ≥ **5 años en archivo**; retención verificada de 12 meses | `Sección 4 · §4.2.5` SEG-011-RA-03; `§4.7.6`; SEG-013 |
| Remedición de hallazgos | Crítica: bloquea el despliegue; Alta: antes de producción; Media: primeros **30 días** post-go-live; Baja: mejora continua | `Sección 4 · §4.1.4` |
| Incidentes — contención / notificación / titulares | Muy Grave **≤1 h / ≤2 h / ≤15 días hábiles**; Grave **≤4 h / ≤8 h / ≤30 días hábiles**; Menos Grave **≤24 h**; Menor **≤72 h** | `Sección 4 · §4.6.5` |
| Sanciones SIC | Hasta **2.000 SMLMV** | `Sección 4 · §4.5.4` |
| Aprobaciones para desplegar a producción | **Dos** (desarrollador + líder de seguridad) | `Sección 4 · §4.7.10` |
| Escaneos en CI | SCA/SAST/DAST sin hallazgos críticos antes de cada despliegue | `Sección 4 · §4.7.10` |

### 7.4 Diseño y contenido: límites numéricos

| Regla | Valor | Fuente |
|---|---|---|
| Ítems del menú principal | Máx. **7** | CAG-09, FUN-012 |
| Secciones internas del menú | Máx. **4** (CAG-09) / «máx. 2 niveles» (FUN-012) | CAG-09, `§5.1.2` |
| Elementos visibles en un desplegable-lista | Máx. **5** | CAG-18 |
| Área activa táctil mínima | **44 × 44 px** (paginación en móvil, barra superior; WCAG 2.2 AA exige 24 × 24) | `§2.2.3`, CAG-23, `§6.12.5` regla 5 |
| Contraste | **4.5:1** texto normal / **3:1** texto grande y componentes UI | CAG-03, CAG-28, RF-PAL-02/03, WCAG 1.4.3 y 1.4.11 |
| Longitud de línea | **45–75 caracteres** | RF-TIP-01, CAG-27 |
| Gutter de la rejilla | **24 px**; base de píxeles múltiplos de **8 px**; íconos múltiplos de **4 px** | `§2.2.3` |
| Breakpoints | xs <576 · sm ≥576 · md ≥768 · lg ≥992 · xl ≥1200 · xxl ≥1400 | `§2.2.3` |
| Barra de accesibilidad oculta | Entre **768 y 992 px** | CAG-07 |
| Alerta | Padding lateral **40 px**; ancho **70 %** en desktop y **100 %** en responsive | `§2.2.3` |
| Modales simultáneos | Máx. **1**; cierre con `Esc` y clic fuera | CAG-21 |
| Indicador de carga | Informar progreso si **> 10 s** | CAG-29 |
| Título CTA de tarjeta | Máx. **2 palabras** | CAG-26 |
| Pestañas | Horizontal máx. **23 caracteres**; vertical máx. **30** | `§2.3.2` componente 25 |
| Logo GOV.CO en barra superior | **24 × 136 px**, `#0943B5` | CAG-05 |
| Botón de idioma | **24 × 24 px** | CAG-06 |
| Encabezados | Un solo `h1`, sin saltos de nivel | `§3.4.3` |
| `alt` de imágenes informativas | Máx. **150 caracteres** | `§3.4.3`, `§3.3.1` |
| Galería de aplicaciones | Hasta **3 columnas verticales** | `§2.3.2` componente 15 |
| Reflow / zoom | **320 CSS px** sin scroll horizontal; zoom **400 %** sin pérdida; resize text **200 %** | WCAG 1.4.10, 1.4.4 |
| Espaciado de texto | `line-height 1.5; letter-spacing 0.12em; word-spacing 0.16em` | WCAG 1.4.12 |

### 7.5 Transparencia, contenido y publicación

| Regla | Valor | Fuente |
|---|---|---|
| Fecha de publicación | Obligatoria en toda la información | FUN-019 |
| Orden del listado | Del **más reciente al más antiguo** | FUN-019 |
| Duplicidad entre secciones | Prohibida | FUN-020 |
| Periodicidad de actualización de transparencia | **No consta un período expreso.** Lo exigido es que la información esté «actualizada, veraz, oportuna y completa» (FUN-058) y que la publicación fije una **próxima revisión** con aviso cuando venza; el plazo es **configurable** | FUN-058; `docs/trazabilidad.md` FUN-058 (`la_revision_vencida_se_pone_en_conocimiento`, `el_plazo_de_revision_es_configurable`) |
| Contenido publicado | Solo contenido **publicado y vigente** se expone en transparencia | `docs/trazabilidad.md` FUN-017 |
| Licencia de los archivos | Licencia abierta, libre uso, sin restricciones legales, formatos abiertos | FUN-059 |
| Retención documental | TRD/TVD del AGN; plazo contado **desde el cierre** del expediente; sin serie documental no hay plazo; vencida la retención procede la disposición final | `Sección 1 · §1.2.9`; `docs/trazabilidad.md` FUN-054 |
| Lenguaje claro | Oraciones ≤ **25 palabras**; sin siglas sin expandir; sin nominalizaciones ni doble negación | `Sección 3 · §3.4.6` |
| Cookies no necesarias | **Opt-in** granular por categorías (estrictas, analíticas, funcionales) | `Sección 4 · §4.5.1` |

### 7.6 Accesibilidad: umbrales de auditoría y remedición

| Regla | Valor | Fuente |
|---|---|---|
| Puerta automática | Score **≥ 90** y **0 violaciones críticas**; sin hallazgos *Serious* ni *Critical* de axe-core | `Sección 3 · §3.6.2`; `Sección 2 · §2.6.3`, `§2.6.7` |
| Cobertura de la herramienta | Ninguna herramienta cubre sola WCAG 2.1 AA (axe-core ~57 %) | `Sección 3 · §3.5.2` |
| Tab-through manual | ≥ **5 páginas críticas** | `Sección 3 · §3.6.2` |
| Pruebas con usuarios | **3 perfiles** de discapacidad, umbral **≥ 4/5** de satisfacción | `Sección 3 · §3.6.2` |
| Frecuencia de auditoría externa | **Trimestral o anual** | `Sección 3 · §3.6.1` |
| Declaración de conformidad | Próxima revisión **máximo 12 meses** | `Sección 3 · §3.6.3` |
| SLA de remediación | Crítico: antes del próximo deploy · Alto: ≤ **5 días hábiles** · Medio: ≤ **30 días calendario** · Bajo: backlog | `Sección 3 · §3.6.4` |
| Obligatoriedad AA | Desde **1-ene-2022** | `Sección 3 · §3.1.1` |
| Auditoría manual semestral | Atributo Accesibilidad (O-11) | `Sección 1 · §1.4` |

### 7.7 Usabilidad y proceso de proyecto

| Regla | Valor | Fuente |
|---|---|---|
| Encuesta de usabilidad disponible | En **≥ 90 %** de las páginas | `Sección 5 · §5.6.3` |
| Tasa de respuesta de la encuesta | ≥ **2 %** de las sesiones autenticadas `[A]` | `Sección 5 · §5.6.3` |
| Abandono de formulario | ≤ **25 %** `[A]` | `Sección 5 · §5.6.3` |
| Tiempo para completar un trámite | ≤ **5 min** simples / ≤ **15 min** complejos `[A]` | `Sección 5 · §5.6.3` |
| Lighthouse móvil | ≥ **90** `[A]` | `Sección 5 · §5.6.3` |
| Enlaces rotos en producción | **0** (medición semanal) | `Sección 5 · §5.6.4` |
| Formularios sin captcha / sin aviso de privacidad | **0** | `Sección 5 · §5.6.4` |
| Secciones obligatorias presentes | **3 / 3** | `Sección 5 · §5.6.4` |
| Ítems visuales obligatorios | **3 / 3** | `Sección 5 · §5.6.4` |
| Cobertura FUN-001…FUN-006 | **100 % implementados** | `Sección 5 · §5.6.4` |
| Trámites en la v1 | **5 a 10** | `Sección 6 · §6.2` paso 0.2 |
| Duración total del proyecto | **18–26 semanas** (fases suman ~19 semanas: 1+2+3+10+2+2+2) | `Guía Maestra §0`; `Sección 6 · §6.1` |
| Sprints | **2 semanas** | `Sección 6 · §6.5` |
| Cobertura de pruebas de regresión | ≥ **70 %** | `Sección 6 · §6.7` paso 5.1 |
| Pruebas exploratorias | **4 sesiones de 4 horas** con usuarios piloto | `Sección 6 · §6.7` paso 5.2 |
| Piloto | **50–100 usuarios reales** durante **1 semana** | `Sección 6 · §6.8` paso 6.1 |
| Go-live canary | **10 % → 50 % → 100 %** | `Sección 6 · §6.8` paso 6.2 |
| Auditoría anual de criterios | FUN + ACC + SEG | `Sección 5 · §5.6.5` |
| Reporte mensual | Al Comité Institucional de Gestión y Desempeño | `Sección 5 · §5.6.5` |
| Despliegue | **2 aprobaciones previas** a producción | `Sección 4 · §4.7.10`; `docs/despliegue-secretos.md` (hoy **no automatizable** por el plan de GitHub) |

---

## 8. Requisitos de accesibilidad medibles

**Norma exigida:** **WCAG 2.1 nivel AA**, obligatorio y vigente en Colombia desde el **1 de enero de 2022** por la **Resolución MinTIC 1519 de 2020, art. 3 y Anexo 1** (`Sección 3 · §3.1.1`). WCAG 2.2 (5-oct-2023) se aplica como **objetivo de ingeniería recomendado**, no como mínimo legal (`§3.1.2`). AAA es **aspiracional y no obligatorio** (`§3.3.3`). Aplicación obligatoria en «todos los procesos de actualización, estructuración, reestructuración, diseño y rediseño de sus portales web y sedes electrónicas» (`§3.1.3`).

### 8.1 Umbrales exactos

| Requisito | Umbral exacto | Fuente |
|---|---|---|
| **Contraste** — texto normal | **≥ 4.5:1** | WCAG 1.4.3 AA; `Sección 3 · §3.4.4`; RF-PAL-02; CAG-28 |
| **Contraste** — texto grande | **≥ 3:1** | WCAG 1.4.3 AA; `§3.4.4` |
| Umbral de "texto grande" | **≥ 18 pt / ≥ 24 CSS px** (o **≥ 14 pt bold / ≥ 19 CSS px**). *El PPT usa "<18 px / >18 px", que el expediente califica de ambiguo y ordena usar el umbral WCAG exacto* | `§3.4.4` `[A]` |
| **Contraste** — componentes UI, íconos, bordes | **≥ 3:1** | WCAG 1.4.11; `§3.4.4` |
| **Contraste** — indicador de foco | **≥ 3:1** contra el fondo adyacente | WCAG 1.4.11 / 2.4.13 |
| **Contraste** — placeholder | **4.5:1** (cuenta como texto) | `§3.4.4` |
| **Contraste** — texto sobre imagen | **4.5:1** medido contra el píxel más extremo; exige overlay para fondo uniforme | `§3.4.4` |
| **Contraste** — carrusel (contenido vs. controles) | **≥ 4.5:1** | CAG-03 |
| **Contraste** — pie de página | **≥ 4.5:1** | CAG-12 |
| **Contraste** — foco del menú y de la línea de avance | **≥ 4.5:1** | CAG-10, CAG-20 |
| AAA (no obligatorio) | **7:1** normal / **4.5:1** grande | WCAG 1.4.6 |
| Estados deshabilitados | **Exentos** de contraste | `§3.4.4` |
| **Tamaño de objetivo táctil** | **≥ 44 × 44 px** en móvil (paginación y área activa táctil). WCAG 2.2 AA baja el mínimo a **24 × 24 CSS px** (2.5.8); 44 × 44 es 2.5.5 (AAA) | `§2.2.3`, CAG-23, `§3.1.4`, `§6.12.5` regla 5 |
| **Foco visible** | `:focus-visible` con `outline` **≥ 2 CSS px**; el expediente exige además «contraste de foco suficiente». Ejemplo canónico de código: `outline: 3px solid #ffbf00; outline-offset: 2px`. Criterio 2.4.13 (AAA): **2 CSS px** y **3:1** entre estados | `§3.4.2`, `§3.4.5`, `§2.6.4` |
| **Navegación por teclado** | **100 %** por teclado (`Tab`, `Shift+Tab`, `Enter`, `Espacio`, `Esc`, flechas, `Inicio/Fin`, `Page Up/Down`); el orden de foco debe coincidir con el orden visual y de lectura; **prohibido `tabindex` positivo**; sin trampas de foco | `§3.4.2`, WCAG 2.1.1/2.1.2/2.4.3, `§2.6.4` |
| **Escala tipográfica (Kit UI 9.2)** | h1 42/50 · h2 34/42 · h3 26/32 · h4 22/26 · h5 20/24 · h6 16/22 · Description 20/22 · Body 15/22 · Body 2 14/20 · Caption 12/20 (px, familia y peso por elemento) | `Sección 2 · §2.2.2.1` |
| Barra de accesibilidad | Debe ofrecer **Aumentar letra · Reducir letra · Contraste**; oculta entre **768–992 px** | CAG-07, PD-05 |
| Redimensión | Texto al **200 %** sin pérdida (1.4.4); reflow a **320 CSS px** sin scroll bidimensional (1.4.10); zoom al **400 %** sin pérdida de contenido | `§3.3.1`, `§3.3.2` |
| Espaciado de texto | Sin rotura con `line-height:1.5; letter-spacing:0.12em; word-spacing:0.16em` | `§3.3.2` (1.4.12) |
| `alt` | Obligatorio en imágenes funcionales/informativas; `alt=""` o `role="presentation"` en decorativas; **máximo 150 caracteres** | `§3.3.1`, `§3.4.3` |
| Encabezados | **Un único `h1`** por página, **sin saltos de nivel** | `§3.4.3` |
| Idioma | `lang="es"` (o `es-CO`) en `<html>`; `lang` en fragmentos de otro idioma | WCAG 3.1.1, 3.1.2 |
| Mensajes de estado | `aria-live="polite"` (no urgentes) y `aria-live="assertive"` (errores que requieren acción) | `§3.4.3` |
| Sesión y tiempo | Sesión de 30 min con posibilidad de extender (2.2.1); *(2.2.6, aviso 20 s antes, es AAA)* | `§3.3.1` |
| Carrusel | Pausa en `hover` y `focus` (2.2.2); sin flashes > 3 Hz (2.3.1) | `§3.3.1` |
| Formularios | `<label for>`, instrucciones de formato (`aria-describedby`), error con `aria-invalid` + `aria-errormessage`/`role="alert"`, botones de confirmar y cancelar, `autocomplete` según WHATWG | `§3.4.1` |

### 8.2 Conformidad que debe acreditarse con evidencia

| Obligación de conformidad | Evidencia exigida | Fuente |
|---|---|---|
| **Declaración de Conformidad de Accesibilidad Web (Directrices WCAG 2.1 – Nivel AA)** | Contenido **obligatorio** del botón de transparencia (Res. 1519/2020 Anexo 1, numeral **9.3**). Plantilla mínima del expediente: entidad, sede, norma de referencia, fecha de evaluación, alcance (URLs o `sitemap.xml`), herramientas y versiones, auditor, estado de cumplimiento, criterios no aplicables, criterios con excepciones, hallazgos pendientes con plan y fecha objetivo, y **fecha de próxima revisión (máximo 12 meses)** | `Sección 3 · §3.6.3` |
| Gate de accesibilidad en CI | `make accesibilidad` — axe-core sobre las **15 vistas públicas**, **sin hallazgos críticos ni serios** (CAG-32, Res. 1519/2020, WCAG 2.1 AA) | `Sección 2 · §2.6.7` |
| Gate de diseño | `make diseno` — tipografía efectiva, paleta efectiva, contraste de todo el texto, carrusel (CAG-01…04), rejilla en los **6 breakpoints**, área activa táctil, buscador, formularios, galería y los criterios que no aplican; cada verificación cita su criterio | `Sección 2 · §2.6.7` |
| Evidencia visual | `node tests/captura-diseno.mjs` — capturas completas a **1440 y 390 px** de las seis vistas principales | `Sección 2 · §2.6.7` |
| Captura en el PR | Desktop **1440** + Mobile **375** adjunta al PR | `Sección 2 · §2.6.6` |
| Bitácora de auditoría | Registro firmado con: versión auditada (commit SHA + URL de staging), listado de issues con WCAG/severidad/estado, capturas de axe DevTools o Accessibility Insights, **grabación de pantalla con NVDA o VoiceOver de al menos un flujo crítico**, y firma del auditor y del frontend lead | `Sección 3 · §3.6.5` |
| Procedimiento de conformidad | Escaneo automatizado → tab-through en ≥ 5 páginas → NVDA + VoiceOver en PQRS/login/búsqueda → Accessibility Insights Assessment completo → pruebas con 3 perfiles de discapacidad → **acta de conformidad** → release | `Sección 3 · §3.6.2` |
| Herramientas mínimas | axe-core, WAVE, Lighthouse, Pa11y, Accessibility Insights, NVDA, VoiceOver, TalkBack, WebAIM Contrast Checker, Stark, W3C Validator | `Sección 3 · §3.5.1` |
| Desviaciones | Deben declararse y justificarse (las de diseño en **ADR-0015**; la matriz de trazabilidad distingue *satisfecho*, *no aplica* y *desviación declarada*) | `Sección 2 · §2.6.7`; `docs/adr/README.md` ADR-0015 |
| Riesgo por incumplimiento | Acción de cumplimiento (Ley 393/1997), seguimiento de la PGN vía FURAG e ITA, multa de la SIC (Ley 1581 art. 23) y demanda por daño antijurídico | `Sección 3 · §3.1.5` |

---

## 9. Contradicciones y ambigüedades detectadas EN LA DOCUMENTACIÓN

Ordenadas por impacto sobre la planificación del CMS. Cada una cita la sección exacta.

### 9.1 Bloqueantes para planificar (afectan el alcance del CMS)

1. **Anexo 1 y Anexo 2 de la Resolución 1519/2020 se definen de dos formas incompatibles.**
   - `Sección 1 · §1.5.1`: «Anexo 1: WCAG 2.1 AA. Anexo 2: estándares de transparencia y divulgación. Anexo 3: seguridad digital. Anexo 4: datos abiertos». Igual en `Sección 3 · §3.1.1` (Anexo 1 = WCAG 2.1 AA).
   - `Sección 4 · §4.5.3`: «la Sede debe cumplir el **Anexo 1 (transparencia pasiva y activa)** y el **Anexo 2 (accesibilidad, usabilidad, seguridad)**».
   - Además `Sección 5 · §5.4` INT-13 cita «Resolución 1519/2020 **Anexo 2.1**» como fuente del Kit UI.
   Es imposible saber qué anexo gobierna el módulo de Transparencia y cuál la accesibilidad. **Debe resolverse contra el texto de la resolución antes de planificar el CMS.**

2. **El módulo de Transparencia no tiene lista de contenidos obligatorios.** La `Sección 5` (FUN-016…FUN-020) describe *atributos* de la sección (integridad, buscador propio, fecha, orden, no duplicidad) pero **no enumera los conjuntos de información** que la Ley 1712/2014 obliga a publicar. Ese listado se delega a la Resolución 1519/2020 —cuyo Anexo es justamente el punto 1— y **no consta** en el expediente. Sin él no puede diseñarse el modelo de contenidos de transparencia.

3. **FUN-023 (los trámites en línea deben direccionar a gov.co) contradice el diseño de la sede con radicación propia.** Si el trámite "en línea" se ejecuta en gov.co, la sede no radica, no cobra, no notifica y no genera expediente; sin embargo FUN-024, FUN-029, FUN-035, FUN-040, FUN-042 y todo el `Sección 5 · §5.2.3` describen radicación, pago y seguimiento **dentro** de la sede. `Sección 1 · §1.2.8` dice que la integración es un «redireccionamiento **desde GOV.CO hacia la sede** de la autoridad», mientras FUN-023 (`Sección 5 · §5.3.2.3`) e INT-02 (`Sección 5 · §5.4`) dicen lo contrario (la sede redirige **hacia** gov.co). La dirección de la integración no está resuelta.

4. **Códigos `ACC-xxx` inexistentes.** `Sección 6` referencia `ACC-001…ACC-008` y `docs/trazabilidad.md` lista `ACC-001, 002, 003, 004, 007, 008` con estado «pendiente». La `Sección 3` **no define ningún código ACC**: solo criterios WCAG. La Guía Maestra, Apéndice B, los cuantifica en «`ACC-NN` (WCAG): 49 [F] + 75 [A]» = 124 criterios. Consecuencia: seis criterios de aceptación de accesibilidad **no tienen enunciado** y otros dos (`ACC-005`, `ACC-006`) se citan implícitamente en `§6.5` S8 («ACC-001…ACC-008») sin aparecer en ninguna tabla.

5. **Los códigos `RN-xx` y `RT-xx` nunca se definen.** Se usan en `Sección 6 · §6.1, §6.2, §6.3, §6.5, §6.9` y `docs/trazabilidad.md` los registra como criterios con evidencia. Agravantes:
   - **`RT-02` tiene dos significados**: «Firma electrónica avanzada (escenario v2)» (`Sección 6 · §6.9`, prioridad *Won't*) y «TLS 1.2/1.3» (`docs/trazabilidad.md`).
   - **`RN-02` se usa para "firmar el acta de inicio con el sponsor"** (`Sección 6 · §6.2` paso 0.4): un acto administrativo interno vinculado a un código de "marco normativo".
   - **Colisión de prefijos**: `RN-0x` (marco normativo) convive con `SEG-002-RN-0x` (requisito de red, `Sección 4 · §4.2.2`); en `docs/trazabilidad.md` la evidencia de `RN-02` y `RN-03` es literalmente la de `SEG-002-RN-02/03` (cortafuegos y segmentación de red), lo que sugiere que el generador de la matriz confundió ambos prefijos.

### 9.2 Inconsistencias en umbrales y conteos

6. **Disponibilidad: 95 % vs 99 %.** FUN-053 y `§5.6.1` exigen **≥ 95 %**; el atributo de calidad del art. 14 inc. 2 (`Sección 1 · §1.2.7`, `§1.3`) y el criterio de salida de la Fase 6 (`Sección 6 · §6.8`) exigen **SLA ≥ 99 % mensual**. Son dos objetivos distintos para la misma propiedad.

7. **RPO: ≤ 1 hora vs ≤ 24 horas.** `Sección 5 · §5.6.1` fija RPO ≤ 1 h; `Sección 4 · §4.2.9` (SEG-BAK-04) fija RPO ≤ 24 h.

8. **MTTR ≤ 30 min no es coherente con RTO ≤ 4 h** (`Sección 5 · §5.6.1`, mismas filas) ni con una disponibilidad del 95 %.

9. **Sesión "deslizante" vs "absoluta".** `Sección 5 · §5.2.1` exige «expiración **deslizante** ≤ 30 min de inactividad»; `Sección 4 · §4.2.3` (SEG-004-RA-05) exige «expiración **absoluta** ≤ 30 min para datos sensibles, ≤ 8 h para operaciones estándar».

10. **HSTS: 1 año vs 2 años.** `max-age ≥ 31536000` en SEG-001-RT-04 (`Sección 4 · §4.2.1`) frente a `max-age=63072000` en el catálogo de cabeceras (`§4.2.7`) y en `docs/adr/README.md` ADR-0008.

11. **Inventario de puertos: trimestral vs mensual.** `SEG-002-RN-01` dice «inventario **trimestral**» (`§4.2.2`); la prueba de SEG-002 dice «escaneo `nmap` **mensual**» (`§4.3`) y `§4.7.2` exige «en los últimos **90 días**».

12. **Número de componentes del Kit: 24 vs 31.** `Sección 2 · §2.3` afirma «**24 componentes** en **3 grupos**», pero las tablas enumeran 8 transversales + **19** generales (§2.3.2 se titula «generales (11)» y lista los ítems **9 a 27**) + 4 de formulario (ítems 28–31) = **31 componentes**. El propio §2.3.4 rotula el grupo como «generales (11)» y dibuja 19 bloques. `docs/adr/README.md` ADR-0004 dice «24 componentes (8 transversales, **19 generales** y 4 de formulario)», suma que da **31**, no 24. La `§2.6.2` vuelve a decir «8 transversales, **11 generales** y 4 de formulario» (= 23). **No es posible determinar cuántos componentes exige el expediente.**

13. **Número de íconos: 499 vs 768.** `Sección 2 · §2.2.4` y `§2.6.1` afirman **499** SVG en `src/assets/icons/`; `docs/adr/README.md` ADR-0002 y ADR-0003 trabajan con **768** iconos SVG y «~7,8 MB de assets». Una de las dos cifras es errónea.

14. **Total de criterios del expediente: 34/57 y 60/74.** El Apéndice B de la Guía Maestra declara `CAG-NN` = **57** y `FUN-NN` = **74**; las secciones solo definen **34** CAG y **60** FUN. El resumen ejecutivo de la Guía («Sección 5 — ... **74 criterios**») repite la cifra de 74; `Sección 5 · §5.3.3` cierra con «**Total 60**» y `docs/trazabilidad.md` también cuenta 60. Los criterios faltantes no existen en ninguna tabla.

15. **"Seis atributos" del trámite vs tres.** `Sección 5 · §5.1.3` exige **seis** atributos y advierte que «si falta cualquiera de ellos, el ítem no cumple criterio de aceptación»; FUN-021 solo exige **tres** (modalidad, costo, tiempo de solución) y la métrica de conformidad de `§5.6.4` mide **tres** («0 trámites sin modalidad/costo/tiempo declarados»). No queda claro si "canal de inicio", "mecanismo de consulta de estado" y "documentos/requisitos" son obligatorios con la misma severidad.

16. **Menú: "máximo 4 secciones internas" vs "máximo 2 niveles".** CAG-09 exige ≤ **4 secciones internas**; FUN-012 y `§5.1.2` exigen desplegables de ≤ **2 niveles**. Son restricciones distintas sobre la misma estructura y no se declara cuál prevalece.

17. **`CAG-18` reexpresado por ADR-0015.** El criterio exige «máximo **5** elementos visibles»; ADR-0015 lo sustituye por «por encima de **12 elementos** debe usarse el desplegable con filtro de búsqueda». El criterio del expediente y su ADR **no dicen lo mismo**, y ambos siguen vigentes en el repositorio.

18. **Umbrero táctil mal etiquetado.** `Sección 6 · §6.12.5` regla 5: «**24 × 24 px** sólo en WCAG 2.2 **AAA**». Es incorrecto: 2.5.8 (Target Size Minimum, **AA**) es 24 × 24 CSS px y 2.5.5 (Enhanced, **AAA**) es 44 × 44. La propia `Sección 3 · §3.1.4` lo dice bien (2.5.8 = AA, 24×24; 2.5.5 = AAA). Además `Sección 6 · §6.6` paso 4.2 lista «objetivo táctil 24×24 px» como criterio nuevo de WCAG 2.2 a auditar.

19. **Foco visible: 2 px vs 3 px.** `§2.6.4` y `§3.4.2` exigen `outline` **≥ 2 px**; el código canónico de `§3.4.5` usa **3 px**; la tabla AA de `§3.3.2` muestra un ejemplo de **2 px** con ratio 4.7:1. Los ejemplos no son consistentes entre sí (aunque ≥2 px los cubre todos).

20. **Pruebas con usuarios: 3 perfiles con umbral 4/5.** El diagrama de `Sección 3 · §3.6.2` define «Pruebas con usuarios — **3 perfiles** de discapacidad» y, en el paso siguiente, el gate es «**≥ 4/5** de satisfacción». Con 3 participantes el denominador 5 es inaplicable.

### 9.3 Ambigüedades, criterios inaplicables y pendientes declarados

21. **Cinco políticas en el pie (SEG-006) vs tres (FUN-005/FUN-015/FUN-033).** SEG-006 exige Términos y condiciones, **Seguridad y Privacidad**, **Protección y tratamiento de datos personales**, **Uso de Cookies** y **Derechos de Autor**. FUN-015 y FUN-005 exigen solo tres (Términos, Privacidad y tratamiento de datos, Derechos de autor). `docs/adr/README.md` ADR-0006 aplica las **cinco**, y `Sección 4 · §4.7.8` verifica los cinco enlaces HTTP 200. El expediente se contradice sobre el contenido obligatorio del pie.

22. **Créditos del pie: `FUN-014` exige ocho campos, dos de los cuales no tienen dato.** Código postal y **línea anticorrupción** son exigidos por FUN-014 y **no constan** para la Alcaldía Distrital de Santa Marta (`docs/adr/README.md` ADR-0006 solo aporta NIT, dirección, teléfonos, línea gratuita y dos correos). El propio ADR deja los valores por ratificar en `datos_por_confirmar`.

23. **Sin logotipo oficial.** ADR-0015, punto 7: «Mientras el Distrito no entregue su archivo oficial, el pie y la cabecera usan el nombre de la entidad con el tratamiento de marca del Kit. **Pendiente:** incorporar el logo oficial de la Alcaldía». FUN-014 exige el logo de la autoridad y CAG-12 lo verifica.

24. **CAG-05 y CAG-06 chocan con el área activa táctil de 44 × 44 px.** CAG-05 fija el logo GOV.CO en **24 × 136 px** y CAG-06 el botón de idioma en **24 × 24 px**, mientras `Sección 2 · §2.2.3` fija el «área activa táctil mínima **44 × 44 px**» citando expresamente «Barra superior — logo». Ambos criterios son 🔴/🟠 y no pueden cumplirse literalmente a la vez.

25. **La omisión del botón de idioma (ADR-0015) contradice FUN-010 y §5.1.2.** El expediente exige «enlaces de traducción» en la barra superior (FUN-010, `§5.1.2`) y el botón de idioma (CAG-06); ADR-0015 lo omite deliberadamente y lo justifica con FUN-008 (sede íntegramente en castellano). La decisión es razonable, pero deja un requisito del expediente sin satisfacer y una desviación que debe declararse formalmente.

26. **Cinco criterios CAG declarados "no aplican" mientras mapas del propio expediente los exigen.** ADR-0015 declara que **CAG-19** (calendario), **CAG-21** (modal), **CAG-22** (toast), **CAG-24** (tablas) y **CAG-25** (acordeón) **no aplican** porque la sede no usa esos componentes. Pero `Sección 2 · §2.5` exige **Tablas y Acordeón** para "Trámites y servicios (listado)" y "Detalle de trámite" y **Alertas y notificaciones (toast)** para "Formularios" y "Notificaciones globales"; `Sección 6 · §6.12.4` exige la **Alerta modal** para "Diálogos de confirmación"; y `Sección 2 · §2.3.3` (componente 29, Desplegables) incluye el **calendario**, que CAG-19 regula. Si el CMS incorpora esos componentes —como sugieren esos mapas— la desviación deja de ser válida y la puerta de conformidad debe volver a exigir los criterios.

27. **La paleta "solo 21 tokens" no se cumple con el Kit real.** ADR-0015, punto 5: el bundle del Kit escribe `#3366cc`, `#4b4b4b`, `#E5ECF8`, `#004884`, `#E6EFFD`, `#B5C7E9`, `#CDE6DF` y `#EECDD2` como literales, mientras CAG-33 exige «solo los 21 tokens `--govcolor-*`». La sede resuelve el conflicto midiendo la paleta **efectiva** de sus vistas; el criterio literal del expediente queda sin cumplir.

28. **Contraste del propio Kit por debajo de AA.** ADR-0015, punto 4: el *toast* de éxito del Kit combina `--govcolor-green` (#158361) con `#CDE6DF` y rinde **3,61:1**, por debajo del **4,5:1** de WCAG 1.4.3. El Kit oficial incumple el criterio que el expediente exige usar.

29. **Los títulos del pie rompen la jerarquía respecto del ejemplo del Kit.** ADR-0015, punto 6: el Kit estiliza el pie sobre `h4` y `h5`, pero en las vistas el contenido termina en `h1`/`h2`, de modo que el salto rompería el orden de encabezados exigido por `Sección 3 · §3.4.3`. La sede aplica la apariencia por clase. Es una desviación declarada, no una contradicción del expediente, pero debe registrarse.

30. **Tres URLs distintas del CDN del Kit, y al menos una probadamente falsa.**
    - `Sección 2 · §2.3` y `Sección 6 · §6.12.1/§6.12.2`: `https://cdn.www.gov.co/layout/v5/all.css` y `.../script.js`.
    - `Sección 6 · §6.12.6`: `https://cdn.gov.co/layout/v5/govco-9.2.css` y `https://cdn.gov.co/layout/v5/script.js` (dominio distinto y archivo con otro nombre).
    - `docs/adr/README.md` ADR-0002 verifica con `curl` que `cdn.www.gov.co/layout/v5/all.css` **devuelve HTML, no CSS** (`content-length: 1908`, `content-type: text/html`) y que la base real es `https://cdn.www.gov.co/layout-govco-v5/all.css` (266 316 B de CSS). El expediente, por tanto, contiene una instrucción de instalación que **no funciona**.
    Además `Guía Maestra, Apéndice C.4` reitera la URL rota.

31. **El `script.js` global del Kit rompe cualquier página que no use sus componentes.** `Sección 6 · §6.12.2` y `§6.12.6` ordenan incluirlo en todas las páginas; ADR-0011 documenta con Playwright que se autoinicializa en `window.load` y lanza `Cannot read properties of null (reading 'addEventListener')` en cada carga, y decide **no cargarlo**. Desviación declarada frente a un requisito explícito de la Sección 6.

32. **"No anidar desplegables en pestañas — CAG-NN lo prohíbe".** `Sección 6 · §6.12.5` regla 4 atribuye la prohibición a «CAG-NN» sin número; **ninguno de los 34 CAG prohíbe esa combinación**. Requisito sin trazabilidad.

33. **"Directiva 03/2019" sin contenido ni aplicabilidad.** FUN-047 la invoca para «entidades nacionales de la rama ejecutiva» sin decir si aplica a un distrito. **No consta.**

34. **`Decreto 1875/2015 art. 2.2.17.1.2` citado como "ámbito de aplicación".** La numeración `2.2.17.x` corresponde al **Decreto 1078/2015** (DUR TIC), no al 1875/2015 (`Sección 5 · Referencias`). Hay un error de cita normativa y **no consta** el texto del artículo.

35. **Citas de artículo cruzadas en el Decreto 2106/2019.**
    - `Sección 5 · §5.4` INT-01 cita «Decreto 2106/2019 (**art. 16** — autenticación digital)», pero el art. 16 trata de **gestión documental electrónica**; la autenticación digital está en el art. 9 (SCD).
    - `Sección 4 · Referencias` N-08 describe el Decreto 2106/2019 como «**Art. 14** sobre autenticación digital», cuando el art. 14 es "Integración a la sede electrónica".
    - `Sección 6 · §6.3` paso 1.6 vincula el certificado TLS a **SEG-002**, que es "puertos abiertos"; el criterio correcto es SEG-001.
    - `Sección 6 · §6.5` S7 vincula cabeceras HTTP, CSP y rate limiting a **SEG-006**, que es el **pie de página**.
    - `Sección 6 · §6.4` paso 2.4 vincula la barra de accesibilidad a **CAG-09** (menú); el criterio correcto es CAG-07.
    - `Sección 6 · §6.2` paso 0.5 vincula «publicar el alcance en la intranet» a **CAG-12** (pie de página).
    - `Sección 6 · §6.4` paso 2.1 rotula «CAG-03…CAG-08 (paleta)»: CAG-03/04 son carrusel.
    - `Sección 6 · §6.5` S2 vincula el catálogo de trámites a **FUN-014/FUN-015** (pie y políticas); S3 vincula el detalle de trámite a FUN-016…018 (transparencia); S6 vincula Transparencia/Participa/Noticias a FUN-022…025 (trámites).
    - `Sección 6 · §6.9` vincula «App Gallery» a **CAG-25** (acordeón) y «Carrusel en Home» a **CAG-26** (tarjeta); el carrusel son CAG-01…04.
    El mapa de trazabilidad de la Sección 6 es, en la práctica, **inservible** como está.

36. **`INT-14` cita un dominio que no es el de la autoridad competente.** `Sección 5 · §5.4` da «CSIRT nacional: `https://www.csirt.gov.co` (**a confirmar**)»; `Sección 4 · §4.6.2` da como canal principal `https://www.csirtgobierno.gov.co/` y añade COLCERT (`contacto@colcert.gov.co`, PGP, teléfonos) y CC-CSIRT Policía. Dos fuentes de contacto distintas para la misma obligación.

37. **El sexto atributo "Documentos/requisitos" choca con el art. 10 inc. 4 del DL 2106.** FUN-021/§5.1.3 exige publicar una lista descargable de requisitos, mientras O-05 y el art. 10 inc. 4 prohíben exigir al ciudadano documentos que ya reposan en otra entidad integrada. No se indica cómo reconciliar el catálogo de requisitos con la verificación oficiosa.

38. **`FUN-059` (licencia abierta) vs `SEG-006` (derechos de autor).** La sede debe publicar contenidos bajo licencia abierta sin restricciones legales y, a la vez, publicar la política de "Derechos de Autor y uso sobre contenidos". El expediente no define qué contenidos quedan en cada régimen.

39. **Ausencia de requisitos de SEO.** No consta en el expediente ninguna obligación de posicionamiento, `robots.txt`, `sitemap.xml`, canónicas, datos estructurados ni Open Graph. Lo único cercano es `meta description` en un snippet de ejemplo (`Sección 6 · §6.12.6`) y «metadatos SEO» como capacidad del CMS (ADR-0009). Si el CMS debe cubrir SEO, **el requisito no proviene del expediente**.

40. **Ausencia de requisitos de usuarios/roles.** El expediente exige MFA para funcionarios (SEG-016) y segregación de funciones (`Sección 4 · §4.1.3`), pero **no define** roles, permisos, flujo de aprobación editorial, ni quién administra qué. La taxonomía existe solo en la implementación (ADR-0009: Spatie, `/admin`). **No consta** en el expediente.

41. **Ausencia de un tipo de contenido "bloques de inicio".** El expediente fija los ítems visuales obligatorios (`§5.1.2`) y los componentes del Home (`§2.5`), pero no un modelo de bloques administrables. `GET /bloques` proviene de la implementación (`docs/trazabilidad.md`), no del expediente.

42. **Periodicidad de actualización de transparencia: no hay número.** El expediente exige información "actualizada, veraz, oportuna y completa" (FUN-058) y una "próxima revisión" configurable (implementación), pero **no fija** un período. `Guía Maestra Apéndice B` no ayuda. **No consta.**

43. **Retención documental sin cifras.** El expediente exige TRD/TVD del AGN (`Sección 1 · §1.2.9`) pero **no transcribe ningún plazo de retención**; el único dato cuantitativo está en las pruebas de la implementación ("la retención se cuenta desde el cierre"). **No consta** el catálogo de series y plazos.

44. **Criterios FUN pendientes de evidencia declarados por la propia matriz.** `docs/trazabilidad.md` marca **sin evidencia**: **FUN-005, FUN-006, FUN-047, FUN-049, FUN-050, FUN-051, FUN-052, O-02, O-06, O-09, O-11, O-12, RT-03 y ACC-001/003/004/007/008** — 18 criterios, con cobertura de **122 de 140 (87 %)**. Entre los pendientes hay obligaciones no menores: `O-06` (integración a GOV.CO), `O-09` (identidad con la Registraduría), `O-11` (auditorías de accesibilidad en CI), **`O-12` (modelo MSPI completo)**, `FUN-047` (Kit UI + Directiva 03/2019), `FUN-049` (CSS externo), `FUN-050` (multi-navegador), `FUN-051` (responsive), `FUN-052` (encuesta de usabilidad).

45. **La matriz de trazabilidad no es reproducible en este workspace.** `docs/trazabilidad.md` se declara «documento generado» a partir del contrato OpenAPI, las pruebas y las vistas (`npm run trazabilidad`), y cita rutas como `backend/`, `frontend/`, `scripts/` y `make trazabilidad`; **esas carpetas no existen en `/home/sacunapolo/Documentos/sede`**, que solo contiene `docs/`, `.scratch/`, `vendor-src/` y cuatro archivos Markdown de una guía de seguridad DevOps ajena a la sede. Cualquier planificación basada en la matriz debe verificar antes dónde vive el código.

46. **Ambigüedad en las fechas.** La Guía Maestra está fechada **27-sep-2026** y el gantt de `Sección 6 · §6.1` arranca el **2026-10-05**; la `Sección 4` cita «$2.700M+ en 2026» y la `Sección 3` cita «axe-core 4.13.0 (ago-2026)». Son fechas futuras coherentes entre sí pero **no verificables** contra fuentes externas desde el expediente.

47. **Definición de "sede electrónica" resuelta por inferencia.** `Sección 1 · §1.2.13` cita el «Art. **147** (sic, Cap. XIII)» del DL 2106/2019 —el propio expediente marca el «sic»—, lo que indica que la numeración de ese artículo es dudosa.

48. **Umbrales de usabilidad enteramente inferidos.** Las métricas de `Sección 5 · §5.6.3` (tasa de respuesta ≥ 2 %, abandono ≤ 25 %, tiempo ≤ 5/15 min, Lighthouse ≥ 90) y varias de `§5.6.1` (MTBF, MTTR, RPO) están marcadas `[A]` y **no provienen de norma ni del PPT**; no pueden tratarse como obligación legal pero sí como criterio de aceptación si el cliente los adopta.

---

## 10. Hechos verificados

1. El **Decreto Ley 2106 de 2019** obliga a que la sede electrónica esté dotada de seis atributos de calidad —calidad, seguridad, disponibilidad, accesibilidad, neutralidad e interoperabilidad— y a que la titularidad sea de cada autoridad — art. 14 inc. 2 (`docs/Sección 1 · Marco normativo.md`, §1.1.1 y §1.2.7).
2. **WCAG 2.1 nivel AA es obligatorio en Colombia desde el 1 de enero de 2022** por el art. 3 y el Anexo 1 de la Resolución MinTIC 1519 de 2020 — (`docs/Sección 3 · Accesibilidad.md`, §3.1.1).
3. El **contraste mínimo exigido** es **4.5:1** para texto normal y **3:1** para texto grande y componentes UI — (`docs/Sección 3 · Accesibilidad.md`, §3.4.4; `docs/Sección 2 · Diseño.md`, CAG-28).
4. El **área activa táctil mínima es 44 × 44 px**, y WCAG 2.2 AA añade un mínimo de **24 × 24 CSS px** — (`docs/Sección 2 · Diseño.md`, §2.2.3 y CAG-23; `docs/Sección 3 · Accesibilidad.md`, §3.1.4).
5. El **foco visible** exige `outline` de al menos **2 CSS px** con contraste suficiente; el ejemplo canónico del expediente usa **3 px** y `:focus-visible` — (`docs/Sección 3 · Accesibilidad.md`, §3.4.2 y §3.4.5).
6. El **menú principal admite máximo 7 ítems** — (`docs/Sección 2 · Diseño.md`, CAG-09; `docs/Sección 5 · Funcionalidad.md`, FUN-012).
7. El **pie de página debe enlazar cinco políticas**: Términos y condiciones, Seguridad y Privacidad, Protección y tratamiento de datos personales, Uso de Cookies y Derechos de Autor — (`docs/Sección 4 · Seguridad.md`, SEG-006).
8. **FUN-014 exige ocho datos de la entidad en el pie** (nombre, dirección, código postal, teléfono, línea gratuita, línea anticorrupción, correo de atención al usuario, correo de notificaciones judiciales) — (`docs/Sección 5 · Funcionalidad.md`, §5.3.2.1).
9. La entidad titular es la **Alcaldía Distrital de Santa Marta**, NIT **891780009**, Calle 14 No. 2-49 Palacio Municipal, línea de atención **(+57) 605 4351719**, PBX **(+57) 605 420 9600**, línea gratuita **01-8000-955-532**, `atencionalciudadano@santamarta.gov.co` y `notificacionesalcaldiadistrital@santamarta.gov.co` — (`docs/adr/README.md`, ADR-0006).
10. El trámite exige **seis atributos obligatorios**: modalidad, tiene costo, tiempo de solución, canal de inicio, mecanismo de consulta de estado y documentos/requisitos; si falta uno, «el ítem no cumple criterio de aceptación» — (`docs/Sección 5 · Funcionalidad.md`, §5.1.3).
11. El expediente enumera **14 integraciones externas obligatorias** (INT-01…INT-14), **13 de ellas con al menos un valor marcado «(a confirmar)»** — (`docs/Sección 5 · Funcionalidad.md`, §5.4).
12. Solo **dos credenciales de tercero** están nombradas en el expediente: `SEDE_PAGOS_SECRETO` (pasarela) y `SEDE_CORREO_CERTIFICADO_SECRETO` (correo certificado), y se declaran expresamente **no creadas** por ser acuerdo con un tercero — (`docs/despliegue-secretos.md`).
13. El **rate-limit de login** exigido es **5 fallos en 5 minutos → HTTP 429** y **bloqueo de cuenta tras 10 fallos/día** — (`docs/Sección 4 · Seguridad.md`, SEG-004 y §4.7.3).
14. La **disponibilidad** exigida es **≥ 95 %** por FUN-053 y **SLA ≥ 99 %** en el criterio de salida de la Fase 6 — (`docs/Sección 5 · Funcionalidad.md`, FUN-053; `docs/Sección 6 · Implementación paso a paso.md`, §6.8).
15. La **respuesta a PQRSD** es de **≤ 15 días hábiles** y el **acuse de radicación de ≤ 1 minuto** — (`docs/Sección 5 · Funcionalidad.md`, §5.6.2).
16. El plazo de digitalización aplicable a **distritos** es **hasta mayo de 2028 (77 meses)** por el Decreto 088 de 2022 — (`docs/Sección 1 · Marco normativo.md`, §1.5.1).
17. La sede debe conformar **expedientes electrónicos** con integridad, disponibilidad y autenticidad (firma digital + timestamp, almacenamiento inmutable) — art. 16 del DL 2106/2019 y O-07 (`docs/Sección 1 · Marco normativo.md`, §1.2.9 y §1.4).
18. La sección de Transparencia exige **buscador propio, fecha de publicación, orden del más reciente al más antiguo y prohibición de duplicidad** — FUN-016 a FUN-020 (`docs/Sección 5 · Funcionalidad.md`, §5.3.2.2).
19. La **Declaración de Conformidad de Accesibilidad Web (WCAG 2.1 AA)** es contenido obligatorio del botón de transparencia (Res. 1519/2020 Anexo 1, numeral 9.3), con revisión máxima cada 12 meses — (`docs/Sección 3 · Accesibilidad.md`, §3.6.3).
20. La **verificación automática de accesibilidad** debe ejecutarse con axe-core sobre **15 vistas públicas** sin hallazgos críticos ni serios, y la de diseño con `make diseno` midiendo tipografía, paleta, contraste, carrusel y rejilla en los **6 breakpoints** — (`docs/Sección 2 · Diseño.md`, §2.6.7).
21. El expediente **no define ningún código `ACC-NNN`** en su sección de accesibilidad; solo los referencia en la Sección 6 y en `docs/trazabilidad.md` — (`docs/Sección 3 · Accesibilidad.md` completo; `docs/Sección 6 · Implementación paso a paso.md`, §6.4–§6.6).
22. El expediente **no define los códigos `RN-01…RN-03` ni `RT-01…RT-05`**, que solo se usan como referencia — (`docs/Sección 6 · Implementación paso a paso.md`, §6.1–§6.9; `docs/trazabilidad.md`).
23. La **URL de instalación del Kit que da la Sección 6 (`cdn.www.gov.co/layout/v5/all.css`) devuelve HTML y no CSS**; la base real es `cdn.www.gov.co/layout-govco-v5/` — (`docs/adr/README.md`, ADR-0002).
24. La matriz declara **140 criterios** con evidencia en **122 (87 %)**, y deja **18 sin evidencia**, entre ellos **O-06 (integración GOV.CO), O-09 (Registraduría), O-11, O-12 (MSPI), FUN-047, FUN-049, FUN-050, FUN-051, FUN-052 y los ACC-*** — (`docs/trazabilidad.md`, «Resumen» y «Criterios sin evidencia registrada»).
25. El **Repositorio del workspace no contiene** `backend/`, `frontend/`, `scripts/` ni el contrato OpenAPI que `docs/trazabilidad.md` cita como origen; solo contiene `docs/`, `.scratch/`, `vendor-src/`, `GUIA-MAESTRA-COMPLETA.md`, `guia-maestra-seguridad.md`, `capitulo-01.md` y `deliverable.md` (verificado con `ls` y búsqueda de archivos en `/home/sacunapolo/Documentos/sede`).
