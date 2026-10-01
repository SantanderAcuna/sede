# Plan — Barra de Accesibilidad Web de la Sede Electrónica

> **Naturaleza del documento.** Este es el plan de ejecución de un componente, no el
> plan maestro del proyecto (ése es [`plan.md`](plan.md)). Cada afirmación sobre el
> estado del mundo lleva su evidencia —ruta y línea— y cada decisión lleva su motivo o
> su opción descartada. Lo que no está decidido está en la §16 y **no se implementa**
> hasta que se ratifique.
>
> **Versión:** 1.0 · **Fecha:** 2026-10-01 · **Estado:** propuesto, pendiente de ratificación
> **Alcance:** `sitio/` (Nuxt 4, SSR) · componente `BarraAccesibilidad` y su estado
> **Norma que lo motiva:** Resolución MinTIC 1519 de 2020, Anexo 1 · WCAG 2.1 AA · vigente desde 2022-01-01
> **Brief de origen:** «Brief profesional para diseño y construcción de Barra de Accesibilidad Web» (cliente, 2026-10-01)

---

## Índice

| § | Sección |
|---|---|
| 1 | Resumen ejecutivo |
| 2 | Estado de partida verificado |
| 3 | Brecha frente al brief |
| 4 | Decisiones de arquitectura |
| 5 | Modelo de estado y persistencia |
| 6 | Especificación de UI |
| 7 | Especificación de interacción y ARIA |
| 8 | Teclado, foco y lector de pantalla |
| 9 | CSS: clases de modo y sus trampas |
| 10 | Preferencias del sistema operativo |
| 11 | Archivos afectados |
| 12 | Fases y puertas |
| 13 | Pruebas y criterios de aceptación |
| 14 | Matriz CC1–CC32 |
| 15 | Desviaciones declaradas y riesgos |
| 16 | Decisiones por ratificar |
| 17 | Trazabilidad |

---

## 1. Resumen ejecutivo

Hoy la Sede tiene **dos** juegos de controles de accesibilidad en paralelo —una barra
lateral fija en escritorio y una lista de botones en el pie— y ninguno cubre los once
componentes mínimos que pide el brief. Este plan los sustituye por **un solo sistema**:

| Pieza | Qué es |
|---|---|
| **Disparador** | Botón en la cabecera con el icono universal de accesibilidad y etiqueta textual. Sin solapes, siempre visible, en el orden natural del teclado. |
| **Panel** | `<dialog>` nativo con los once controles agrupados en cuatro categorías. Trampa de foco nativa, `Escape`, devolución del foco al disparador. |
| **Espejo** | Un enlace de texto en el pie («Ajustes de accesibilidad») que abre el mismo panel. Es la segunda vía que exige CC12 y la que sobrevive cuando la cabecera queda fuera de pantalla. |
| **Estado** | `useAccesibilidad` ampliado a siete preferencias, versionado y con migración del formato anterior. |

**Lo que se gana:** once controles en vez de cinco; cuatro modos de contraste en vez de
un interruptor; funcionamiento en móvil, tableta y escritorio sin depender del ancho;
un único sitio donde vive cada control; y una superficie que se puede auditar con axe
sin falsos positivos.

**Lo que se pierde, y se declara:** la clase `barra-accesibilidad-govco` del Kit
desaparece. Es una **desviación declarada** (§15.1), del mismo tipo que las ya
declaradas para el carrusel y la cabecera.

---

## 2. Estado de partida verificado

Todo lo que sigue se comprobó en el árbol de trabajo antes de escribir este plan.

### 2.1 Componentes

| Hecho | Evidencia |
|---|---|
| La barra lateral existe, mide 210 líneas y tiene **5** controles: 4 botones (`contrast`, `decrease-font-size`, `increase-font-size`, `restablecer`) y 1 enlace (Centro de Relevo) | `sitio/app/components/govco/BarraAccesibilidad.vue:60-129` |
| Se oculta **por debajo de 992 px** | `BarraAccesibilidad.vue:62` (`d-none d-lg-flex`) y media query en `public/govco/all.css:813` |
| Está fija a la derecha y centrada verticalmente | `BarraAccesibilidad.vue:138-144` |
| El pie publica **6** controles con etiqueta | `sitio/app/components/govco/PiePaginaGovco.vue` (clases `accesibilidad-pie`, `lista-accesibilidad-pie`) |
| El pie es hoy la única vía de ajuste en móvil y tableta | consecuencia de las dos filas anteriores |

### 2.2 Estado

| Hecho | Evidencia |
|---|---|
| Preferencias actuales: `contraste: boolean`, `letra: number`, `espaciado: boolean` | `sitio/app/composables/useAccesibilidad.ts:40-48` |
| `letra` va de −5 a +5 en pasos de 8 % ⇒ **60 %–140 %** | `useAccesibilidad.ts:35,38,113-116` |
| Se aplica con `zoom` sobre la raíz y dos clases: `contraste-govco`, `espaciado-govco` | `useAccesibilidad.ts:96-106` |
| Persiste en `localStorage` bajo `sede-accesibilidad`, sin versión | `useAccesibilidad.ts:37,80-87` |
| Se lee al montar y se guarda en cada cambio | `useAccesibilidad.ts:133-143` |

### 2.3 Hojas de estilo

| Hecho | Evidencia |
|---|---|
| `.contraste-govco` es un modo real: negro de fondo, amarillo para lo pulsable, 21:1 y ~15:1 | `sitio/app/assets/css/sitio.css:24-81` |
| `.espaciado-govco` aplica los valores literales de WCAG 1.4.12 | `sitio/app/assets/css/sitio.css:355-368` |
| `sitio.css` es la única hoja propia; se registra en `nuxt.config.ts:33-38` | `sitio/nuxt.config.ts` |

### 2.4 Puertas de calidad que **tocan** este componente

Éste es el hallazgo que más condiciona el plan: la barra no está sin probar, está
probada **contra su implementación actual**.

| Puerta | Qué asevera hoy | Línea |
|---|---|---|
| `make diseno` → `CAG-07` | Que existen `.contrast`, `.decrease-font-size` y `.increase-font-size`; que la barra **se ve a 1280** y **se oculta a 800** | `sitio/tests/diseno.mjs:292-321` |
| `npm test` → `accesibilidad-preferencias` | Que las preferencias son exactamente `{contraste:false, letra:0, espaciado:false}`, que `LIMITE === 5` y que el paso 1 escribe `zoom: '1.08'` | `sitio/tests/accesibilidad-preferencias.test.ts:66-158` |
| `make accesibilidad` | axe-core sobre 18 páginas, WCAG 2.0/2.1 A y AA; falla con violación crítica o seria | `sitio/tests/accesibilidad.mjs` |
| `make trazabilidad` | Mapa `CAG-07 → BarraAccesibilidad.vue:24 · PiePaginaGovco.vue:62 · tests/diseno.mjs:292` | `docs/trazabilidad.md:64` |

**Consecuencia de plan:** ninguna fase termina sin actualizar la prueba que le
corresponde. Las pruebas viajan con el código; una puerta roja no se «arregla» bajando
el umbral, se reescribe la aserción para que mida lo que ahora debe medirse.

---

## 3. Brecha frente al brief

El brief fija once componentes mínimos (§5.2). Ésta es la cuenta honesta:

| # | Componente pedido | Hoy | Veredicto |
|---|---|---|---|
| 1 | Botón de activación: icono universal + etiqueta textual | No existe. La barra está siempre abierta | **Falta** |
| 2 | Panel de opciones agrupado por categorías | No existe. Los controles están sueltos en una tira | **Falta** |
| 3 | Tamaño de texto A− / A / A+ (100 %–200 %) | Existe, pero el rango es 60 %–140 % | **Parcial** |
| 4 | Contraste: normal, alto, inverso, escala de grises | Sólo alto (interruptor) | **Parcial (1/4)** |
| 5 | Resaltar enlaces | No existe | **Falta** |
| 6 | Fuente para dislexia | No existe | **Falta** |
| 7 | Espaciado de texto | Existe y cumple WCAG 1.4.12 | **Cumple** |
| 8 | Guía de lectura | No existe | **Falta** |
| 9 | Detener animaciones | No existe | **Falta** |
| 10 | Restablecer | Existe | **Cumple** |
| 11 | Ayuda / atajos de teclado | No existe | **Falta** |

**Resultado: 2 de 11 completos, 2 parciales, 7 ausentes.**

A esto se suman cuatro requisitos transversales del brief que tampoco se cumplen hoy:
respeto explícito de `prefers-contrast` (§5.1), regiones semánticas y `role` en la
barra (§7.1), anuncio de cambios con `aria-live` (§7.1) y una vía de ajuste disponible
en móvil que no dependa de una lista de botones al final de la página (§6.8).

---

## 4. Decisiones de arquitectura

### D1 · El disparador es un círculo flotante centrado en el lado derecho; el panel es un `<dialog>` modal

> **Revisión 2 (2026-10-01).** La primera versión de este plan situaba el
> disparador **en la cabecera**. La indicación posterior del cliente lo cambió:
> el botón tiene que ser **un círculo, estar siempre centrado verticalmente en el
> lado derecho y ser igual en todas las pantallas**. Una cabecera no puede
> garantizar eso —cambia de forma, de orden y de contenido según el ancho—, así
> que manda el requisito nuevo. Lo que no cambia es el panel.

El brief admite dos ubicaciones: «en header o como botón flotante persistente»
(§5.1). Se elige **botón flotante**.

| Opción | Veredicto |
|---|---|
| **A. Barra lateral fija siempre visible** (lo de hoy) | **Descartada.** Once controles a 44 px, con sus 8 px de separación y los separadores de grupo, ocupan unos 600 px de alto: entran a duras penas en un portátil de 768 px y, sobre todo, **tapan contenido durante toda la visita**, que es justo lo que el brief prohíbe (§5.1). No escala. |
| **B. Disparador con etiqueta en la cabecera** | **Descartada** en la revisión 2. No cumple «siempre en el mismo sitio en cualquier pantalla»: la cabecera se reorganiza por anchos y el control se mudaría de lugar. |
| **C. Círculo flotante, fijo, centrado en el lado derecho** | **Elegida.** Mismo sitio en móvil, tableta y escritorio; forma reconocible de un vistazo; no depende de la cabecera ni de su orden; y el panel puede crecer sin coste. |

El `<dialog>` se abre con `showModal()`, que da **trampa de foco, fondo inerte y
`Escape` gratis y correctos** —tres cosas que en una iteración anterior había que
programar a mano y que son justo donde más se falla.

**Consecuencias de la forma circular, asumidas y resueltas:**

| Consecuencia | Resolución |
|---|---|
| Un círculo no admite etiqueta visible al lado sin dejar de ser círculo | El nombre accesible viaja en un texto **recortado**, no en un `aria-label`, para que exista también en modo lectura y para que el control por voz diga lo mismo |
| Flota sobre el contenido y en pantallas estrechas lo tapaba | Se le reserva su franja en `#contenido-principal` por debajo de 576 px (§9.3) |
| Podría chocar con «Volver arriba» | No choca: aquél está en `bottom: 10%` y éste en el centro vertical. Siguen siendo dos módulos independientes |
| Un `filter` lo desanclaría en los modos inverso y grises | Se teletransporta a `body`, fuera de `#__nuxt` (§4, D3) |

El `<dialog>` se abre con `showModal()`, que da **trampa de foco, fondo inerte y
`Escape` gratis y correctos** —tres cosas que en la iteración anterior había que
programar a mano y que son justo donde más se falla.

### D2 · Un solo juego de controles; el pie pasa a ser una segunda vía, no una copia

Hoy el pie repite la función de la barra con otra presentación. Eso incumple CC7
(«acciones iguales deben verse y nombrarse igual») y ya provocó que las dos listas
divergieran: el pie tiene espaciado y la barra no; la barra tiene restablecer y el pie
lo tiene con otro nombre.

Se sustituyen los seis botones del pie por **un enlace** —«Ajustes de accesibilidad»—
que abre el mismo panel. Se cumple igual CC12 (múltiples vías al mismo contenido) y
CC10 sin duplicar lógica ni etiquetas.

### D3 · El `filter` se aplica a un envoltorio del contenido, no a `#__nuxt`

Los modos «colores invertidos» y «escala de grises» necesitan un `filter`, y un
`filter` **convierte a su elemento en bloque contenedor** de los descendientes con
`position: fixed`. La primera versión de este plan filtraba `#__nuxt`, y eso
desanclaba todos los elementos flotantes: se iban con el scroll.

| Opción | Veredicto |
|---|---|
| Filtrar `html` o `body` | **Descartada.** Mismo problema y peor: la caja del documento es más alta que la ventana |
| Filtrar `#__nuxt` y teletransportar lo flotante a `body` | **Descartada tras medirla.** Resolvía el `position: fixed`, pero Vue emite lo teletransportado **antes** del contenedor de la aplicación, así que el botón de accesibilidad quedaba por delante del enlace «Saltar al contenido principal» y **rompía RF-B3-022** —medido: el primer tabulable pasó a ser «Ajustes de accesibilidad»— |
| **Filtrar un envoltorio `.contenido-filtrable`** | **Elegida.** El contenido se invierte; los flotantes —botón circular, volver arriba, banner, aviso de salida y panel— quedan fuera del envoltorio, conservan su posición **y su orden en el documento**, y el enlace de salto vuelve a ser el primer tabulable |

Un detalle decide la forma de la solución: el filtro va en **un solo** envoltorio y
no en cada bloque. Cada `filter` crea un contexto de apilamiento; si cada sección
tuviera el suyo, el contenido —que va después en el documento— se pintaría por
encima de los desplegables del menú.

### D4 · El estado del panel abierto también vive en el composable

Para que el disparador de la cabecera y el enlace del pie abran **el mismo** panel sin
conocerse entre sí, la apertura es estado compartido (`useState`), no un `prop` ni un
evento encadenado por el layout.

### D5 · Sin fuente descargada para el modo dislexia (en esta entrega)

Se implementa con la pila tipográfica que ya viaja al navegador (Verdana/Tahoma) más
espaciado, alineación a la izquierda y anulación de cursivas —que es lo que la
evidencia sobre legibilidad y dislexia sostiene con más fuerza—. Descargar una fuente
específica (p. ej. Atkinson Hyperlegible) es una decisión de licencia y de coste de red
que queda en §16.3 y **no se hace por la puerta de atrás**.

### D6 · Guía de lectura sin movimiento ni JavaScript

Se resuelve por CSS resaltando el bloque que está bajo el puntero o el foco. Un
«subrayador» que sigue al cursor exige `pointermove`, no funciona con teclado y añade
movimiento —tres cosas que chocan con CC20, CC32 y `prefers-reduced-motion`—. La
variante CSS cumple la función sin ninguno de esos costes.

### D7 · Se conserva la paleta del Kit; se adopta del brief sólo el token de foco

El brief propone `#0B5CAB` como color primario. El Kit GOV.CO ya fija Cobalt `#0943B5`
(8,46:1 sobre blanco) y es normativo para una sede `.gov.co`; el propio brief lo
presenta como «valor sugerido». Se conserva Cobalt. Del brief se adopta
`--color-focus: #FFBF00`, porque el proyecto no tenía token de foco y el blanco del Kit
es invisible sobre una superficie blanca como la del panel nuevo.

---

## 5. Modelo de estado y persistencia

### 5.1 Esquema v2

```ts
type ModoContraste = 'normal' | 'alto' | 'inverso' | 'grises'

interface Preferencias {
  contraste: ModoContraste   // antes: boolean
  letra: number              // 0..4  →  100 %, 125 %, 150 %, 175 %, 200 %
  espaciado: boolean
  dislexia: boolean
  resaltarEnlaces: boolean
  guiaLectura: boolean
  detenerAnimaciones: boolean
}

const ESCALA_LETRA = [1, 1.25, 1.5, 1.75, 2] as const
```

`letra: 0` sigue significando **tamaño normal**, que es lo que ya significaba: el valor
por defecto no cambia y el ciudadano que nunca tocó nada no nota diferencia.

### 5.2 Persistencia y migración

| Aspecto | Decisión |
|---|---|
| Clave | Se conserva `sede-accesibilidad` |
| Formato | `{ v: 2, ...preferencias }` |
| v1 detectada (sin `v`) | `contraste: true → 'alto'`, `false → 'normal'`; `letra` se reasigna al paso más cercano por porcentaje: ≤ 0 → `0`, 1–2 → `1`, 3–4 → `2`, ≥ 5 → `4` |
| v1 corrupta o desconocida | Se descarta y se usan los valores por defecto, sin lanzar |
| Escritura | Igual que hoy: en cada cambio, con `try/catch` (almacenamiento privado o lleno) |

La migración es obligatoria y se prueba: sin ella, todo ciudadano que ya hubiera
ajustado el contraste se encontraría con el modo apagado y creería que el sitio olvidó
su decisión.

### 5.3 Regla de aplicación

`aplicar()` sigue siendo el único punto que toca el DOM, y pasa a alternar siete clases
sobre `document.documentElement` más el `zoom`. Se mantiene `zoom` —y no `font-size`—
porque el CSS del Kit está lleno de `px` y escalar la raíz no lo alcanzaría.

---

## 6. Especificación de UI

### 6.1 Tokens

| Token | Valor | Uso | Contraste medido |
|---|---|---|---|
| `--acc-superficie` | `#FFFFFF` | Fondo del panel | — |
| `--acc-texto` | `#1A1A1A` | Texto del panel | 17,4:1 sobre blanco |
| `--acc-primario` | `#0943B5` (Cobalt, Kit) | Acentos, botones | 8,46:1 sobre blanco |
| `--acc-foco` | `#FFBF00` | Anillo de foco exterior | 10,5:1 sobre `#1A1A1A` |
| `--acc-borde` | `#C9C9C9` (Silver, Kit) | Separadores | 1,7:1 — **sólo decorativo**, nunca información |
| `--acc-error` | `#B00020` | Errores | 7:1 sobre blanco |

Regla de foco adoptada del brief (§6.5): **doble anillo** —interior blanco de 2 px y
exterior oscuro de 3 px— de modo que el indicador se vea sobre superficie clara, oscura
o de color sin depender del contexto.

### 6.2 Tipografía

- Se conserva la del sitio: Nunito Sans en títulos, Verdana en prosa. No se introduce
  ninguna familia nueva (§4, D5).
- Tamaño base del panel: `1rem` (16 px), nunca por debajo de 12 pt / 16 px (§6.2).
- Interlínea del panel: 1,5.

### 6.3 Iconografía y tamaño

| Regla | Valor |
|---|---|
| Área activa mínima | 44 × 44 px (`2.75rem`), conforme a RNF-B3-005 y §6.4 |
| Separación entre controles | 8 px (`0.5rem`), conforme a §6.4 |
| Ancho mínimo soportado | 320 px sin scroll horizontal |
| Iconos | SVG en línea; `aria-hidden="true"` cuando hay etiqueta textual; `aria-label` cuando el icono es el único contenido |
| Prohibido | Texto incrustado en imágenes (CC29) |

### 6.4 Estados

Seis estados por control, y **ninguno se distingue sólo por color** (CC5):

| Estado | Tratamiento |
|---|---|
| Normal | Superficie blanca, texto `--acc-texto` |
| Hover | Fondo `#E5ECF8` (Solitude del Kit) |
| Foco | Doble anillo (§6.1) |
| Activo / pulsado | Fondo `#DBEAFE` **y** borde azul **y** marca de verificación visible |
| Seleccionado (radios) | Fondo, borde **y** glifo `✓` |
| Deshabilitado | Opacidad reducida **y** `aria-disabled`/`disabled`, con el motivo escrito al lado cuando el límite se alcanza |

### 6.5 Estructura del panel

```
┌─ Ajustes de accesibilidad ─────────────────── × ─┐
│ Los cambios se guardan solos en este navegador.  │  ← CC15: el aviso va ANTES
│                                                   │
│ ▸ Contraste                                        │  ← radio group (4 modos)
│    ○ Normal   ○ Alto   ○ Inverso   ○ Escala de grises
│                                                   │
│ ▸ Tamaño de texto            100 % ─────────────  │  ← A− / indicador / A+
│    [A−]        A 100 %        [A+]                 │  ← aria-live="polite"
│                                                   │
│ ▸ Lectura                                          │
│    [ ] Más espaciado      [ ] Fuente para dislexia │  ← role="switch"
│    [ ] Resaltar enlaces   [ ] Guía de lectura      │
│                                                   │
│ ▸ Movimiento                                       │
│    [ ] Detener animaciones                         │
│                                                   │
│ ─────────────────────────────────────────────────  │
│ [ Restablecer todo ]   [ Ayuda y atajos ]           │
└───────────────────────────────────────────────────┘
```

**Cuatro categorías con título propio** (CC23), **controles en listas** y no en tablas
(CC9), y el orden DOM igual al orden visual (CC14).

---

## 7. Especificación de interacción y ARIA

| Control | Elemento | Rol / estado | Nombre accesible | Anuncio |
|---|---|---|---|---|
| Disparador | `<button>` | `aria-expanded`, `aria-controls`, `aria-haspopup="dialog"` | «Ajustes de accesibilidad» + icono universal | Cambia `aria-expanded` al abrir y cerrar |
| Panel | `<dialog>` | `role="dialog"` implícito, `aria-modal` implícito, `aria-labelledby` | Título «Ajustes de accesibilidad» | — |
| Contraste | `<fieldset>` + `<legend>` con `role="radiogroup"` implícito y `<input type="radio">` nativos | `checked` | «Contraste: <modo>» | — |
| Tamaño de texto | Dos `<button>` + `<output>` | `disabled` en los extremos | «Reducir tamaño de texto», «Aumentar tamaño de texto» | `aria-live="polite"` con «Tamaño de texto: 125 %» |
| Espaciado, dislexia, resaltar enlaces, guía de lectura, detener animaciones | `<button role="switch">` | `aria-checked` | La etiqueta visible | `aria-live="polite"` con «<Nombre>: activado / desactivado» |
| Restablecer | `<button>` | — | «Restablecer todos los ajustes de accesibilidad» | Anuncio con el resultado |
| Ayuda y atajos | `<details>` + `<summary>` | — | «Ayuda y atajos de teclado» | — |
| Enlace del pie | `<button class="enlace">` | Igual que el disparador salvo `aria-expanded` | «Ajustes de accesibilidad» | — |

**Región de anuncios.** Una sola `<div role="status" aria-live="polite">` dentro del
panel, que recibe el texto de cada cambio. No toma el foco (CC22) y no se anuncia dos
veces.

**Nombre accesible = etiqueta visible** en todos los controles, para que el control por
voz funcione (RF-B3-030 / CC7).

---

## 8. Teclado, foco y lector de pantalla

### 8.1 Recorrido

| Tecla | Acción |
|---|---|
| `Tab` | Recorre disparador → grupos en orden → restablecer → ayuda → cerrar |
| `Shift+Tab` | Retrocede por el mismo orden |
| `Enter` / `Espacio` | Activa el control enfocado. Nunca cambia nada por el mero hecho de enfocar (CC22) |
| `↑` `↓` `←` `→` | Dentro del grupo de radios, cambia la opción (comportamiento nativo) |
| `Escape` | Cierra el panel y devuelve el foco al disparador |
| `Tab` en el último control | Permanece dentro del panel (trampa nativa de `<dialog>`) |

### 8.2 Reglas de foco

1. Al abrir, el foco entra en el panel —en el título con `tabindex="-1"`— para que el
   lector anuncie el contexto antes que el primer control.
2. Al cerrar, el foco vuelve **al disparador que lo abrió**, sea el de la cabecera o el
   del pie. Se guarda la referencia del invocador, no se asume.
3. El foco nunca queda atrapado: `Escape` siempre sale.

### 8.3 Lector de pantalla

- `lang="es-CO"` ya está declarado en `nuxt.config.ts:43`; el panel no introduce texto
  en otro idioma (CC27).
- Todo icono es decorativo (`aria-hidden`) o tiene nombre propio; ninguno queda mudo.
- Los cambios se anuncian una vez, en la región `role="status"`.

---

## 9. CSS: clases de modo y sus trampas

### 9.1 Clases sobre `document.documentElement`

| Clase | Efecto |
|---|---|
| `.contraste-govco` | **Ya existe** (`sitio.css:24-81`). Se reutiliza tal cual para el modo `alto`. |
| `.contraste-inverso-govco` | **Nueva.** `filter: invert(1) hue-rotate(180deg)` sobre la raíz; imágenes y vídeos re-invertidos para que no queden en negativo |
| `.contraste-grises-govco` | **Nueva.** `filter: grayscale(1)` |
| `.espaciado-govco` | **Ya existe** (`sitio.css:355-368`). Sin cambios |
| `.dislexia-govco` | **Nueva.** Verdana/Tahoma, `text-align: left`, `font-style: normal`, interlínea 1,6 |
| `.resaltar-enlaces-govco` | **Nueva.** Subrayado reforzado + fondo de enlace; no depende del color |
| `.guia-lectura-govco` | **Nueva.** Resalta el párrafo bajo puntero o foco |
| `.detener-animaciones-govco` | **Nueva.** Anula `animation` y `transition`, y fija `scroll-behavior: auto` |

### 9.2 La trampa del `filter` — riesgo principal del plan

Un `filter` distinto de `none` sobre un ancestro **crea un bloque contenedor**: todo
`position: fixed` que esté dentro pasa a comportarse como `absolute`. Aplicar el filtro
del modo inverso sobre la raíz puede, por tanto, **descolocar todos los elementos
flotantes del sitio**: el propio panel, `VolverArriba`, el aviso de salida y el banner
de cookies.

Mitigación en dos niveles:

1. **Preferida.** Aplicar el `filter` sobre la raíz y un **contrafiltro** en la
   superficie del panel, que además se teleporta a `<body>` (§4, D3) y por tanto queda
   fuera del subárbol filtrado. Se comprueba en navegador real que los cuatro flotantes
   siguen en su sitio en los cuatro modos.
2. **Reserva, si (1) falla.** Teleportar a `<body>` todo lo flotante y filtrar sólo
   `#__nuxt`.

Esta comprobación es **puerta de la fase 3**: no se declara terminada sin la captura en
los cuatro modos.

---

## 10. Preferencias del sistema operativo

| Consulta | Tratamiento | Motivo |
|---|---|---|
| `prefers-reduced-motion: reduce` | **Respetada siempre**, con o sin elección del ciudadano: el panel no anima y las transiciones se anulan | Es una necesidad declarada por el sistema; no hay nada que consentir |
| `prefers-contrast: more` | **Se sugiere, no se impone.** Si el ciudadano no tiene preferencia guardada, el panel muestra un aviso no bloqueante que propone el modo alto contraste | El brief prohíbe forzar cambios sin consentimiento (§7.4) |
| `prefers-color-scheme: dark` | **No se finge.** El sitio no tiene tema oscuro; se declara `color-scheme: light` de forma explícita y se documenta como fuera de alcance | Mostrar un tema oscuro a medias sería peor que no tenerlo, y el brief pide respetar, no inventar |

---

## 11. Archivos afectados

| Archivo | Tipo | Qué cambia |
|---|---|---|
| `sitio/app/composables/useAccesibilidad.ts` | **Modificado** | Esquema v2, migración desde el formato anterior, siete clases, apertura del panel |
| `sitio/app/components/govco/BotonAccesibilidad.vue` | **Nuevo** | Círculo flotante (revisión 2), con sus escalones de tamaño por ancho |
| `sitio/app/components/govco/PanelAccesibilidad.vue` | **Nuevo** | `<dialog>` nativo con los once controles, teletransportado a `body` |
| `sitio/app/components/govco/BarraAccesibilidad.vue` | **Retirado** | Lo sustituyen los dos anteriores |
| `sitio/app/assets/css/sitio.css` | **Modificado** | Seis clases de modo, alto contraste del panel, reflujo, franja del círculo |
| `sitio/app/layouts/default.vue` | **Modificado** | Envoltorio `.contenido-filtrable`; montaje del círculo y del panel fuera de él; retiro de `<BarraAccesibilidad />` |
| `sitio/app/components/govco/PiePaginaGovco.vue` | **Modificado** | Seis botones → un enlace al panel |
| `sitio/app/components/govco/VolverArriba.vue` | **Modificado** | Comentario: vive fuera del envoltorio filtrable |
| `sitio/app/components/BannerCookies.vue` | **Modificado** | Comentario: vive fuera del envoltorio filtrable |
| `sitio/tests/accesibilidad-preferencias.test.ts` | **Modificado** | Esquema v2 + siete casos de migración |
| `sitio/tests/diseno.mjs` | **Modificado** | CAG-07 reescrito: círculo fijo centrado a cualquier ancho + panel |
| `sitio/tests/verificar-modos-contraste.mjs` | **Nuevo** | Verificación de la trampa del `filter` y del reflujo (`npm run test:contraste`) |
| `docs/trazabilidad.md` | **Regenerado** | Ancla de CAG-07 |
| `DESIGN.md` · `PRODUCT.md` | **Modificado** | Especificación y estado del componente |

**Sin cambios, y conviene decirlo:** `config/sitemap.ts` y `server/routes/sitemap.xml.ts`
no se tocan. El brief pide «documentar en mapa del sitio XML» (§10) y ya está cumplido:
`/accesibilidad` figura en el mapa desde la fuente única de rutas. Añadir el panel no
crea ninguna ruta nueva.

---

## 12. Fases y puertas

Cada fase termina en una puerta verificable. Ninguna fase se da por buena con la puerta
roja.

| Fase | Entregable | Puerta |
|---|---|---|
| **F0 · Estado** | Esquema v2, migración, siete clases aplicadas desde `aplicar()` | `npm test` verde con las pruebas reescritas del composable |
| **F1 · Panel** | `PanelAccesibilidad.vue` y `BotonAccesibilidad.vue` con ARIA, foco y teclado completos | `npm run typecheck` limpio + recorrido de teclado a mano documentado |
| **F2 · Integración** | Disparador en cabecera, enlace en el pie, retiro de la barra antigua | `make diseno` verde con CAG-07 reescrito |
| **F3 · Modos de contraste** | Inverso y grises, con la trampa del `filter` resuelta y fotografiada | Capturas en los 4 modos × 320 / 800 / 1280 px, con los flotantes en su sitio |
| **F4 · Sistema y ayuda** | `prefers-*`, ayuda y atajos, anuncios `aria-live` | Comprobación manual con la preferencia del sistema activada |
| **F5 · Cierre** | axe, contraste medido, documentación y trazabilidad | `make accesibilidad` sin violaciones críticas ni serias · `docs/trazabilidad.md` al día |

**Orden obligatorio:** F1 depende de F0 (necesita el estado), F2 de F1 (necesita el
panel), F3 de F2 (necesita la integración para medir los flotantes), F5 de todas.

---

## 13. Pruebas y criterios de aceptación

### 13.1 Automáticas

| Instrumento | Criterio |
|---|---|
| `npm test` (vitest) | Esquema v2, migración desde v1, límites de la escala, `restablecer`, persistencia |
| `make diseno` (Playwright) | CAG-07 reescrito: el disparador existe y es visible a 320, 800 y 1280 px; tiene nombre accesible; al activarlo el panel aparece; `aria-expanded` cambia; `Escape` lo cierra y el foco vuelve |
| `make accesibilidad` (axe-core) | Cero violaciones **críticas o serias** en las 18 páginas, con el panel abierto y cerrado |
| `npm run typecheck` | Sin errores |

### 13.2 Manuales, con evidencia

1. **Teclado:** recorrido completo sin ratón; `Tab`, `Shift+Tab`, `Enter`, `Espacio`,
   flechas y `Escape`; foco visible en cada parada.
2. **Zoom 200 %** de texto y **200 %** de navegador a **320 px** de ancho: sin scroll
   horizontal y sin pérdida de contenido (CC4, RNF-B3-004).
3. **Contraste medido**, no estimado: cada par nuevo (texto, icono, borde informativo,
   anillo de foco) con su ratio calculado; umbral 4,5:1 en texto normal y 3:1 en
   componentes.
4. **Lector de pantalla:** NVDA o VoiceOver; se anota lo que anuncia cada control, el
   anuncio de cambio y el retorno del foco.
5. **Los cuatro modos de contraste** con el panel abierto, para verificar de paso que
   ningún flotante se desplazó (§9.2).

---

## 14. Matriz CC1–CC32

| CC | Criterio | Dónde se cumple |
|---|---|---|
| 1 | Alternativa texto en lo no textual | Todo icono lleva `aria-label` o `aria-hidden`; ninguna etiqueta es una imagen |
| 2 | Subtítulos en multimedia de ayuda | No hay vídeo de ayuda en esta entrega; la ayuda es texto |
| 3 | Guion de solo audio/vídeo | No aplica: no hay audio ni vídeo en el panel |
| 4 | Ampliable | Escala hasta 200 %; comprobado a 320 px |
| 5 | Contraste suficiente | §6.1 y §13.2; ningún estado depende sólo del color |
| 6 | Texto junto al icono | Icono + etiqueta visible en el disparador y en los controles que la admiten |
| 7 | Identificación coherente | Un solo juego de controles (§4, D2); misma acción, mismo nombre |
| 8 | Regiones semánticas | `<dialog>` con encabezado, `<fieldset>`/`<legend>`, listas |
| 9 | Listas para agrupar | Listas y `fieldset`; ninguna tabla para maquetar |
| 10 | Saltar bloques repetidos | El enlace de salto ya existe y salta por delante del disparador (`default.vue:186`) |
| 11 | Marcado válido | HTML5; `<dialog>` nativo; sin anidamientos indebidos |
| 12 | Múltiples vías | Disparador de cabecera + enlace del pie + la página `/accesibilidad` |
| 13 | Navegación coherente | El orden de grupos es fijo y no depende del contexto |
| 14 | DOM = orden visual | Los grupos se declaran en el mismo orden en que se ven |
| 15 | Advertencias antes del control | «Los cambios se guardan solos» va en la cabecera del panel |
| 16 | Tabulación lógica | §8.1 |
| 17 | Foco visible 3:1 | Doble anillo (§6.1) |
| 18 | Sin audio automático | El panel no emite sonido |
| 19 | Control de tiempos | No hay mensajes temporizados |
| 20 | Control de movimiento | Modo «detener animaciones» + `prefers-reduced-motion` |
| 21 | Sin actualización automática | El panel nunca recarga ni cambia estado solo |
| 22 | Sin cambios al enfocar | Ningún control actúa al recibir foco |
| 23 | Títulos claros | Cuatro categorías con título |
| 24 | Etiquetas explícitas | Todo control tiene etiqueta visible asociada |
| 25 | Instrucciones claras | Ayuda y atajos dentro del panel |
| 26 | Enlaces descriptivos | «Ajustes de accesibilidad»; ningún «aquí» |
| 27 | Idioma | `lang="es-CO"` ya declarado (`nuxt.config.ts:43`) |
| 28 | Errores en texto claro | Sólo se producen fallos de almacenamiento; se degradan en silencio y se documentan |
| 29 | Sin imágenes de texto | Sólo SVG y texto real |
| 30 | Widgets accesibles | No se integra ningún widget externo |
| 31 | UTF-8 | Declarado en `nuxt.config.ts:45` |
| 32 | Operable por teclado | §8.1 |

---

## 15. Desviaciones declaradas y riesgos

### 15.1 Desviaciones del Kit GOV.CO

| Desviación | Motivo |
|---|---|
| Se retira la barra lateral `barra-accesibilidad-govco` y su visibilidad `d-none d-lg-flex` | El Kit la mide para **tres** botones y la oculta en tableta; el brief pide once y cobertura en móvil. La Guía Maestra la declara obligatoria «con Aumentar, Reducir y Contraste»: el brief del cliente la amplía expresamente. |
| El disparador vive en la cabecera y no flotando | §5.1 del brief admite ambas; la flotante colisiona con `VolverArriba` |

Ambas siguen la convención ya usada en el proyecto para el carrusel y la cabecera: la
desviación se declara en el propio componente y en la documentación, no se disimula.

### 15.2 Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| El `filter` descoloca los flotantes | Media | Alto | §9.2, con plan de reserva y puerta en F3 |
| Las pruebas existentes quedan obsoletas y se «arreglan» bajando el umbral | Media | Alto | Las aserciones se reescriben para medir el comportamiento nuevo; se revisa en la puerta de cada fase |
| El panel no abre sin JavaScript | Baja | Medio | Se documenta; la página `/accesibilidad` explica los ajustes a nivel de sistema operativo y navegador |
| Pérdida de la preferencia al migrar | Media | Medio | Casos de migración en vitest, incluida la v1 corrupta |
| Once controles vuelven a divergir entre el panel y el pie | Baja | Medio | Un solo juego de controles (§4, D2); el pie sólo abre |

### 15.3 Hallazgos medidos al verificar

Todo lo que sigue se midió con Chromium sobre el sitio en marcha, no se dedujo.

| Hallazgo | Medición | Qué se hizo |
|---|---|---|
| **El globo de ayuda de la galería empujaba el ancho del documento.** Se oculta con `visibility: hidden`, que **sigue ocupando sitio**, y el Kit le fija 16,75 rem. | Su caja medía **536 px** sobre una ventana de 320 | Se saca del flujo mientras está oculto (`display: none`) y se devuelve al flujo en los mismos estados en que el Kit lo muestra. Acotarlo con `max-width` **no** servía: las unidades `vw` no se reescalan con `zoom` |
| **Las tarjetas de información no se encogían** | 344 px sobre una ventana de 320 | `min-width: 0` y `max-width: 100%` |
| **El círculo tapaba texto en pantallas estrechas** | Ocupaba 48 px de los 304 útiles a 320 px | Se le reserva la franja en `#contenido-principal` por debajo de 576 px |
| **`scrollWidth` bajo `zoom` no es de fiar.** Con `zoom: 2` a 320 px, Chromium informa `scrollWidth: 620` y `clientWidth: 320`, pero `window.scrollTo(99999, 0)` deja `scrollX` en **0** | Desplazamiento horizontal real: **0** en los cuatro modos y en los tres anchos | La verificación dejó de usar `scrollWidth` y mide desplazamiento horizontal real |

**Limitación conocida, y hay que decirla.** El ajuste de tamaño de texto escala la
raíz con `zoom`, y **`zoom` no dispara las media queries**. Por eso, en las
combinaciones extremas —texto al 200 % sobre una ventana de 320 px, o al 200 %
sobre 1280— el menú de navegación se recorta por la derecha en vez de replegarse,
porque el Kit sigue creyendo que está en escritorio.

Lo verificado y lo que no:

| Caso | Resultado |
|---|---|
| 320 px con texto al 100 % (equivalente a 400 % de aumento de navegador, WCAG 1.4.10) | **Sin recorte y sin desplazamiento horizontal** |
| 1280 px con texto al 200 % —cabecera, buscador, contenido— | **Sin recorte** |
| 1280 px con texto al 200 % —menú de navegación— | Se recorta por la derecha |
| 320 px con texto al 200 % (160 px de composición) | Se recorta |

La conformidad con WCAG 1.4.4 se sostiene por el **aumento del navegador**, que sí
dispara las media queries y repliega el menú; el control de la barra es un atajo
de la sede, no el mecanismo con el que se acredita el criterio. Se deja anotado
para que la Entidad decida si prefiere bajar el máximo del atajo o convivir con la
limitación.

---

## 16. Decisiones por ratificar

**No se implementan sin ratificación expresa.**

1. **Retirar los seis controles del pie.** Afecta a quien navegue sin JavaScript: el
   pie deja de ajustar y sólo enlaza. *Recomendación: ratificar.* La duplicación ya
   causó divergencia y es la única forma de cumplir CC7.
2. **Cambiar la escala de letra de 60 %–140 % a 100 %–200 %.** El brief lo pide
   literal; cambia un comportamiento que hoy existe. *Recomendación: ratificar*, con
   migración de los valores guardados.
3. **Fuente específica para dislexia.** Descargar una woff2 propia exige decidir
   licencia y asumir coste de red. *Recomendación: no hacerlo en esta entrega*;
   mantener Verdana + espaciado y revisarlo con evidencia de uso.
4. **Retirar la barra del Kit.** Es la desviación de mayor alcance. *Recomendación:
   ratificar* a la vista del brief, y dejarla anotada en `docs/trazabilidad.md`.

---

## 17. Trazabilidad

| Requisito | Fuente | Dónde se cumple |
|---|---|---|
| RF-B1-044 | `sede-electronica-doc/07-accesibilidad/accesibilidad.md:20` | Panel completo; contraste, tamaño, Centro de Relevo y persistencia |
| RF-B3-022 | `accesibilidad.md:21` | Sin cambios: el enlace de salto sigue siendo el primer tabulable |
| RF-B3-014 | `accesibilidad.md:42` | Modo espaciado (ya existente), conservado |
| RNF-B3-005 | `accesibilidad.md:101` | 44 × 44 px en todo control |
| RNF-07-D01 | `accesibilidad.md:107` | El disparador existe y opera de 320 px en adelante |
| RNF-B1-014 / CAG-32 | `accesibilidad.md:94` | `make accesibilidad`, axe sin violaciones críticas ni serias |
| RNF-B1-018 | `accesibilidad.md:98` | Modo detener animaciones + `prefers-reduced-motion` |
| CC1–CC32 | Anexo 1, Res. 1519/2020 | §14 |
| CAG-07 | `docs/trazabilidad.md:64` | Aserción reescrita en `sitio/tests/diseno.mjs` |

---

*Elaborado con rigor de ingeniería y de diseño: la barra de accesibilidad no es un
adorno estético, es un instrumento normativo. Cada control que se anuncie aquí tiene que
poder operarse con el teclado, entenderse con un lector de pantalla y verse con
suficiente contraste; lo que no cumpla las tres cosas no entra.*
