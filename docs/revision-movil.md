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
| Botón de accesibilidad a media altura: tapaba el final de las líneas en **las 20 rutas**; en horizontal se montaba sobre el botón del menú. | **Tercera pasada (2026-10-29).** La 1.ª versión lo anclaba abajo a la derecha (contradecía D1); la 2.ª reservaba un carril de 44–56 px que **ocupaba espacio** y estrechaba el texto en móvil. Se retiran el carril y también la columna reservada desde 992 px: el círculo es **fijo, centrado a la derecha, con 0,5 rem de margen, igual en todas las pantallas y por encima del contenido, sin reservar espacio y sin desbordar la ventana** (D1). En teléfono mide 44 px (el mínimo táctil) para cubrir lo menos posible. Se probó y **se descartó** una variante en la cabecera por debajo de 992 px (Kit UI 9.2); el cliente no la aceptó. Pruebas: «Casos de prueba del botón de accesibilidad». |
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

## Límites de esta revisión

- Emulación en Chromium: **no** se probó en un iPhone ni en un Android real, ni
  con Safari ni Firefox móvil (entra en T-29).
- No hubo lector de pantalla. El banner plegado/desplegado se probó con clics y
  con axe; el recorrido con teclado y con VoiceOver/TalkBack queda sin probar.
- **Límite que no se puede quitar sin reservar espacio:** el círculo es fijo, está a media altura y **no reserva espacio a ningún ancho**, así que **al desplazarse pasa por encima** de lo que haya en el borde derecho: en un teléfono el contenido llega a 12 px de él y, entre 992 y ~1430 px, los contenedores de Bootstrap dejan menos margen que el círculo (puede cubrir una tarjeta, el botón de buscar o la flecha «siguiente» del carrusel mientras pasa). Las exigencias «igual en todas las pantallas» (D1), «no ocupa espacio» y «no cubre contenido» (HU-07-D02) no caben juntas; la prueba (`movil.mjs` §2b) informa cuánto cubre en cada ejecución. El carril de teléfono y la columna reservada de escritorio lo evitaban a costa de ocupar espacio y se descartaron.
- **«Volver arriba»** (Kit + RF-B3-061: flotante, inferior derecha) sigue cubriendo contenido mientras se desplaza; sólo aparece tras desplazar.
- **Variante de cabecera descartada.** Se propuso al cliente (2026-10-29) llevar el botón a la cabecera por debajo de 992 px, apoyándose en el Kit UI 9.2 y CAG-07; **no fue aceptada**, así que no se aplica. La propuesta queda registrada como «Revisión 3» en `docs/archivo/plan-barra-accesibilidad.md`.
- Medida analítica de lo que cubre el círculo: ignora textos sólo para lectores de pantalla y las imágenes o enlaces que ocupan el ancho entero (el cartel del carrusel).

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

## Casos de prueba del botón de accesibilidad

Criterios de aceptación (de `estructura-identidad.md`, el plan del botón y el Kit UI 9.2) y cómo se verifica cada uno. «Auto.» = prueba automática (`tests/diseno.mjs` CAG-07, `tests/movil.mjs` §2/§2b/§4/§6, `tests/verificar-modos-contraste.mjs` §3, `tests/e2e/humo.spec.mjs`); «QA» = pasos manuales para el equipo de calidad.

| ID | Criterio | Pasos | Resultado esperado | Cómo |
|---|---|---|---|---|
| TC-ACC-01 | D1 / CAG-07: círculo fijo, centrado a la derecha, igual en todas las pantallas | Abrir `/` a 320, 390, 667, 767, 800, 991, 992, 1280 y 1536 px y desplazar | **Un** círculo visible, fijo, centrado verticalmente, pegado al borde derecho, fuera de la cabecera; no se oculta a ningún ancho | Auto. + QA en un teléfono y una tableta reales |
| TC-ACC-02 | No ocupa espacio a ningún ancho | Medir el contenedor de `main` a 320/390/667/800/991/992/1300/1536 px | Mide lo que le corresponde a Bootstrap (toda la ventana en teléfono; 540, 720, 960, 1140 o 1320 px) con su relleno derecho de 12 px: sin carril ni límite de ancho | Auto. (`movil.mjs` §2) |
| TC-ACC-03 | Límite declarado: el círculo pasa por encima del borde derecho al desplazar | Desplazar 17 rutas a 320–1920 px y observar el borde derecho | Se informa cuántas líneas y controles cubre; **no** se exige cero (exigirlo obliga a reservar espacio) | Auto. (informativo, `movil.mjs` §2b) + QA visual |
| TC-ACC-04 | El círculo no desborda la ventana | A 320, 390, 800, 992 y 1000 px medir el círculo y `scrollWidth` | Dentro de la ventana; `scrollWidth` = ancho de ventana | Auto. (`diseno.mjs` CAG-07, `movil.mjs` §4) |
| TC-ACC-05 | RNF-07-D01: operable desde 320 px | Abrir el panel con el círculo a 320, 390 y 800 px | El panel se abre, cabe en la pantalla y se cierra con `Escape` | Auto. (`movil.mjs` §3) |
| TC-ACC-06 | RNF-B3-005: área activa ≥ 44 × 44 px | Medir el círculo en cada ancho | 44 px (teléfono, ≤ 575 px y pantallas bajas) y 48 px (resto) | Auto. |
| TC-ACC-07 | Panel con todas las funciones | Abrir con Intro a 1280, 800 y 390 px | Diálogo, 4 modos de contraste, 5 interruptores, Centro de Relevo, ayuda, restablecer | Auto. |
| TC-ACC-08 | CC17 / foco: `Escape` cierra y devuelve el foco | Abrir y cerrar | `aria-expanded` vuelve a `false`; el foco está en el círculo | Auto. |
| TC-ACC-09 | Modos de contraste no rompen la posición | Activar los 4 modos y desplazar | El círculo sigue fuera del subárbol filtrado y centrado | Auto. |
| TC-ACC-10 | RF-B3-061 / Kit: «Volver arriba» no choca con el círculo | Desplazar a cada ancho | Las dos cajas no se solapan | Auto. |
| TC-ACC-11 | Un solo círculo en el documento y en el árbol de accesibilidad | Contar los elementos del círculo; con un lector de pantalla, listar los botones | Exactamente 1 círculo (el pie ofrece además su propio botón «Ajustes de accesibilidad», a propósito: CC12) | Auto. (`diseno.mjs` CAG-07). **QA:** confirmar con NVDA/VoiceOver/TalkBack en dispositivos reales |
| TC-ACC-12 | Defecto previo: el pie desbordaba a 992–1008 px | Medir `scrollWidth` a 992 y 1000 px | Sin desbordamiento (las redes del pie saltan de fila) | Auto. (`movil.mjs` §4) |
| TC-ACC-13 | Defecto previo: «Inicio» se partía en la miga de pan | Abrir `/tramites` a 320 y 390 px | «Inicio» en una línea (≤ 50 px de alto) | Auto. (`movil.mjs` §6) |

**Reversión:** revertir el PR de esta pasada devuelve el diseño anterior (círculo con carril de texto hasta 851 px, que ocupaba espacio); no hay migración ni datos implicados.

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
