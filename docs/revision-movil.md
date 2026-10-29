# Revisión móvil de la Sede (Playwright)

Qué se midió, qué se corrigió y qué queda abierto. La puerta que lo vigila es
`sitio/tests/movil.mjs` (`npm run test:movil`, con `BASE_URL` apuntando a un
sitio con datos).

## Método

- Chromium con emulación táctil (`isMobile`, `hasTouch`) a **390×844, 360×740 y
  320×640**, más 320×568 (iPhone SE) y 667×375 (horizontal) para el banner y el
  botón de accesibilidad.
- 20 rutas públicas: portada, transparencia, atención, trámites (listado y
  ficha), PQRSD, realizar una petición, seguimiento, noticias, portales,
  servicios, normativa, buscar, mapa del sitio, accesibilidad, políticas,
  participa (índice y subpágina), verificar y la página 404.
- Por ruta: desbordamiento horizontal, objetivos táctiles, texto menor de 12 px,
  solape del botón flotante con el texto y captura completa.

> **Trampa que costó un ciclo.** Con desbordamiento horizontal, el navegador
> móvil ensancha la ventana de diseño y `innerWidth` crece con ella: comparar
> `scrollWidth` con `innerWidth` da siempre «sin desbordamiento». La puerta
> compara con el ancho pedido.

## Hallazgos y correcciones

| Defecto (medido) | Corrección |
|---|---|
| Banner de cookies: **88 %** de la pantalla a 390 px y **100 %** a 360, 320×568 y en horizontal; los botones quedaban fuera de vista. Causa: categorías siempre desplegadas y `flex: 1 1 40ch`, que en columna se leía como **alto** y reservaba ~320 px de aire. | Categorías plegadas con «Elegir por categorías» (`aria-expanded`); «Aceptar todas» y «Rechazar opcionales» de igual peso; barra de botones pegada al borde inferior; máximo 70 % de la altura. En escritorio no cambia. |
| Botón de accesibilidad a media altura: tapaba el final de las líneas en **las 20 rutas**; en horizontal se montaba sobre el botón del menú. | **Corregido el 2026-10-29 (segunda pasada).** La primera versión lo anclaba abajo a la derecha en móvil, y eso **contradecía el requisito**: el plan del botón (D1, revisión 2, indicación del cliente del 2026-10-01) exige un círculo fijo, centrado verticalmente en el lado derecho e igual en todas las pantallas, y la HU-07-D02 pide que no se solape con el contenido. Se revirtió el anclaje y «Volver arriba» recuperó su sitio; el solape se resuelve apartando el **texto** (carril de 44 px a la derecha en los contenedores y en la leyenda del carrusel, hasta 851 px), no moviendo el botón. Medido: 0 líneas tapadas a 320, 390, 600, 767, 800, 851, 900 y 1024 px en 17 rutas; la prueba automática (`tests/movil.mjs`, sección 2b) repite la medición en 320, 390, 767, 800 y 851 px. Solo cuenta líneas de **texto**: el botón sigue pasando por encima de imágenes y tarjetas, como pide el plan del botón (flota). |
| Panel de accesibilidad: el aviso fijo restaba ~90 px y cortaba por la mitad el título del grupo al desplazar. | El aviso va dentro de la zona que se desplaza; menos relleno en pantallas estrechas. |
| Indicadores del carrusel de 16×16 px; «Reproducir» de 34 px. | Área de 44×44 px con pseudoelemento y paso de 44 px; 44 px de alto. |
| Botón del menú GOV.CO de 42×42; `.btn-govco` de 42 px; «Ver la ficha completa» de 24 px; enlaces de la 404 de 39 px. | 44 px. |
| **Lupa del buscador invisible** (en escritorio también): el Kit la dibuja en blanco sobre un círculo cobalto que aquí no existe. | Filtro «cobalto» del propio Kit sobre el icono. |
| Migas de pan con sangría de 58 px y niveles apilados. El Kit fuerza `padding: 0 46px !important`. | Margen negativo **sólo a la izquierda** (a la derecha desbordaba la página). Sin `!important`. |
| Radios y casillas de `.form-check` pegados a su etiqueta. | Fila con `gap`. |
| `/accesibilidad`: el correo de notificaciones judiciales sacaba la página **30 px** (390) y **99 px** (320). | `overflow-wrap: break-word` en `main`; el pie parte los correos tras la arroba. |
| Rótulo del buscador cortado a media letra. | Menos relleno y puntos suspensivos. (La primera versión de esta regla **nunca se aplicó**: el Kit usa `input[type=text].…` y gana por especificidad; se midieron 20 px de relleno pese a declarar 14.) |
| Región desplazable de la tabla de conformidad sin foco de teclado (axe: `scrollable-region-focusable`). | `tabindex="0"`, `role="region"` y nombre. |

## Efectos colaterales a conocer

- La **lupa** cambia el aspecto de **todas** las capturas de escritorio (una
  franja de ~15 px en la cabecera). Es la corrección, no una regresión.
- Alto contraste: el correo del pie pasa de azul sobre gris oscuro a blanco
  subrayado. Causa probable (no verificada): el `<span>` que ahora envuelve el
  texto hereda el color del modo en vez del azul del Kit. Es una mejora, pero
  accidental.
- Las referencias de `tests/visual/referencia/` deben regenerarse en la máquina de
  quien las mantiene (`node scripts/captura-visual.mjs --guardar`).
- El texto de los contenedores de `main` es 44 px más estrecho hasta 851 px (el carril del botón de accesibilidad).

## Límites de esta revisión

- Emulación en Chromium: **no** se probó en un iPhone ni en un Android real, ni
  con Safari ni Firefox móvil (entra en T-29).
- No hubo lector de pantalla. El banner plegado/desplegado se probó con clics y
  con axe; el recorrido con teclado y con VoiceOver/TalkBack queda sin probar.
- El círculo de accesibilidad **pasa por encima** de bloques que no son texto (imágenes, tarjetas de color) al desplazarse: el carril sólo aparta el texto y los campos de los contenedores. La medida cuenta líneas de texto, no formularios ni imágenes.

## Ajuste a los requisitos del Módulo 01 (segunda pasada)

Lo que la primera pasada dejó abierto, contrastado con `estructura-identidad.md`:

| Requisito | Medido antes | Corrección |
|---|---|---|
| **RF-B1-005** buscador visible en el encabezado, ≥ 27 caracteres | En móvil el campo de la cabecera medía ~137 px a 390 (≈ 16 caracteres) y ~67 px a 320; además el menú traía **un segundo buscador** (dos regiones `search` con el mismo nombre). | Un solo buscador, el de la cabecera, en **su propia fila a todo el ancho** por debajo de 768 px (como hace el propio Kit con `.search-mobile`). El menú ya no recibe el buscador. Botón de borrar sin reservar sus 44 px cuando está vacío. Letra de 16 px (el Kit la fija en 15). |
| **WCAG 2.5.3 / RF-B3-066** el rótulo visible debe estar en el nombre accesible | axe `label-content-name-mismatch`: «Reproducir» (`aria-label` «Reanudar…»), botones A−/A+ del panel y 4 enlaces del pie (redes sociales). | «Reproducir las diapositivas automáticamente»; A−/A+ con texto para lector de pantalla en lugar de `aria-label`; las redes empiezan por el usuario visible («@usuario, Facebook de la Entidad…»); el correo usa el mismo texto que se ve. 0 violaciones en portada, panel y `/accesibilidad`. |

Cómo se midió lo de los 27 caracteres: se mide, con el motor de texto del navegador,
cuántos caracteres de una muestra de 27 (`licencia de construcción ab`) caben en el
área útil del campo **vacío**: 27 a 320, 360 y 390 px. Con texto escrito (y el botón de
borrar a la vista) los 27 caben a partir de 360 px; a 320 el campo se desplaza, que es
el comportamiento normal de un `<input>`.

**Límite honesto:** «27 caracteres» no define el ancho de carácter. Se usó una muestra
de letras de ancho medio; con mayúsculas o con «m/w» cabrían menos.

## Abierto

- **Imagen del carrusel en teléfono: de 216 a 136 px** (≤ 575 px), a petición de José
  sobre una captura (la franja gris entre el menú y el texto azul). Es un valor de
  diseño en `sitio.css`; sube si la Entidad prefiere más foto.
- La diapositiva de la portada sigue usando la imagen gris de marcador del Kit. **AC-23.3 exige que ninguna imagen sea
  `fondo-gris-carrusel.jpg`**: es trabajo de T-23 (carrusel con contenido) y necesita
  las imágenes de la Entidad; inventarlas contradice la regla de no crear contenido
  institucional.
- En 768–991 px la cabecera sigue con el buscador en línea entre el logotipo y las
  aplicaciones (615 px de ancho a 768): no se tocó porque ahí cabe.
- La puerta `tests/accesibilidad.mjs` arranca el servidor sin la URL de la API y
  se queda sin memoria en este entorno; no se pudo ejecutar completa. El mismo
  axe se pasó a mano sobre portada, listado, formulario, accesibilidad y panel.
