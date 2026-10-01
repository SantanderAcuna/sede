# Módulo 01 — Estructura e Identidad GOV.CO

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** identidad visual y marca GOV.CO (top bar, footer, logo), navegación (menús, breadcrumb, buscador, mapa del sitio), página de inicio (noticias, carrusel), página 404, gestión de cookies, políticas en línea, componentes del Kit UI v9.2, infraestructura de red (IPv4/IPv6) e **integración/redireccionamiento al Portal Único GOV.CO**.
> **Convenciones y trazabilidad:** ver `../README.md`. IDs `RF-Bn-xxx` trazan a `/tmp/elicit/out/_req_bundle_{n}.md`; `(#NN)` al documento fuente.

---

## 1. Descripción y alcance

Este módulo define la "carcasa" de la sede: lo que es común a **todas** las páginas y la forma en que la sede se presenta como parte del ecosistema GOV.CO. La integración a GOV.CO se realiza por **redireccionamiento con enmascaramiento de URL** mediante el proxy de MinTIC; el ciudadano ve `https://www.gov.co/...` aunque el contenido lo sirva la infraestructura de la Alcaldía. El diseño sigue el **Kit UI GOV.CO v9.2** (Bootstrap 5.0, Nunito Sans + Verdana, paleta Cobalt `#0943B5`).

## 2. Requisitos Funcionales (RF)

### 2.1 Barra superior, footer e identidad

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-001 / RF-B2-004 / RF-B3-053 | **Top bar GOV.CO** con logo enlazado a `https://www.gov.co/home/` y opción de traducción a la derecha, en TODAS las páginas. Altura 56 px, color Cobalt `#0943B5`, área activa ≥44×44 px. | #3,#7,#21,#225,#241,#70,#118,#132 | Must | Al cargar cualquier página, la barra superior GOV.CO con logo enlazado es visible antes que cualquier otro contenido. |
| RF-B1-002 / RF-B2-005 / RF-B3-057 | **Footer GOV.CO** con: logo GOV.CO + Marca País CO, nombre de la autoridad, dirección, código postal, municipio/departamento, horario, redes sociales, conmutador (+57), línea gratuita, línea anticorrupción (018000), correo institucional, correo de notificaciones judiciales, mapa del sitio y enlaces a políticas. | #7,#21,#225,#241,#70,#118 | Must | En el pie de cualquier página aparecen todos los elementos completos, con prefijo +57 en teléfonos. |
| RF-B2-006 | Teléfonos del footer con prefijo país **+57** e indicativo CRC. **Excepción:** líneas 018000/019000. | #70,#125,#130,#144 | Must | Toda línea distinta a 018000/019000 se publica con +57 e indicativo. |
| RF-B1-043 / RF-B3-056 | **Logo de la Alcaldía** arriba a la izquierda (máx 50 px horizontal; 80-90 px cuadrado/circular) enlazado a inicio; además enlace textual rotulado exactamente "Inicio". Header con logo, buscador y menú. | #7,#170,#118 | Must | Clic en el logo desde cualquier página interna → redirige a la página de inicio. |
| RF-B1-098 / RF-B3-045/046/059 | **Identidad visual Kit UI GOV.CO**: tipografía Nunito Sans (títulos) y Verdana (párrafos), paleta Cobalt `#0943B5`, logo GOV.CO sin deformar. Mayor autonomía de color en botones propios; footer puede usar color institucional. | #7,#252,#118,#120 | Must | Auditoría de marca: barras y navegación siguen el estándar; 0 violaciones de uso del logo. |
| RF-B3-055 | **Botón de idioma** en barra superior (si hay traducción), con cambio persistente; traducción simple (no automática). *Decisión #16 (2026-06-05): DIFERIDO — castellano únicamente por ahora; lenguas étnicas a futuro.* | #118,#102 | Could | Seleccionar idioma cambia todas las páginas hasta nueva elección. *(Diferido hasta definir alcance de traducción.)* |
| RF-B3-061 | Botón flotante **"Volver arriba"** (inferior derecha) en páginas largas. | #118 | Should | Al hacer scroll en página larga aparece el botón. |

### 2.2 Navegación y búsqueda

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-003 / RF-B2-007 / RF-B3-072 | **Menú principal** obligatorio en orden: Inicio, Transparencia y acceso a la información pública, Atención y Servicios a la Ciudadanía, Participa; + hasta 3 ítems (máx 7 total), máx 2 niveles de submenú, `aria-label`. | #7,#21,#225,#241,#70,#118 | Must | El menú muestra los 4 ítems obligatorios en orden, sin exceder 7 opciones ni 2 niveles. |
| RF-B1-004 | **Menú responsive** (hamburguesa <768 px); simple (≤10), megamenú (≤4 secciones) o dinámico (≤10 ítems). | #7 | Must | En móvil <768 px el menú colapsa en hamburguesa con todos los ítems. |
| RF-B1-005 / RF-B2-035 / RF-B3-067 | **Buscador interno** visible en header de todas las páginas (preferente sup. derecha), busca solo contenido de la sede (no Google), ancho mín. 27 caracteres, botón de borrado, predictivo. | #7,#21,#241,#60,#118 | Must | Buscar un término devuelve resultados solo del contenido de la sede, ordenados por pertinencia. |
| RF-B1-006 / RF-B2-036 | Buscador con **autocompletado (≤10 sugerencias)**, tolerancia a errores ortográficos; resultados con fecha, categoría, título, extracto, autor, miniatura. | #170,#60 | Must/Should | Con ≥3 caracteres aparecen hasta 10 sugerencias relevantes pese a errores tipográficos. |
| RF-B1-010 / RF-B2-037 | **Mapa del sitio** enlazado en footer, autoactualizado al cambiar la navegación; además `sitemap.xml` para buscadores. | #17,#241,#70 | Must | Clic en "Mapa del sitio" → estructura completa navegable; `/sitemap.xml` accesible. |
| RF-B2-038 / RF-B3-073 | **Migas de pan (breadcrumb)** en todas las páginas internas (no en home), coherentes con la jerarquía, posición sup. izquierda, niveles enlazados. | #60,#70,#118,#243 | Must | En página interna se ve la ruta completa (Inicio > … > página actual). |
| RF-B2-041 | **Sin vínculos rotos** (0 HTTP 404 en el sitio). | #60,#70,#129,#153 | Must | W3C Link Checker: ningún vínculo retorna 404/error. |

### 2.3 Página de inicio, errores, salidas y pop-ups

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-011 | **Módulo de noticias** en home: imagen 4:3 o 16:9, título ≤150 car., descripción ≤200 car., fecha; orden cronológico inverso. | #7 | Must | Noticia publicada aparece con imagen, título, descripción y fecha en el orden establecido. |
| RF-B1-042 / RF-B3-069 | **Carrusel** con controles obligatorios: indicadores de posición, Play/Stop, flechas; controles que no se solapan con imágenes; pausa por defecto (ver Accesibilidad). | #7,#23,#118 | Must | Clic en Stop detiene el carrusel y el botón cambia a Play. |
| RF-B1-007 / RF-B2-039 / RF-B3-142 | **Página 404 personalizada** con la razón del error y ≥3 opciones de navegación/contenido alternativo (menú, buscador, secciones populares). | #7,#170,#60,#243 | Must | URL inexistente → página 404 personalizada con navegación, no la genérica del servidor. |
| RF-B1-071 / RF-B2-040 / RF-B3-094 | **Aviso de salida a sitio externo** ("Usted está a punto de ingresar a un sitio externo…") con nombre del destino, entidad responsable y confirmación; abrir en nueva pestaña. | #241,#70,#132,#209 | Must/Should | Clic en enlace externo → modal de aviso con confirmación antes de redirigir. |
| RF-B1-008 / RF-B2-011 | **Banner de consentimiento de cookies**: ninguna cookie no esencial activa por defecto; opciones aceptar/rechazar/configurar por categoría; revocación en cualquier momento. | #7,#21,#241,#70,#125 | Must | Usuario nuevo ve el banner; las cookies de analítica quedan inactivas hasta aceptación explícita. |

### 2.4 Políticas en línea (publicación)

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-009 / RF-B3-058 | Publicar en footer: (a) Términos y condiciones, (b) Política de privacidad y tratamiento de datos (Ley 1581/2012), (c) Derechos de autor, (d) Política de cookies, (e) Accesibilidad; descargables en formato abierto. | #7,#21,#53,#225,#241,#118 | Must | Clic en "Políticas" → acceso a los documentos vigentes descargables. |
| RF-B2-008 | Términos y condiciones con: condiciones/alcances/límites de uso, derechos y deberes del usuario, responsabilidad de la entidad, contacto, referencia a privacidad/seguridad. | #70,#125,#128,#144 | Must | El documento muestra los 6 componentes mínimos en lenguaje claro. |
| RF-B2-009 | Política de privacidad conforme Ley 1581/2012 y 1712/2014: categorías de datos, finalidades, derechos ARCO, contacto del responsable, mecanismo de reclamo. | #70,#125,#128,#144,#152 | Must | El documento contiene todos esos elementos. |
| RF-B2-010 | Política de derechos de autor / autorización de uso de contenidos en el footer. | #70,#125,#128,#144 | Must | La política describe los términos de reutilización de contenidos. |

### 2.5 Componentes del Kit UI v9.2

| ID | Descripción | Fuente | Prio |
|----|-------------|--------|------|
| RF-B2-084 / RF-B2-085 | Componentes del **KIT UI GOV.CO** (tipografías, colores, botones, formularios, tablas, notificaciones, modales, tabs, collapses); grilla de 12 columnas; lector de pantalla lee primero Área de Servicio, luego el Trámite. | #130,#198 | Must |
| RF-B3-060 | Cuadrícula **Bootstrap 5.0**, 12 columnas, 6 breakpoints (XS<576 … XXL≥1400), espaciado mín. 24 px. | #118 | Must |
| RF-B3-062 | **Acordeón** (Default/Desplegado/Deshabilitado/Focus); no abre al recibir foco; `aria-expanded`. | #118 | Must |
| RF-B3-063 | **Alerta modal** (confirmación/éxito/error/advertencia); cierre con ESC y clic exterior; no pantalla completa. | #118 | Must |
| RF-B3-064 | **Toast** (informativa/positiva/negativa) con tiempo de lectura suficiente. | #118 | Must |
| RF-B3-066 | **Botones** (texto/simbólico/mixto/textual; Default/Hover/Focus/Disabled) con `aria-label`. | #118 | Must |
| RF-B3-068 | **Galería de aplicaciones** (Carpeta Ciudadana, CIIU, Portal GOV.CO); navegable Enter/Esc. | #118 | Should |
| RF-B3-070 | **Indicador de carga** (spinner); si proceso >10 s, informar estado. | #118 | Must |
| RF-B3-075 | **Paginación** (Anterior/Siguiente, primera/última, elipsis); ≥44×44 px en móvil; `aria-current`. | #118 | Must |
| RF-B3-076 | **Tablas** (básica, filas acentuadas, responsiva, anidamiento, pie) con ordenamiento asc/desc. | #118 | Must |

> **Componentes de formulario del Kit UI** (RF-B3-077 carga de archivos, RF-B3-078 desplegables con filtro, RF-B3-079 entradas de texto con estados, RF-B3-080 checkboxes/radios con `<label>`): ver módulo **08 Usabilidad**. **Login** (RF-B3-074), **área de servicio/stepper** de trámites (RF-B3-065/071): ver módulos **09 Seguridad** y **03 Servicios**.

### 2.6 Integración y redireccionamiento a GOV.CO

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-070 / RF-B3-128 | **Proceso de integración de 7 pasos**: (1) leer lineamientos, (2) identificar ajustes, (3) adecuar la sede, (4) diligenciar formulario de solicitud a MinTIC, (5) atender observaciones, (6) verificar funcionamiento, (7) monitoreo y mejora continua. | #241,#209 | Must | Cumplidos los requisitos, MinTIC verifica y activa el enmascaramiento de URL en gov.co. |
| RF-B1-100 | Elaborar y publicar el **Plan de Integración** a GOV.CO; incorporarlo al PETI; enviarlo a la Dir. de Gobierno Digital; actualizar avance mensual. | #165 | Must | El PETI incluye el plan con actividades, responsables, fechas y presupuesto. |
| RF-B2-001 / RF-B3-125 | Integración por **redireccionamiento con enmascaramiento de URL** (proxy MinTIC); estructura `https://www.gov.co/[palabra_clave]` o `/tramites-y-servicios/T{id_SUIT}`. | #128,#141,#143,#172,#209 | Must | El portal redirige transparentemente sin que el ciudadano abandone el dominio gov.co. |
| RF-B2-002 | Integrar **todos** los portales, sitios, plataformas, VU y apps de la Alcaldía a la sede. | #141,#172 | Must | Todo servicio en línea de la Alcaldía se canaliza por la sede integrada. |
| RF-B2-003 | Integrar portales de **programas transversales** en ≤6 meses desde vigencia del decreto. | #141,#172 | Must | Portal transversal opera bajo dominio GOV.CO con redireccionamiento activo. |
| RF-B3-126 | Registrar todos los trámites en **SUIT (DAFP)** y publicar ficha en GOV.CO con los 4 momentos (acceso, solicitud, resolución, resultado). | #131,#132 | Must | El trámite muestra ficha completa con los 4 momentos y el enlace al portal distrital. |
| RF-B3-127 | Implementar **estados estandarizados**: solicitud registrada → recibida a satisfacción → en trámite → resuelta. | #131 | Must | El estado mostrado usa exactamente los estados que GOV.CO reconoce. |
| RF-B1-099 / RF-B3-040 | **Doble pila IPv4 + IPv6** con DNS para ambos protocolos. | #241,#132 | Must | Acceso desde red IPv6 resuelve y conecta correctamente. |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-01-D01 | El banner de cookies debe **persistir y versionar el consentimiento** (categorías aceptadas/rechazadas, fecha, versión de política) y re-solicitarlo cuando cambie la política o expire (máx. 12 meses). | [DOMINIO]+[NORMATIVA] Ley 1581; cruza con RF-B1-008 que solo describe el alta del banner | Must | Usuario que aceptó analítica hace 13 meses → al volver, ve de nuevo el banner; el sistema guarda registro con versión y timestamp. |
| RF-01-D02 | **CRUD del menú de navegación y del módulo de noticias/carrusel** desde el CMS (crear, editar, reordenar, despublicar, eliminar con confirmación), respetando el tope de 7 ítems y 2 niveles. | [DOMINIO] RF-B1-003/011/042 definen el render pero no el mantenimiento | Must | Editor reordena ítems del menú → cambio reflejado en todas las páginas; intentar superar 7 ítems → bloqueado con mensaje. |
| RF-01-D03 | El **aviso de salida a sitio externo** (RF-B1-071) debe gestionarse desde una **lista blanca de dominios de confianza** administrable, para no mostrar el modal en redirecciones internas a GOV.CO/SCD. | [DOMINIO] manejo de falsos positivos | Should | Enlace a `gov.co` o a la pasarela del Articulador → no dispara el modal; enlace a dominio no listado → sí. |

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B1-027 / RNF-B2-025 / RNF-B3-040 | Infraestructura — red | Doble pila IPv4 + IPv6 con DNS de doble resolución | #99,#241,#70 |
| RNF-B1-033 / RNF-B2-013 / RNF-B3-033 | Neutralidad tecnológica | Funciona sin diferencias en ≥3 navegadores (Chrome, Firefox, Safari, Edge), 0 errores funcionales | #21,#241,#70,#132 |
| RNF-B3-045 | Identidad visual | 100% de componentes con paleta Cobalt `#0943B5` + tipografía Nunito Sans/Verdana | #118,#120 |
| RNF-B3-046 | Identidad visual | Logo GOV.CO sin modificaciones; 0 violaciones de marca | #120 |
| RNF-B3-005 | Tap-target | Elementos interactivos ≥44×44 px en móvil | #118 |

### 3.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-01-D01 | Resiliencia CDN | Si el CDN GOV.CO (`cdn.www.gov.co`) no responde, las tipografías y el Kit UI deben degradar a fuentes locales de respaldo sin romper el layout; reintento con timeout ≤3 s. | [DOMINIO] el propio módulo §8 advierte "si el CDN falla, las tipografías pueden no cargar" pero no lo convierte en requisito |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-001 / RN-B3-006 | La Alcaldía (Rama Ejecutiva territorial) DEBE integrar su sede a GOV.CO (proceso de 7 pasos). | Decreto 2106/2019 Art.15; Decreto 1078/2015 Art.2.2.17.1.2 | #241 |
| RN-B3-004 | Toda autoridad pública debe tener al menos una sede electrónica. | Ley 1437/2011 Art.60 | #132,#209 |
| RN-B3-005 | La sede integra TODOS los portales, plataformas y ventanillas de la entidad. | Decreto 2106/2019 Art.14 | #209 |
| RN-B2-028 | Los portales/trámites integrados quedan bajo dominio GOV.CO (proxy enmascara la URL propia). | Decreto 2106/2019 Art.15 | #70,#125,#128,#144 |
| RN-B2-029 | Kit UI GOV.CO de cumplimiento obligatorio en trámites integrados; solo el footer puede usar color institucional. | Directiva Presidencial 03/2019; Res. 2893/2020 | #130,#198,#192 |
| RN-B1-012 / RN-B3-025 | La sede opera en **castellano**; traducciones admisibles (Ley 1712); lenguas étnicas como complemento. No incluir contenido ajeno a las funciones. | Ley 1712/2014; Decreto 2106/2019; Art.10 CP | #241,#209 |
| RN-B1-014 | **No publicidad** ni marcas ajenas a las funciones de la Alcaldía. | Directiva Presidencial 03/2019 | #241 |
| RN-B1-017 / RN-B2-025 | Teléfonos publicados con prefijo **+57** e indicativo CRC (excepto 018000/019000). | Res. 1519/2020 Anexo 2, 2.2.1-2.2.2 | #7,#225,#70 |
| RN-B3-007 | Ventanillas únicas debían integrarse a GOV.CO en 6 meses (plazo vencido may/2020) → auditar y plan correctivo. | Decreto 2106/2019 Art.15 par.2 | #141 |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-01-D01 | El consentimiento de cookies es **válido por un período máximo y versionado**: si cambia la política de cookies o transcurren >12 meses, el consentimiento previo caduca y DEBE re-solicitarse; ninguna cookie no esencial se reactiva con consentimiento caducado. (Motiva RF-01-D01; refuerza RN-B3-031.) | Ley 1581/2012; principio de consentimiento informado y temporal | [NORMATIVA]+[DOMINIO] |
| RN-01-D02 | El menú de navegación principal NO puede exceder **7 ítems de primer nivel ni 2 niveles de profundidad**: cualquier alta/edición que viole el tope se rechaza. (Eleva a invariante el tope que RF-B1-003/RF-01-D02 solo describen.) | Kit UI GOV.CO; Guía de Usabilidad MinTIC | [DOMINIO]+[WEB] |
| RN-01-D03 | El modal de "aviso de salida a sitio externo" NO se muestra para destinos en la **lista blanca de dominios de confianza** (GOV.CO, pasarela del Articulador, SCD); sí se muestra para cualquier dominio no listado. (Motiva RF-01-D03.) | Buena práctica de seguridad/UX; evita falso positivo | [DOMINIO] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B2-009 / UC-B3-019 | Integrar trámite a GOV.CO | Equipo TI/tramitólogo, MinTIC, AND | Actualizar ficha en SUIT → formulario de integración → metodología de 17 semanas → MinTIC verifica requisitos mínimos (top bar, footer, 4 etapas, seguridad, accesibilidad) → activación del redireccionamiento → monitoreo. | #128,#152,#108,#209 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B3-028 | Como ciudadano quiero los datos de contacto siempre visibles en el footer para comunicarme fácilmente. | **Positivo:** **Dado** que navego cualquier página de la sede, **cuando** visualizo el footer, **entonces** encuentro visible el teléfono +57, la línea gratuita, la línea anticorrupción, el correo institucional y la dirección física. | #118,#221 |
| HU-B1-001 | Como ciudadano quiero encontrar la sede como primer resultado en Google al buscar "Alcaldía Santa Marta". | **Positivo:** **Dado** que realizo una búsqueda en Google con el término "Alcaldía Santa Marta", **cuando** se muestran los resultados orgánicos, **entonces** la URL oficial de la sede electrónica aparece entre los primeros 5 resultados. | #170,#39 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-01-D01 | **Como** ciudadano **quiero** que mi consentimiento de cookies se recuerde pero se me vuelva a pedir cuando cambie la política o caduque **para** mantener control real y vigente sobre mis datos. | **Positivo:** *Dado* que acepté analítica con la política v3, *cuando* la entidad publica la política v4, *entonces* en mi siguiente visita se muestra de nuevo el banner y no se reactiva ninguna cookie no esencial hasta que vuelva a aceptar. <br> **Positivo (caducidad):** *Dado* que mi consentimiento tiene >12 meses, *cuando* vuelvo a entrar, *entonces* el banner reaparece y se registra un nuevo consentimiento con versión y timestamp. <br> **Negativo:** *Dado* un consentimiento caducado, *cuando* el front intenta cargar Google Analytics, *entonces* el script no se inyecta y queda registro de "consentimiento caducado, cookie bloqueada". | RF-01-D01 · RN-01-D01 · UC-016 A2 · [NORMATIVA] Ley 1581 |
| HU-01-D02 | **Como** editor de contenidos **quiero** crear, reordenar y despublicar ítems del menú y noticias del carrusel **para** mantener la sede actualizada sin romper el Kit UI. | **Positivo:** *Dado* un menú con 5 ítems, *cuando* reordeno y publico, *entonces* el orden se refleja en todas las páginas en <1 min y queda en el log. <br> **Negativo (tope):** *Dado* un menú con 7 ítems de primer nivel, *cuando* intento agregar el 8.º o un 3.er nivel, *entonces* el sistema lo bloquea con mensaje "máx. 7 ítems / 2 niveles". <br> **DoR:** definido el árbol de menú vigente y los roles con permiso de edición. **DoD:** cambio auditado, validado en responsive y sin romper enlaces existentes. | RF-01-D02 · RN-01-D02 · [DOMINIO]+[WEB] |
| HU-01-D03 | **Como** administrador **quiero** mantener una lista blanca de dominios de confianza **para** que el modal de "sitio externo" no moleste en redirecciones a GOV.CO/SCD. | **Positivo:** *Dado* un enlace a `gov.co`, *cuando* el ciudadano lo pulsa, *entonces* navega sin modal. <br> **Negativo:** *Dado* un enlace a un dominio no listado, *cuando* el ciudadano lo pulsa, *entonces* aparece el aviso de salida a sitio externo. | RF-01-D03 · RN-01-D03 · UC-003 E4 · [DOMINIO] |
| HU-01-D04 | **Como** ciudadano **quiero** que la sede siga legible aunque el CDN de GOV.CO falle **para** poder usarla en cualquier momento. | **Positivo:** *Dado* que `cdn.www.gov.co` no responde, *cuando* cargo una página, *entonces* se usan tipografías locales de respaldo y el layout no se rompe (reintento ≤3 s). <br> **Negativo:** *Dado* el CDN caído, *cuando* el fallback también está mal configurado, *entonces* se registra el incidente de disponibilidad para observabilidad (RNF-TX-D01). | RNF-01-D01 · [DOMINIO] |

## 7. Datos / Entidades del módulo

- **Sede electrónica:** URL original, palabra clave asignada, URL enmascarada en GOV.CO, institución, categoría, sector, estado de integración. (#241,#209)
- **Cookie:** tipo, finalidad, origen, gestor, periodo de conservación, estado de consentimiento. (#241)
- **Datos de contacto de la entidad:** nombre, dirección (hasta 3 locaciones), código postal, departamento, municipio, conmutador (+57), línea gratuita, anticorrupción, correo institucional, correo de notificaciones judiciales, redes, horarios. (#118,#209)
- **Plan de integración:** trámites (nombre, solicitudes/año, acción, fecha, responsable), dominios web, otros medios (apps, chatbots, PQR). (#181)

## 8. Integraciones

- **Proxy GOV.CO (MinTIC):** enmascaramiento de URL — dependencia crítica. (#241)
- **CDN GOV.CO** (`cdn.www.gov.co`) y **Kit UI v9.2** (`gov.co/files/KITUI.pdf`) y **Biblioteca GOV.CO**: fuentes de verdad gráfica. Si el CDN falla, las tipografías pueden no cargar. (#130,#192,#198)
- **SUIT (DAFP):** fuente de fichas de trámite para el catálogo y los enlaces (ver módulos 03 y 10).

## 9. Ambigüedades y preguntas abiertas

- **[A-01]** "Otros sujetos obligados" (EICE, SEM) "podrán optar" por el top bar → ¿obligación o facultad? (#225)
- **[Kit UI]** No se aclara si el Cobalt `#0943B5` del top bar es inamovible para alcaldías o admite color propio. (#118)
- **[Traducción]** "Traducción simple, no automática" no define qué tecnologías cumplen. (#102)
- **[Des-integración]** Ningún documento define el proceso de baja de un portal de GOV.CO ni qué pasa si cambia/retira una URL de trámite. (#144,#209)
- **[PREGUNTA ABIERTA]** ¿Qué micrositios/apps/portales independientes tiene hoy la Alcaldía que deban integrarse a la sede (Art. 14 Decreto 2106)? (#101)
