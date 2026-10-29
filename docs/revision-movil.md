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
| Botón de accesibilidad a media altura: tapaba el final de las líneas en **las 20 rutas**; en horizontal se montaba sobre el botón del menú. | Esquina inferior derecha hasta 767 px (y en pantallas de ≤480 px de alto), con `env(safe-area-inset-*)`. «Volver arriba» queda justo encima. En escritorio sigue centrado. |
| Panel de accesibilidad: el aviso fijo restaba ~90 px y cortaba por la mitad el título del grupo al desplazar. | El aviso va dentro de la zona que se desplaza; menos relleno en pantallas estrechas. |
| Indicadores del carrusel de 16×16 px; «Reproducir» de 34 px. | Área de 44×44 px con pseudoelemento y paso de 44 px; 44 px de alto. |
| Botón del menú GOV.CO de 42×42; `.btn-govco` de 42 px; «Ver la ficha completa» de 24 px; enlaces de la 404 de 39 px. | 44 px. |
| **Lupa del buscador invisible** (en escritorio también): el Kit la dibuja en blanco sobre un círculo cobalto que aquí no existe. | Filtro «cobalto» del propio Kit sobre el icono. |
| Migas de pan con sangría de 58 px y niveles apilados. El Kit fuerza `padding: 0 46px !important`. | Margen negativo **sólo a la izquierda** (a la derecha desbordaba la página). Sin `!important`. |
| Radios y casillas de `.form-check` pegados a su etiqueta. | Fila con `gap`. |
| `/accesibilidad`: el correo de notificaciones judiciales sacaba la página **30 px** (390) y **99 px** (320). | `overflow-wrap: break-word` en `main`; el pie parte los correos tras la arroba. |
| Rótulo del buscador cortado a media letra. | Menos relleno y puntos suspensivos. |
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
- `tests/verificar-modos-contraste.mjs` esperaba el círculo **centrado** a
  cualquier ancho; ahora espera «anclado abajo» hasta 767 px.

## Límites de esta revisión

- Emulación en Chromium: **no** se probó en un iPhone ni en un Android real, ni
  con Safari ni Firefox móvil (entra en T-29).
- No hubo lector de pantalla. El banner plegado/desplegado se probó con clics y
  con axe; el recorrido con teclado y con VoiceOver/TalkBack queda sin probar.
- Los **dos botones flotantes** (accesibilidad y «Volver arriba») siguen
  cruzando contenido al desplazar; ahora sólo en la esquina.

## Abierto (no se tocó)

- La diapositiva de la portada usa la imagen gris de marcador del Kit: en móvil
  ocupa ~270 px de pantalla sin información. Es contenido, lo decide la Entidad.
- En el menú móvil hay **dos buscadores** (cabecera y menú).
- axe marca `label-content-name-mismatch` (WCAG 2.5.3, «serio») en «Reproducir»,
  en los botones de tamaño de texto del panel y en enlaces del pie cuyo
  `aria-label` no contiene el texto visible. Existía antes de esta revisión.
- La puerta `tests/accesibilidad.mjs` arranca el servidor sin la URL de la API y
  se queda sin memoria en este entorno; no se pudo ejecutar completa. El mismo
  axe se pasó a mano sobre portada, listado, formulario, accesibilidad y panel.
