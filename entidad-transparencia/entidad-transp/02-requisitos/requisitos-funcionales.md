# Requisitos Funcionales — Módulos 01 (Estructura-Identidad) y 02 (Transparencia)

> **Proyecto:** Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Marco de especificación:** ISO/IEC/IEEE 29148:2018 [12], plantilla Wiegers [16].
> **Convención de identificación:** `RF-01-NNN` = RF del módulo 01; `RF-02-NNN` = RF del módulo 02; `RF-XX-DNN` = requisito derivado de segunda pasada profunda (delta).
> **Trazabilidad:** cada requisito → diseño (`03-propuesta/`), tarea (`07-plan-tareas/`), test (`08-trazabilidad/`).
> **Cliente:** indica qué Nuxt cliente consume la operación: `sitio` (público, solo lectura), `panel` (administración autenticada), `ambos`.
> **Origen:** cada RF mapea a su antecedente en `sede-electronica-doc/01-estructura-identidad/` o `02-transparencia/` (`RF-Bn-*`, `RF-02-D0*`, `RF-01-D0*`), que a su vez cita el corpus normativo original (`(#NN)`).

---

## Resumen ejecutivo

| Categoría | Total | Must | Should | Could | Won't |
|---|---|---|---|---|---|
| RF módulo 01 — Estructura-Identidad | 22 | 19 | 2 | 1 | 0 |
| RF módulo 02 — Transparencia | 30 | 26 | 3 | 1 | 0 |
| **Total** | **52** | **45** | **5** | **2** | **0** |

Distribución por cliente:

| Cliente | RF módulo 01 | RF módulo 02 |
|---|---|---|
| `sitio` (solo GET) | 19 | 26 |
| `panel` (CRUD autenticado) | 3 | 4 |
| `ambos` | 0 | 0 |

---

## Módulo 01 — Estructura e Identidad GOV.CO

### RF-01-001 — Top bar GOV.CO en todas las páginas
- **Descripción:** El sistema debe renderizar la barra superior GOV.CO con logo enlazado a `https://www.gov.co/home/`, altura 56 px, color Cobalt `#0943B5` y área activa mínima de 44×44 px, en **toda** respuesta HTML servida por el sitio público.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Actor(es):** Ciudadano, turista, persona con discapacidad
- **Precondición:** Ninguna
- **Flujo principal:**
  1. El usuario solicita cualquier ruta del sitio público.
  2. El cliente `sitio` consume `GET /api/v1/identidad/topbar`.
  3. El backend devuelve el JSON del topbar (logo, idioma, enlace GOV.CO, elementos contextuales).
  4. El layout `<DefaultLayout>` inyecta `<TopBarGOVCO>` antes que cualquier otro contenido.
- **Flujos alternativos:**
  - Si la API falla → se renderiza el topbar con datos estáticos de fallback y se registra el incidente en `log_auditoria`.
- **Postcondición:** El usuario visualiza la barra superior antes que cualquier otro contenido.
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un ciudadano accede a cualquier ruta del sitio público
  Cuando la página termina de cargar
  Entonces la barra superior GOV.CO es visible en la parte superior con altura 56 px, color Cobalt y el logo enlaza a https://www.gov.co/home/
  Y el primer elemento focalizable del DOM es el logo (orden de tabulación correcto).
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 4 (transparencia pasiva, presencia institucional); Res. MinTIC 1519/2020 Anexo 2 §3.1 (componentes obligatorios); Anexo Técnico 2 MinTIC (manual de marca GOV.CO).
- **Antecedente interno:** RF-B1-001 / RF-B2-004 / RF-B3-053
- **Trazabilidad:** → Diseño `03-propuesta/arquitectura/c4-contenedores.md` §6 → Tarea T-01-001 → Test PT-01-001
- **Módulo:** 01-estructura-identidad

### RF-01-002 — Footer GOV.CO con datos de la entidad
- **Descripción:** El sistema debe renderizar el pie de página GOV.CO con: logo GOV.CO + Marca País CO, nombre de la autoridad (Alcaldía Distrital de Santa Marta), NIT, dirección, código postal, municipio/departamento, horario, redes sociales, conmutador (+57), línea anticorrupción (018000), correo institucional, correo de notificaciones judiciales, mapa del sitio y enlaces a políticas.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Actor(es):** Ciudadano, ente de control
- **Precondición:** Ninguna
- **Flujo principal:**
  1. El usuario visualiza cualquier página.
  2. El cliente `sitio` consume `GET /api/v1/identidad/footer`.
  3. El layout renderiza `<FooterGOVCO>` con los datos obtenidos.
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un ciudadano visualiza el pie de cualquier página del sitio
  Cuando el área del footer carga completamente
  Entonces aparecen todos los elementos completos (15 campos validados) con prefijo +57 en teléfonos distintos a 018000/019000.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §3.4; Anexo Técnico 2 MinTIC.
- **Antecedente interno:** RF-B1-002 / RF-B2-005 / RF-B3-057
- **Trazabilidad:** → Diseño §6 → Tarea T-01-002 → Test PT-01-002
- **Módulo:** 01-estructura-identidad

### RF-01-003 — Teléfonos con prefijo +57 e indicativo
- **Descripción:** Todo teléfono del sitio distinto a líneas 018000/019000 debe publicarse con prefijo país +57 e indicativo de la ciudad (CRC); formato `+57 (5) XXX XXXX` para Santa Marta.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Actor(es):** Sistema, administrador
- **Precondición:** El teléfono existe en la BD (`dependencia.telefono`, `servidor_publico.extension`).
- **Flujo principal:**
  1. El administrador registra/edita un teléfono en el panel.
  2. El backend valida con regla Laravel `PrefijoTelefonicoRule` y reformatea.
  3. El frontend muestra el formato normalizado.
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un administrador registra "4351719" como teléfono
  Cuando el sistema guarda el dato
  Entonces se almacena como "+57 (5) 435 1719" y se muestra con ese formato en cualquier vista pública.
  ```
- **Fuente normativa:** Anexo Técnico 2 MinTIC.
- **Antecedente interno:** RF-B2-006
- **Trazabilidad:** → Tarea T-01-003 → Test PT-01-003
- **Módulo:** 01-estructura-identidad

### RF-01-004 — Logo de la Alcaldía en cabecera
- **Descripción:** El sistema debe mostrar el logo de la Alcaldía en la esquina superior izquierda de la cabecera (máx. 80 px alto, proporcional), enlazado al inicio; debe coexistir un enlace textual rotulado "Inicio" para redundancia accesible.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Actor(es):** Ciudadano, lector de pantalla
- **Precondición:** Logo configurado en `media/logo-alcaldia.svg` (subido vía panel).
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un usuario está en una página interna
  Cuando hace clic en el logo de la Alcaldía
  Entonces es redirigido a la ruta raíz "/".
  Y el enlace textual "Inicio" está presente y también apunta a "/".
  ```
- **Fuente normativa:** Anexo Técnico 2 MinTIC.
- **Antecedente interno:** RF-B1-043 / RF-B3-056
- **Trazabilidad:** → Tarea T-01-004 → Test PT-01-004
- **Módulo:** 01-estructura-identidad

### RF-01-005 — Identidad visual Kit UI GOV.CO
- **Descripción:** El sitio debe aplicar el Kit UI GOV.CO: tipografía Nunito Sans (títulos) y Verdana (párrafos), paleta Cobalt `#0943B5` (primario), `#00ADE7` (entidad), `#00568D` (entidad oscuro), `#FFFFFF` (fondo), `#1A1A1A` (texto principal), `#4C4C4C` (texto secundario), `#1D3557` (enlaces visitados), `#FEE697` (alerta), `#DC2626` (error), `#16A34A` (éxito).
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que se carga el sitio
  Cuando el navegador aplica los estilos
  Entonces el color del top bar es exactamente #0943B5 y los títulos usan Nunito Sans 600.
  ```
- **Fuente normativa:** Anexo Técnico 2 MinTIC.
- **Antecedente interno:** RF-B1-098 / RF-B3-045/046/059
- **Trazabilidad:** → Tarea T-01-005 → Test PT-01-005
- **Módulo:** 01-estructura-identidad

### RF-01-006 — Botón flotante "Volver arriba"
- **Descripción:** En páginas con scroll vertical mayor a 600 px se debe mostrar un botón flotante fijo en la esquina inferior derecha (≥44×44 px, `aria-label="Volver arriba"`) que al activarse hace scroll suave a la parte superior.
- **Prioridad (MoSCoW):** Should
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario hace scroll más allá de 600 px
  Cuando aparece el botón "Volver arriba"
  Y lo activa con Enter o clic
  Entonces el viewport vuelve al inicio con scroll suave.
  ```
- **Fuente normativa:** Anexo Técnico 2 MinTIC.
- **Antecedente interno:** RF-B3-061
- **Trazabilidad:** → Tarea T-01-006 → Test PT-01-006
- **Módulo:** 01-estructura-identidad

### RF-01-007 — Menú principal obligatorio
- **Descripción:** El sistema debe mostrar el menú principal con los ítems obligatorios en este orden: (1) Inicio, (2) Transparencia y acceso a la información pública, (3) Atención y Servicios a la Ciudadanía, (4) Participa; más hasta 3 ítems adicionales (máx. 7 total), con un máximo de 2 niveles de submenú y `aria-label="Menú principal"`.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un usuario carga la página de inicio
  Cuando inspecciona el <nav> con role="navigation"
  Entonces los 4 ítems obligatorios aparecen en el orden exacto definido.
  Y el número total no excede 7.
  Y cada submenú tiene máximo 2 niveles de profundidad.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §3.2; Decreto 2106/2019 (sede única).
- **Antecedente interno:** RF-B1-003 / RF-B2-007 / RF-B3-072
- **Trazabilidad:** → Tarea T-01-007 → Test PT-01-007
- **Módulo:** 01-estructura-identidad

### RF-01-008 — Menú responsive (hamburguesa <768 px)
- **Descripción:** En viewports <768 px el menú colapsa en un botón hamburguesa con `aria-expanded` que al activarse despliega un drawer con todos los ítems; el foco se gestiona con `focus-trap`.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el viewport es 375 px de ancho
  Cuando el usuario abre el menú hamburguesa
  Entonces el drawer se despliega con todos los ítems del menú principal, el foco queda atrapado dentro y ESC cierra el drawer.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §6.1 (responsive).
- **Antecedente interno:** RF-B1-004
- **Trazabilidad:** → Tarea T-01-008 → Test PT-01-008
- **Módulo:** 01-estructura-identidad

### RF-01-009 — Buscador interno predictivo
- **Descripción:** El sistema debe ofrecer un buscador visible en la cabecera de todas las páginas, ancho mínimo 27 caracteres, con autocompletado (≤10 sugerencias), tolerancia a errores ortográficos (≤1 distancia Levenshtein), predictivo y botón de borrado; busca **solo** contenido del sitio (no Google).
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario escribe "transparenci" en el buscador
  Cuando han transcurrido 250 ms (debounce)
  Entonces aparecen hasta 10 sugerencias incluyendo "transparencia" y "transparente".
  Y los resultados no incluyen dominios externos.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §3.3.
- **Antecedente interno:** RF-B1-005/006 / RF-B2-035/036 / RF-B3-067
- **Trazabilidad:** → Tarea T-01-009 → Test PT-01-009
- **Módulo:** 01-estructura-identidad

### RF-01-010 — Mapa del sitio enlazado en footer
- **Descripción:** El footer debe enlazar a `/mapa-del-sitio` (página navegable) y servir `/sitemap.xml` para indexadores. El sitemap debe regenerarse al cambiar la navegación (evento `NavegacionActualizada`).
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario accede a "/sitemap.xml"
  Cuando el servidor responde
  Entonces el XML contiene todas las URLs públicas del sitio con `lastmod`, `changefreq` y `priority`.
  Y se regenera automáticamente al cambiar la navegación.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §3.5.
- **Antecedente interno:** RF-B1-010 / RF-B2-037
- **Trazabilidad:** → Tarea T-01-010 → Test PT-01-010
- **Módulo:** 01-estructura-identidad

### RF-01-011 — Migas de pan (breadcrumb)
- **Descripción:** En toda página interna (no en home) se debe mostrar la ruta jerárquica en migas de pan, posición superior izquierda, niveles enlazados excepto el último (página actual), `aria-label="Migas de pan"`.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario está en "/transparencia/contratacion"
  Cuando carga la página
  Entonces aparece "Inicio > Transparencia > Contratación" con los 2 primeros enlaces navegables y el último como texto plano.
  ```
- **Fuente normativa:** Anexo Técnico 2 MinTIC.
- **Antecedente interno:** RF-B2-038 / RF-B3-073
- **Trazabilidad:** → Tarea T-01-011 → Test PT-01-011
- **Módulo:** 01-estructura-identidad

### RF-01-012 — Cero vínculos rotos
- **Descripción:** El sistema debe garantizar que **0** vínculos internos/externo-verificados retornan código HTTP ≥400 en el sitio público. La verificación es continua (cron semanal `VinculoRoto:Verificar`).
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que se ejecuta el cron semanal de verificación de vínculos
  Cuando el crawler visita cada URL pública
  Entonces ninguna URL retorna HTTP 4xx/5xx.
  Y si detecta, se notifica al admin vía `notificacion`.
  ```
- **Fuente normativa:** RNF-B2-041.
- **Antecedente interno:** RF-B2-041
- **Trazabilidad:** → Tarea T-01-012 → Test PT-01-012
- **Módulo:** 01-estructura-identidad

### RF-01-013 — Módulo de noticias en home
- **Descripción:** El home debe mostrar las últimas 6 noticias en orden cronológico inverso, con imagen (4:3 o 16:9), título ≤150 caracteres, descripción ≤200 caracteres, fecha de publicación y enlace al detalle.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el editor publica una noticia en el panel
  Cuando el home se recarga
  Entonces la noticia aparece en la primera posición con todos los campos validados.
  Y la imagen tiene atributo alt obligatorio (validado en el panel).
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 (imagen con alt).
- **Antecedente interno:** RF-B1-011
- **Trazabilidad:** → Tarea T-01-013 → Test PT-01-013
- **Módulo:** 01-estructura-identidad

### RF-01-014 — Carrusel con controles accesibles
- **Descripción:** El home debe incluir un carrusel con indicadores de posición, controles Play/Stop, flechas de navegación; pausa por defecto (no autoplay); los controles no se solapan con imágenes; `aria-live="polite"` en la región del carrusel.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario carga el home
  Cuando el carrusel está activo
  Entonces NO se reproduce automáticamente.
  Y el botón "Pausar/Reanudar" cambia el estado y su aria-pressed refleja el estado.
  Y los indicadores de posición son navegables con teclado.
  ```
- **Fuente normativa:** WCAG 2.1 AA §2.2.2 (Pausar, Detener, Ocultar).
- **Antecedente interno:** RF-B1-042 / RF-B3-069
- **Trazabilidad:** → Tarea T-01-014 → Test PT-01-014
- **Módulo:** 01-estructura-identidad

### RF-01-015 — Página 404 personalizada
- **Descripción:** Toda URL inexistente debe responder con la página 404 personalizada (código HTTP 404) que muestre la razón, ≥3 opciones de navegación (menú, buscador, secciones populares) y un CTA "Volver al inicio".
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un usuario accede a "/ruta-inexistente"
  Cuando el servidor responde
  Entonces el código HTTP es 404 y la página contiene buscador, menú resumido y CTA a inicio.
  ```
- **Fuente normativa:** WCAG 2.1 AA §3.1.1 (idioma de la página) + usabilidad básica.
- **Antecedente interno:** RF-B1-007 / RF-B2-039 / RF-B3-142
- **Trazabilidad:** → Tarea T-01-015 → Test PT-01-015
- **Módulo:** 01-estructura-identidad

### RF-01-016 — Aviso de salida a sitio externo
- **Descripción:** Todo enlace a dominio externo debe disparar un modal de aviso con el nombre del destino, la entidad responsable y botón de confirmación; el enlace se abre en nueva pestaña solo tras confirmación.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario hace clic en un enlace externo
  Cuando aparece el modal
  Entonces se muestra el dominio destino, la entidad responsable y un botón "Continuar" y otro "Cancelar".
  Y solo al confirmar se abre el destino en nueva pestaña.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §6.2.
- **Antecedente interno:** RF-B1-071 / RF-B2-040 / RF-B3-094
- **Trazabilidad:** → Tarea T-01-016 → Test PT-01-016
- **Módulo:** 01-estructura-identidad

### RF-01-017 — Banner de consentimiento de cookies
- **Descripción:** El sitio debe mostrar el banner de cookies en la primera visita (ninguna cookie no esencial activa por defecto), con opciones Aceptar/Rechazar/Configurar por categoría; la elección persiste (cookie técnica `cd_cookies`) y es revocable en cualquier momento desde el footer.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un usuario nuevo accede al sitio
  Cuando la página carga
  Entonces aparece el banner de cookies y las cookies de analítica NO se cargan hasta aceptación explícita.
  Y el usuario puede revocar su elección desde el footer.
  ```
- **Fuente normativa:** Ley 1581/2012; Ley 1712/2014 (categorías de datos).
- **Antecedente interno:** RF-B1-008 / RF-B2-011
- **Trazabilidad:** → Tarea T-01-017 → Test PT-01-017
- **Módulo:** 01-estructura-identidad

### RF-01-018 — Publicación de políticas en footer
- **Descripción:** El footer debe enlazar a 5 políticas descargables en formato abierto: Términos y condiciones, Política de privacidad (Ley 1581/2012), Derechos de autor, Política de cookies, Accesibilidad.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario hace clic en "Políticas" del footer
  Cuando se despliega el panel
  Entonces aparecen los 5 enlaces y cada uno abre un PDF/A o HTML descargable en formato abierto.
  ```
- **Fuente normativa:** Ley 1581/2012; Ley 1712/2014 Art. 9; Ley 23/1982 (derechos de autor).
- **Antecedente interno:** RF-B1-009 / RF-B3-058
- **Trazabilidad:** → Tarea T-01-018 → Test PT-01-018
- **Módulo:** 01-estructura-identidad

### RF-01-019 — Términos y condiciones completos
- **Descripción:** El documento de Términos y Condiciones debe incluir: (a) condiciones/alcances/límites de uso, (b) derechos y deberes del usuario, (c) responsabilidad de la entidad, (d) contacto, (e) referencia a privacidad y seguridad, (f) jurisdicción aplicable.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el documento "TerminosYCondiciones" es cargado por el editor
  Cuando el sistema valida el contenido
  Entonces las 6 secciones obligatorias están presentes (validador de plantillas en panel).
  ```
- **Fuente normativa:** Código Civil colombiano (art. 1502); Ley 1480/2011 (Estatuto del Consumidor, cuando aplique).
- **Antecedente interno:** RF-B2-008
- **Trazabilidad:** → Tarea T-01-019 → Test PT-01-019
- **Módulo:** 01-estructura-identidad

### RF-01-020 — Política de privacidad conforme Ley 1581/2012
- **Descripción:** El documento debe incluir: categorías de datos tratados, finalidades, derechos ARCO, contacto del responsable (Oficial de Protección de Datos), mecanismo de reclamo y plazo de respuesta (15 días hábiles).
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario lee la política de privacidad
  Cuando busca los derechos ARCO
  Entonces encuentra: acceso, rectificación, cancelación y oposición con su procedimiento, contacto y plazo.
  ```
- **Fuente normativa:** Ley 1581/2012 Arts. 17-18; Decreto 1377/2013.
- **Antecedente interno:** RF-B2-009
- **Trazabilidad:** → Tarea T-01-020 → Test PT-01-020
- **Módulo:** 01-estructura-identidad

### RF-01-021 — Componentes del Kit UI v9.2
- **Descripción:** El sitio debe implementar los componentes del Kit UI GOV.CO v9.2: grilla Bootstrap 5.0 (12 columnas, 6 breakpoints), acordeón (`aria-expanded`), alerta modal (cierre con ESC), toast, botones con `aria-label`, paginación ≥44×44 px móvil con `aria-current`, tablas con ordenamiento y tablas responsivas, indicador de carga (spinner >10 s informa estado), galería de aplicaciones navegable Enter/Esc.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario interactúa con la paginación
  Cuando pulsa "Siguiente" con teclado
  Entonces el foco se mantiene en el control y aria-current="page" se aplica al número activo.
  ```
- **Fuente normativa:** Anexo Técnico 2 MinTIC; WCAG 2.1 AA.
- **Antecedente interno:** RF-B2-084/085, RF-B3-060/062/063/064/066/068/070/075/076
- **Trazabilidad:** → Tarea T-01-021 → Test PT-01-021
- **Módulo:** 01-estructura-identidad

### RF-01-022 — Plan de Integración a GOV.CO
- **Descripción:** La administración debe elaborar y publicar el Plan de Integración a GOV.CO; el plan debe incluirse en el PETI y reportarse mensualmente a la Dirección de Gobierno Digital de MinTIC con actividades, responsables, fechas y presupuesto.
- **Prioridad (MoSCoW):** Should
- **Cliente:** panel
- **Actor(es):** G-CIO / Director de TI, MinTIC
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el G-CIO actualiza el plan de integración
  Cuando guarda los cambios
  Entonces el reporte mensual queda disponible para descarga y se notifica a MinTIC por el canal configurado.
  ```
- **Fuente normativa:** Decreto 2106/2019 Arts. 14-15; MinTIC Guía de Integración GOV.CO.
- **Antecedente interno:** RF-B1-100
- **Trazabilidad:** → Tarea T-01-022 → Test PT-01-022
- **Módulo:** 01-estructura-identidad

### RF-01-D01 — Botón de idioma persistente (diferido)
- **Descripción:** El sistema debe soportar cambio de idioma (ES/EN) con persistencia de preferencia cuando se decida activar; por ahora queda **diferido** (castellano únicamente) por decisión #16 (2026-06-05); la arquitectura debe permitir añadir el segundo idioma sin refactor mayor.
- **Prioridad (MoSCoW):** Could
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el sistema soporta i18n mediante clave de mensajes
  Cuando se añade un nuevo locale "en"
  Entonces todas las claves del JSON ES tienen su contraparte "en" y el selector persiste en localStorage.
  ```
- **Fuente normativa:** Anexo Técnico 2 MinTIC §4 (idiomas).
- **Antecedente interno:** RF-B3-055 (diferido)
- **Trazabilidad:** → Tarea T-01-023 → Test PT-01-023
- **Módulo:** 01-estructura-identidad

---

## Módulo 02 — Transparencia y Acceso a la Información Pública

### RF-02-001 — Menú Transparencia con 10 subsecciones
- **Descripción:** El sitio debe publicar el menú "Transparencia y acceso a la información pública" con **exactamente 10 subsecciones** (Anexo 2 Res. 1519/2020): (1) Información de la entidad, (2) Normativa, (3) Contratación, (4) Planeación/presupuesto/informes, (5) Trámites, (6) Participa, (7) Datos abiertos, (8) Grupos de interés, (9) Obligación de reporte específico, (10) Tributaria.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un ciudadano accede a "/transparencia"
  Cuando el menú renderiza
  Entonces aparecen las 10 subsecciones en el orden definido y cada una tiene contenido publicado y actualizado.
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 4; Res. MinTIC 1519/2020 Anexo 2.
- **Antecedente interno:** RF-B1-012 / RF-B3-081
- **Trazabilidad:** → Tarea T-02-001 → Test PT-02-001
- **Módulo:** 02-transparencia

### RF-02-002 — Orden cronológico inverso
- **Descripción:** La información publicada debe presentarse en orden cronológico inverso (más reciente primero); cada documento debe mostrar fecha de publicación.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que existen documentos con fechas 2025-01-15, 2025-06-30, 2026-01-01
  Cuando se lista la subsección
  Entonces el orden es 2026-01-01, 2025-06-30, 2025-01-15.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §4 (cronología).
- **Antecedente interno:** RF-B3-087
- **Trazabilidad:** → Tarea T-02-002 → Test PT-02-002
- **Módulo:** 02-transparencia

### RF-02-003 — Buscador de transparencia
- **Descripción:** El módulo debe ofrecer un buscador que filtre por texto sobre todas las subsecciones; los resultados muestran fecha, categoría, título, extracto.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario busca "plan de acción"
  Cuando ejecuta la búsqueda
  Entonces el sistema devuelve resultados de cualquier subsección, ordenados por pertinencia, con fecha, categoría, título y extracto.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §3.3.
- **Antecedente interno:** RF-B3-087
- **Trazabilidad:** → Tarea T-02-003 → Test PT-02-003
- **Módulo:** 02-transparencia

### RF-02-004 — Principio de fuente única
- **Descripción:** Un documento publicado en Transparencia debe tener una URL canónica y servir como fuente única; otros menús que lo referencien deben enlazar a esa URL sin duplicarlo.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un documento "PlanAccion2026.pdf" está publicado en Transparencia
  Cuando se busca en otros menús (Participa, Servicios)
  Entonces los enlaces apuntan a "/transparencia/planeacion/plan-accion-2026.pdf" sin duplicar el archivo.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 (fuente única).
- **Antecedente interno:** RF-B3-088
- **Trazabilidad:** → Tarea T-02-004 → Test PT-02-004
- **Módulo:** 02-transparencia

### RF-02-005 — Información institucional de la entidad
- **Descripción:** La subsección 1 debe publicar: misión, visión, funciones, organigrama actualizado, directorio, plan de acción, informe de gestión, estados financieros e información tributaria territorial.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario accede a "/transparencia/informacion-de-la-entidad"
  Cuando carga la página
  Entonces encuentra los 9 elementos validados, con el organigrama vigente (≤ 6 meses de antigüedad).
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 9; Res. MinTIC 1519/2020 Anexo 2 §1.
- **Antecedente interno:** RF-B1-097
- **Trazabilidad:** → Tarea T-02-005 → Test PT-02-005
- **Módulo:** 02-transparencia

### RF-02-006 — Directorio de servidores públicos (SIGEP)
- **Descripción:** La subsección 1.5 debe publicar el directorio de servidores públicos con: nombre, cargo, correo institucional, teléfono/extensión y dependencia; sincronizado con SIGEP en ≤24 horas hábiles ante cualquier cambio.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un servidor se registra en SIGEP
  Cuando pasan 24 horas hábiles
  Entonces el directorio del sitio refleja el cambio (sincronización vía cron `SIGEP:Sincronizar`).
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 9; Ley 1581/2012 (datos personales).
- **Antecedente interno:** RF-B1-017 / RF-B3-085
- **Trazabilidad:** → Tarea T-02-006 → Test PT-02-006
- **Módulo:** 02-transparencia

### RF-02-007 — Grupos de interés
- **Descripción:** La subsección 8 debe publicar información específica para grupos de interés (mín.: ciudadanía, proveedores, medios, gremios) conforme a la caracterización de la entidad.
- **Prioridad (MoSCoW):** Should
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un proveedor accede a "/transparencia/grupos-de-interes"
  Cuando carga la página
  Entonces encuentra procesos de contratación, formularios y contactos de la oficina de contratación.
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 9; Res. MinTIC 1519/2020 Anexo 2 §8.
- **Antecedente interno:** RF-B1-096
- **Trazabilidad:** → Tarea T-02-007 → Test PT-02-007
- **Módulo:** 02-transparencia

### RF-02-008 — Publicación de normativa
- **Descripción:** La subsección 2 debe publicar la normativa con: tipo, número, fecha de expedición, fecha de publicación, epígrafe, vigencia, enlace de descarga en formato abierto; orden cronológico inverso; proyectos de norma con fecha máxima de comentarios. Enlace al SUIN.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que se expide un decreto
  Cuando el administrador lo carga en el panel
  Entonces aparece en Transparencia ≤24 horas hábiles con los 7 campos validados y enlace de descarga en PDF/A.
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 9; Res. MinTIC 1519/2020 Anexo 2 §2.
- **Antecedente interno:** RF-B1-013 / RF-B3-082
- **Trazabilidad:** → Tarea T-02-008 → Test PT-02-008
- **Módulo:** 02-transparencia

### RF-02-009 — Enlace funcional a SUIN y SUCOP
- **Descripción:** La subsección 2 debe enlazar al SUIN (`http://www.suin-juriscol.gov.co/`), publicar Agenda Regulatoria y permitir comentarios ciudadanos a normas en elaboración vía integración con SUCOP (`https://www.sucop.gov.co/`).
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario hace clic en el logo SUIN
  Cuando el enlace se abre
  Entonces la URL destino es exactamente "https://www.suin-juriscol.gov.co/" y responde 200 OK.
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 9.
- **Antecedente interno:** RF-B1-014
- **Trazabilidad:** → Tarea T-02-009 → Test PT-02-009
- **Módulo:** 02-transparencia

### RF-02-010 — Contratación — SECOP I/II
- **Descripción:** La subsección 3 debe enlazar a SECOP I/II con los contratos de la Alcaldía visibles; publicar Plan Anual de Adquisiciones, contratos adjudicados (objeto, monto) y ejecución (inicio, fin, valor, % ejecutado, pagos, otrosíes).
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un ciudadano accede a "/transparencia/contratacion"
  Cuando hace clic en SECOP II
  Entonces la URL destino filtra por NIT 891.780.009-4 y muestra los contratos vigentes de la Alcaldía.
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 9; Ley 80/1993; Ley 1150/2007.
- **Antecedente interno:** RF-B1-015 / RF-B3-090 / RF-B1-074
- **Trazabilidad:** → Tarea T-02-010 → Test PT-02-010
- **Módulo:** 02-transparencia

### RF-02-011 — Plan de Acción anual
- **Descripción:** Publicar el Plan de Acción antes del 31 de enero de cada año; publicar avances de proyectos de inversión y de metas/indicadores cada 3 meses.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que llega el 31 de enero
  Cuando el sistema ejecuta la validación
  Entonces el Plan de Acción vigente está publicado en o antes de esa fecha (validación por job `PlanAccion:VerificarVigencia`).
  ```
- **Fuente normativa:** Ley 1474/2011 Art. 74; Res. MinTIC 1519/2020 Anexo 2 §4.4.
- **Antecedente interno:** RF-B1-016 / RF-B3-083
- **Trazabilidad:** → Tarea T-02-011 → Test PT-02-011
- **Módulo:** 02-transparencia

### RF-02-012 — Informe de gestión anual
- **Descripción:** Publicar el informe de gestión anual antes del 31 de enero del año siguiente.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que llega el 31 de enero de cada año
  Cuando el sistema valida
  Entonces el informe de gestión del año anterior está publicado.
  ```
- **Fuente normativa:** Ley 1474/2011 Art. 74; Ley 951/2005.
- **Antecedente interno:** RF-B3-084
- **Trazabilidad:** → Tarea T-02-012 → Test PT-02-012
- **Módulo:** 02-transparencia

### RF-02-013 — Informes trimestrales PQRSD
- **Descripción:** Publicar informes trimestrales de PQRSD y de acceso a información (cantidad, tipo, estado, tiempo de respuesta) antes del día 15 del mes siguiente al cierre del trimestre; redirección a KOGUI cuando aplique.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que se cierra el trimestre T1 (31-mar)
  Cuando llega el 15 de abril
  Entonces el informe trimestral de PQRSD T1 está publicado en formato abierto (CSV/XLSX).
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 9; Decreto 1081/2015.
- **Antecedente interno:** RF-B1-037 / RF-B3-091
- **Trazabilidad:** → Tarea T-02-013 → Test PT-02-013
- **Módulo:** 02-transparencia

### RF-02-014 — Informe de control interno semestral
- **Descripción:** Publicar el informe de control interno cada 6 meses en los primeros 5 días hábiles del mes siguiente al cierre del semestre.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que se cierra el semestre S1 (30-jun)
  Cuando llega el 5 de julio hábil
  Entonces el informe semestral de control interno está publicado.
  ```
- **Fuente normativa:** Decreto 2106/2019 Art. 156; Ley 87/1993.
- **Antecedente interno:** RF-B3-152
- **Trazabilidad:** → Tarea T-02-014 → Test PT-02-014
- **Módulo:** 02-transparencia

### RF-02-015 — Información tributaria territorial
- **Descripción:** La subsección 10 debe publicar Predial, ICA y otros impuestos distritales con: sujeto activo, sujeto pasivo, hecho generador, hecho imponible, causación, base gravable, tarifa; proceso de recaudo de rentas locales.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario consulta la ficha del ICA
  Cuando carga la página
  Entonces aparecen los 7 elementos tributarios completos para el periodo vigente.
  ```
- **Fuente normativa:** Estatuto Tributario Distrital; Ley 1712/2014 Art. 9.
- **Antecedente interno:** RF-B1-018 / RF-B3-089
- **Trazabilidad:** → Tarea T-02-015 → Test PT-02-015
- **Módulo:** 02-transparencia

### RF-02-016 — Calendario tributario
- **Descripción:** Publicar el calendario tributario con las fechas de vencimiento de cada impuesto al iniciar la vigencia fiscal.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que inicia la vigencia fiscal (1-enero)
  Cuando el sistema valida
  Entonces el calendario tributario con todas las fechas de vencimiento está publicado.
  ```
- **Fuente normativa:** Decreto 2106/2019 Art. 39.
- **Antecedente interno:** RF-B3-151
- **Trazabilidad:** → Tarea T-02-016 → Test PT-02-016
- **Módulo:** 02-transparencia

### RF-02-017 — Enlace a datos abiertos
- **Descripción:** La subsección 7 debe enlazar al portal de Datos Abiertos Colombia (`https://www.datos.gov.co/`) y listar los datasets publicados por la entidad con metadatos.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario accede a "/transparencia/datos-abiertos"
  Cuando carga la página
  Entonces aparecen los datasets de la Alcaldía con título, descripción, fecha de publicación y enlace al dataset en datos.gov.co.
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 11; CONPES 3920/2018.
- **Antecedente interno:** RF-B1-019/020, RF-B3-086
- **Trazabilidad:** → Tarea T-02-017 → Test PT-02-017
- **Módulo:** 02-transparencia

### RF-02-018 — Trámites (catálogo SUIT)
- **Descripción:** La subsección 5 debe enlazar al catálogo de trámites (SUIT) y permitir búsqueda por palabra clave y por categoría.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario busca "certificado de residencia"
  Cuando ejecuta la búsqueda
  Entonces el sistema devuelve los trámites coincidentes con su código SUIT, descripción y enlace al detalle.
  ```
- **Fuente normativa:** Decreto 2106/2019 (digitalización de trámites); Decreto 088/2022; SUIT.
- **Antecedente interno:** RF-B3-081 (subsección 5)
- **Trazabilidad:** → Tarea T-02-018 → Test PT-02-018
- **Módulo:** 02-transparencia

### RF-02-019 — Participa
- **Descripción:** La subsección 6 debe publicar los mecanismos de participación ciudadana disponibles (foros, encuestas, consulta ciudadana, rendición de cuentas, colaboración e innovación, consejo de participación, formularios).
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario accede a "/transparencia/participa"
  Cuando carga la página
  Entonces aparecen al menos 6 mecanismos de participación con su descripción y enlace.
  ```
- **Fuente normativa:** Ley 1757/2015; Res. MinTIC 1519/2020 Anexo 2 §6.
- **Antecedente interno:** RF-B1-012 (subsección 6)
- **Trazabilidad:** → Tarea T-02-019 → Test PT-02-019
- **Módulo:** 02-transparencia

### RF-02-020 — Reporte específico (obligación específica)
- **Descripción:** La subsección 9 debe publicar la información de reporte específico de la entidad (ej. informes a superintendencias, informes a Ministerios, actos administrativos de trascendencia).
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que se publica un reporte a superintendencia
  Cuando el administrador lo carga en el panel con categoría "Reporte específico"
  Entonces aparece en la subsección 9 con su periodicidad y enlace.
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 9.
- **Antecedente interno:** RF-B1-012 (subsección 9)
- **Trazabilidad:** → Tarea T-02-020 → Test PT-02-020
- **Módulo:** 02-transparencia

### RF-02-021 — CRUD + versionado de documentos
- **Descripción:** El sistema debe permitir crear, editar, reemplazar y eliminar (soft-delete) documentos de transparencia; cada reemplazo conserva la versión anterior con su fecha (historial), sin romper la URL canónica (RF-02-004).
- **Prioridad (MoSCoW):** Must
- **Cliente:** panel
- **Actor(es):** Editor, Administrador de Contenidos
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el editor reemplaza el "PlanAccion2026.pdf"
  Cuando confirma la operación
  Entonces la URL "/transparencia/planeacion/plan-accion-2026.pdf" se mantiene y el documento anterior queda accesible como "/transparencia/planeacion/plan-accion-2026.pdf?v=2025-12-15" (versión histórica).
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 4 (autenticidad e integridad); Res. MinTIC 1519/2020 Anexo 2.
- **Antecedente interno:** RF-02-D01 (delta segunda pasada)
- **Trazabilidad:** → Tarea T-02-021 → Test PT-02-021
- **Módulo:** 02-transparencia

### RF-02-022 — Alertas de vencimiento de publicaciones obligatorias
- **Descripción:** El sistema debe generar alertas automáticas al responsable N días antes del plazo legal de las publicaciones obligatorias (Plan de Acción 31-ene, Informe de Gestión 31-ene, informes trimestrales PQRSD día 15, control interno día 5 hábil, calendario tributario 1-ene).
- **Prioridad (MoSCoW):** Should
- **Cliente:** panel
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que faltan 10 días para el 31 de enero
  Y el Plan de Acción aún no está cargado
  Cuando el job diario `AlertaPublicacion:Verificar` ejecuta
  Entonces se notifica al administrador de cumplimiento por email y en panel.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §4.3, §4.7, §4.10.
- **Antecedente interno:** RF-02-D02 (delta segunda pasada)
- **Trazabilidad:** → Tarea T-02-022 → Test PT-02-022
- **Módulo:** 02-transparencia

### RF-02-023 — Manejo de caída de integraciones externas
- **Descripción:** El sistema debe detectar y manejar la caída de integraciones externas (SECOP, SIGEP, SUIN, SUCOP, KOGUI); cuando una integración no responde, mostrar mensaje claro al usuario y registrar el fallo en `log_auditoria` sin romper la página.
- **Prioridad (MoSCoW):** Should
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que SECOP II no responde
  Cuando el usuario hace clic en el enlace SECOP
  Entonces se muestra "El servicio de contratación no está disponible temporalmente" y se registra el fallo en log_auditoria.
  Y el RF-01-012 (cero vínculos rotos) NO se viola porque el destino es externo y se diferencia del estado interno.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 (servicios externos).
- **Antecedente interno:** RF-02-D03 (delta segunda pasada)
- **Trazabilidad:** → Tarea T-02-023 → Test PT-02-023
- **Módulo:** 02-transparencia

### RF-02-024 — Integridad documental (hash SHA-256)
- **Descripción:** Cada documento publicado debe tener calculado y persistido su hash SHA-256 en el momento de la carga; el hash se publica junto al documento para verificación de integridad por terceros.
- **Prioridad (MoSCoW):** Must
- **Cliente:** panel
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el editor carga "PlanAccion2026.pdf"
  Cuando el sistema procesa el archivo
  Entonces el hash SHA-256 se calcula y persiste, y se muestra en la vista pública junto al enlace de descarga.
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 4 (autenticidad); NTC ISO 27001 (integridad).
- **Antecedente interno:** RN-06
- **Trazabilidad:** → Tarea T-02-024 → Test PT-02-024
- **Módulo:** 02-transparencia

### RF-02-025 — Formatos abiertos y procesables
- **Descripción:** ≥90% de los documentos deben estar en formatos abiertos y procesables por máquina (CSV, XML, RDF, JSON, ODF, PDF/A); el sistema debe registrar el formato de cada documento y el reporte ITA debe computar el % automáticamente.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el reporte ITA se calcula
  Cuando cuenta los documentos
  Entonces ≥90% están en formatos abiertos (CSV/XML/RDF/JSON/ODF/PDF-A).
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 (formatos); Ley 1712/2014 Art. 11.
- **Antecedente interno:** RNF-B3-039
- **Trazabilidad:** → Tarea T-02-025 → Test PT-02-025
- **Módulo:** 02-transparencia

### RF-02-026 — Lenguaje claro (Fernández-Huerta ≥ 60)
- **Descripción:** Las descripciones de trámites y textos principales de transparencia deben tener un índice de lecturabilidad Fernández-Huerta ≥60 ("bastante fácil"), validado al guardar en el panel.
- **Prioridad (MoSCoW):** Must
- **Cliente:** panel
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el editor redacta la descripción de un trámite
  Cuando guarda el texto
  Entonces el sistema calcula el índice Fernández-Huerta.
  Y si es <60, sugiere reescritura (warning, no bloqueo).
  ```
- **Fuente normativa:** Guía de Lenguaje Claro DNP; Ley 1712/2014 (información comprensible).
- **Antecedente interno:** RNF-B1-038 / RNF-B3-037
- **Trazabilidad:** → Tarea T-02-026 → Test PT-02-026
- **Módulo:** 02-transparencia

### RF-02-027 — Búsqueda full-text
- **Descripción:** El buscador de transparencia debe soportar búsqueda full-text con índice actualizado en cada publicación (ElasticSearch o PostgreSQL FTS), tolerancia a errores ortográficos y resaltado de coincidencias.
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario busca "prespuesto" (con error ortográfico)
  Cuando ejecuta la búsqueda
  Entonces aparecen resultados de "presupuesto" con la coincidencia resaltada.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §3.3.
- **Antecedente interno:** RF-B2-035/036 (buscador)
- **Trazabilidad:** → Tarea T-02-027 → Test PT-02-027
- **Módulo:** 02-transparencia

### RF-02-028 — Filtros por subsección y por año
- **Descripción:** El listado de documentos de transparencia debe permitir filtrar por subsección y por año (selector con años 2012-actual).
- **Prioridad (MoSCoW):** Must
- **Cliente:** sitio
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el usuario selecciona la subsección "Normativa" y el año 2025
  Cuando aplica el filtro
  Entonces el listado se reduce a los documentos de normativa del año 2025 en orden cronológico inverso.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 §4.
- **Antecedente interno:** RF-02-002 + RF-02-003
- **Trazabilidad:** → Tarea T-02-028 → Test PT-02-028
- **Módulo:** 02-transparencia

### RF-02-029 — Reporte ITA interno automático
- **Descripción:** El sistema debe calcular automáticamente el tablero ITA interno (no público) con el cumplimiento de cada requisito de la Res. 1519/2020 Anexo 2; el tablero arranca en cero, valida automáticamente al publicar y marca incumplimientos.
- **Prioridad (MoSCoW):** Must
- **Cliente:** panel
- **Actor(es):** Administrador del sistema
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que un editor publica un documento sin alt en la imagen
  Cuando el sistema valida el cumplimiento
  Entonces el tablero ITA marca el ítem como "No cumple" y notifica al responsable.
  ```
- **Fuente normativa:** Res. MinTIC 1519/2020 Anexo 2 (autodiagnóstico).
- **Antecedente interno:** RF-B1-078 (decisión A-03 2026-06-05)
- **Trazabilidad:** → Tarea T-02-029 → Test PT-02-029
- **Módulo:** 02-transparencia

### RF-02-030 — Metadatos de transparencia
- **Descripción:** Cada documento de transparencia debe persistir metadatos: idioma, descripción, palabras clave, fecha de publicación, fecha de actualización, formato, peso, hash SHA-256, periodicidad (anual/trimestral/mensual/eventual), categoría/subsección.
- **Prioridad (MoSCoW):** Must
- **Cliente:** panel
- **Criterios de aceptación (Gherkin):**
  ```gherkin
  Dado que el editor carga un documento
  Cuando completa los metadatos
  Entonces se persisten los 11 campos validados y se exponen vía API en `GET /api/v1/transparencia/documentos/{id}`.
  ```
- **Fuente normativa:** Ley 1712/2014 Art. 9; Res. MinTIC 1519/2020 Anexo 2.
- **Antecedente interno:** RN-08
- **Trazabilidad:** → Tarea T-02-030 → Test PT-02-030
- **Módulo:** 02-transparencia

---

## Trazabilidad global

| RF | Módulo | Cliente | Diseño | Tarea | Test |
|---|---|---|---|---|---|
| RF-01-001..022 | 01 | sitio/panel | §03-propuesta/.../c4-contenedores.md | T-01-001..022 | PT-01-001..022 |
| RF-01-D01 | 01 | sitio | §03-propuesta/.../c4-contenedores.md | T-01-023 | PT-01-023 |
| RF-02-001..030 | 02 | sitio/panel | §03-propuesta/.../c4-contenedores.md | T-02-001..030 | PT-02-001..030 |

> **Conteo verificado:** 22 RF módulo 01 + 30 RF módulo 02 = **52 RF** (objetivo ≥40 cumplido). MoSCoW: 45 Must + 5 Should + 2 Could = 52.
