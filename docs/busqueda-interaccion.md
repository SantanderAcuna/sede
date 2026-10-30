# T-22 · Especificación de interacción del buscador (AC-22.12)

**Estado: BORRADOR para aprobación del titular. No hay código de la entrega 3.**
Elaborada el 2026-10-30. Cubre el combobox de sugerencias del encabezado y la página `/buscar`.
La entrega 3 no se codifica hasta que respondas a las decisiones del §1.

Documentos hermanos: [`busqueda.md`](busqueda.md) (diseño, mediciones, límites) ·
[`casos-de-prueba-t22-busqueda.md`](../sede-electronica-doc/01-estructura-identidad/casos-de-prueba-t22-busqueda.md)
(TC-BUS-25 a 32 se ejecutan contra esta especificación).

Convención: **HECHO** = comprobado en esta sesión o en el repositorio · **INFERENCIA** = se deduce de
datos publicados o del código, sin comprobarlo en el sistema · **DECISIÓN** = elección de diseño que
tú puedes cambiar.

---

## 1. Decisiones que necesito de ti

Respóndelas con «sí», «no» o «cambia a …». Mi recomendación va primero. La D-I1 y la D-I4 alteran
algo que ya estaba escrito en la auditoría; las demás son nuevas.

| # | Decisión | Recomendación | Por qué | Si dices que no |
|---|---|---|---|---|
| **D-I1** | **Pausa del cliente: 350 ms** (la auditoría y AC-22.2 dicen 250 ms) | **Sí** | Con 250 ms, una persona en móvil dispara 9–11 peticiones por consulta; con 350 ms, 5–9 (`busqueda.md` §2.4, INFERENCIA con datos publicados). Con 350 ms, el límite de 120 por minuto protege a ≈ 10 personas detrás de una IP; con 250 ms, a ≈ 5. | Se mantiene 250 ms y hay que subir `BUSQUEDA_LIMITE_POR_MINUTO` a 240 desde el día 1. AC-22.2 se prueba con 250. |
| **D-I2** | **Máximo 6 sugerencias visibles** (la API devuelve hasta 10; el cliente pide `limite=6`) | Sí | 6 filas de 48 px son 288 px: caben en un móvil con el teclado abierto, que deja ≈ 300–340 px (estimación mía, no medida en dispositivos). Más filas obligan a desplazarse dentro de la lista. | Se pide `limite=10` y la lista se desplaza por dentro. |
| **D-I3** | **Intro con una sugerencia activa va al contenido** (no rellena el campo); **sin sugerencia activa, busca** en `/buscar?q=` | Sí | En un buscador del sitio, elegir «Licencia de construcción» y quedarse en el campo obliga a un paso más. Se aparta del ejemplo de la APG (que rellena el campo) y lo digo. | Intro rellena el campo y hace falta una segunda pulsación. |
| **D-I4** | **De dónde salen los resultados de `/buscar`:** decidir A, B, C o D de `busqueda.md` §2.5 **antes** de codificar la página | **A** (canal interno de confianza) si aceptas tocar nginx y compose; si no, **D** (navegador) como mal menor | Medido en staging: las llamadas del servidor del sitio comparten un solo cupo; una ráfaga de 50 cargas de `/tramites` vació el catálogo para quien lo pidió ≈ 80 s. Este comportamiento afecta a todo el sitio, no solo a la búsqueda. | Sin decisión, la página `/buscar` hereda el defecto. |
| **D-I5** | La **categoría** de cada sugerencia («Trámite», «Política») se muestra como **texto**, no solo con icono o color | Sí | WCAG 1.4.1 (uso del color) y lectores de pantalla. | — |
| **D-I6** | **No hay búsquedas recientes ni historial propio** | Sí | Guardar lo que escribe el ciudadano contradice AC-22.7 y la Ley 1581. La URL `?q=` en el historial del navegador es del ciudadano, no nuestra. | Habría que llevarlo al Oficial de Protección de Datos. |
| **D-I7** | Si la API responde 429, **las sugerencias se pausan** durante `Retry-After` y se avisa **una vez** | Sí | Evita que el cliente agrave el bloqueo y evita anunciar un contador cada segundo. | — |
| **D-I8** | **El límite se queda en 120 por minuto** con el criterio de revisión del §6 | Sí | Subirlo a ciegas debilita la defensa sin dato; el criterio dice cuándo subirlo. | — |
| **D-I9** | El campo de `/buscar` usa un **borde de 4,54:1** (`#767676`), no el `#ced4da` de `DESIGN.md` §3.3 | Sí | `#ced4da` sobre blanco da **1,49:1**; WCAG 1.4.11 pide 3:1 para el borde de un campo. HECHO (calculado). `DESIGN.md` §3.3 lo define así para los formularios en general; no lo toco aquí. | El campo de `/buscar` no cumple 1.4.11 salvo que otro indicador lo identifique. |

---

## 2. Fundamentos

- **Patrón de combobox.** [Guía de prácticas de la APG del W3C, ejemplo «combobox con autocompletado
  en lista»](https://www.w3.org/WAI/ARIA/apg/example-index/combobox/combobox-autocomplete-list.html),
  leída en esta sesión (HECHO). Roles y estados: `role="combobox"` en el campo, `aria-autocomplete="list"`,
  `aria-controls`, `aria-expanded`, `aria-activedescendant`; lista con `role="listbox"`; filas con
  `role="option"` y `aria-selected`. Teclado: ↓ y ↑ entran en la lista, Alt+↓ la abre sin mover el
  foco, Esc la cierra, Intro actúa sobre la opción activa.
- **WCAG 2.2** (citados de la Recomendación; **no los releí en esta sesión**, salvo 2.5.8 cuyo mínimo
  de 24 px confirmó la búsqueda): 1.4.1 uso del color · 1.4.4 cambio de tamaño del texto · 1.4.10
  reflujo (320 px) · 1.4.11 contraste de no texto · 2.1.1 teclado · 2.4.7 foco visible · 2.5.8 tamaño
  de objetivo (24 px, AA) · 3.3.1 identificación del error · 4.1.2 nombre, función, valor · 4.1.3
  mensajes de estado. El proyecto exige además 44 px (AC-22.12).
- **Cómo escribe la gente** (determina la pausa): Dhakal et al., *Observations on Typing from 136
  Million Keystrokes*, CHI 2018 — [PDF](https://www.pokristensson.com/pubs/DhakalEtAlCHI2018.pdf):
  intervalo medio entre teclas 238,66 ms (DE 111,6), > 480 ms en el 10 % más lento. Palin et al.,
  *How do people type on mobile devices?*, MobileHCI 2019 —
  [PDF](https://www.pokristensson.com/pubs/PalinEtAlMobileHCI2019.pdf): 36,17 palabras por minuto de
  media, 26,3 entre los 50 y los 59 años. Ambos leídos en esta sesión (HECHO).
- **Paleta y estados de formulario:** `DESIGN.md` §1.2 y §3.3. Contrastes calculados en esta sesión
  (HECHO): texto `#1A1A1A` sobre blanco 17,40:1 · `#4C4C4C` sobre blanco 8,59:1 · cobalto `#0943B5`
  sobre blanco 8,46:1 · error `#DC2626` sobre blanco 4,83:1 · texto `#1A1A1A` sobre la opción
  resaltada `#E6EDFA` 14,80:1 · cobalto sobre `#E6EDFA` 7,20:1.

**Lo que no pude comparar:** el buscador predictivo del Kit GOV.CO (la fuente de `BuscadorGovco.vue`
cita `examples/general/buscador.html`, que no está en el repositorio). Esta especificación sigue la
APG; si el Kit exige otro marcado, hay que decidirlo.

---

## 3. Alcance

**Entra:** (1) sugerencias mientras se escribe en el buscador del encabezado, que ya existe
(`BuscadorGovco.vue`) y hoy solo recoge el término; (2) la página `/buscar` con resultados reales,
filtro por tipo y paginación.

**No entra:** búsqueda por voz; resaltado de la coincidencia dentro del título (con tolerancia a
erratas no hay una coincidencia literal que resaltar); filtros por fecha; contadores por tipo (exigirían
tres consultas); noticias y documentos (T-23, T-24); búsquedas recientes (D-I6).

---

## 4. Sugerencias en el encabezado

### 4.1 Anatomía

Escritorio (1280 px), lista abierta con la tercera opción activa:

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ [logo]   Sede Electrónica                           ┌────────────────────────┬──┐ │
│                                                     │ licencia de c│         │🔍│ │
│                                                     └────────────────────────┴──┘ │
│                                                     ┌─────────────────────────────┐
│                                                     │ Licencia de construcción    │
│                                                     │ Trámite                     │
│                                                     ├─────────────────────────────┤
│                                                     │ Licencia de conducción      │
│                                                     │ Trámite                     │
│                                                     ├─────────────────────────────┤
│                                                     │▌Licencia de urbanismo     ▐│  ← activa
│                                                     │ Trámite                     │
│                                                     └─────────────────────────────┘
└──────────────────────────────────────────────────────────────────────────────────┘
```

Móvil (360 px), lista abierta; el círculo de accesibilidad queda **debajo** de la lista:

```
┌────────────────────────────────┐
│ ☰  Sede Electrónica            │
│ ┌──────────────────────┬─┬───┐ │
│ │ licencia de c│       │✕│🔍 │ │
│ └──────────────────────┴─┴───┘ │
│ ┌────────────────────────────┐ │
│ │ Licencia de construcción   │ │  filas de ≥ 48 px
│ │ Trámite                    │ │
│ ├────────────────────────────┤ │
│ │ Licencia de conducción     │ │
│ │ Trámite                    │ │
│ ├────────────────────────────┤ │          ◯ círculo flotante
│ │ …                          │ │          (z-index 1210) queda
│ └────────────────────────────┘ │          debajo de la lista (1220)
└────────────────────────────────┘
```

### 4.2 Marcado accesible (DECISIÓN, basada en la APG)

```html
<form role="search" aria-label="Buscar en el sitio" action="/buscar" method="get">
  <label for="b-campo">Buscar en el sitio</label>
  <input id="b-campo" name="q" type="text" role="combobox"
         aria-autocomplete="list" aria-expanded="false"
         aria-controls="b-lista" aria-activedescendant=""
         aria-describedby="b-ayuda" autocomplete="off"
         enterkeyhint="search" maxlength="120">
  <span id="b-ayuda" class="solo-lector">Escriba al menos 3 letras para ver sugerencias.</span>
  <button type="button" aria-label="Limpiar la búsqueda">…</button>
  <button type="submit" aria-label="Buscar">…</button>

  <ul id="b-lista" role="listbox" aria-label="Sugerencias de búsqueda" hidden>
    <li id="b-op-1" role="option" aria-selected="false">…título… <span>Trámite</span></li>
  </ul>
  <div role="status" aria-live="polite" class="solo-lector" id="b-estado"></div>
</form>
```

- El **formulario funciona sin JavaScript**: `action="/buscar" method="get"` lleva a la página de
  resultados. Las sugerencias son una mejora, no un requisito.
- El foco **siempre** queda en el campo; la opción activa se indica con `aria-activedescendant` y se
  resalta con color y con un borde izquierdo de 4 px (no solo color).
- `type="text"` y no `type="search"`, para no duplicar el botón de borrar que algunos navegadores
  añaden a `type="search"`. `maxlength="120"` coincide con el máximo de la API.
- El nombre accesible de una opción sale de su contenido: «Licencia de construcción, Trámite».

### 4.3 Teclado

| Tecla | Lista cerrada | Lista abierta |
|---|---|---|
| Escribir | Tras 350 ms de pausa y ≥ 3 caracteres, pide sugerencias | Igual; la opción activa se borra |
| ↓ | Con ≥ 3 caracteres: abre la lista (pide ya, sin esperar la pausa) | Pasa a la siguiente opción; desde la última vuelve al campo (sin activa) |
| ↑ | — | Pasa a la anterior; desde la primera vuelve al campo |
| Alt+↓ | Abre la lista sin activar nada | — |
| Intro | Envía el formulario → `/buscar?q=` | Con opción activa: **va a su URL** (D-I3). Sin activa: envía el formulario |
| Esc | Con texto: lo borra (comportamiento actual). Sin texto: nada | **Cierra la lista** y deja el texto; un segundo Esc borra |
| Tab | Sigue el orden normal: campo → limpiar (si hay texto) → buscar | Cierra la lista y sigue el orden normal |
| ←, →, Inicio, Fin | Mueven el cursor | Mueven el cursor y borran la opción activa |

El recorrido ↓/↑ que pasa por el campo entre la última y la primera opción es una **DECISIÓN** mía; la
prueba de teclado (TC-BUS-27) la fija y el ejemplo de la APG se usa como referencia cruzada.

### 4.4 Estados y textos

Todos los textos son en español y están en el §7. Cada estado tiene una representación visual y un
anuncio a lectores de pantalla.

| Estado | Qué se ve | Qué se anuncia | Marcado |
|---|---|---|---|
| **Vacío / menos de 3 caracteres** | Sin lista | Al enfocar, la ayuda: «Escriba al menos 3 letras para ver sugerencias.» No se anuncia nada al teclear | `aria-describedby` estático |
| **Cargando** | La lista previa se conserva; tras **200 ms** sin respuesta, un indicador discreto junto al icono de buscar. Nunca un vacío intermedio | «Buscando sugerencias.» (una vez por consulta, si pasan 200 ms) | `aria-busy="true"` en la lista; `role="status"` |
| **Con resultados** | Hasta 6 filas (D-I2) | «N sugerencias disponibles.» («1 sugerencia disponible.») | `aria-expanded="true"` |
| **Sin resultados** | Una fila **no seleccionable** dentro de la ventana: «No hay sugerencias. Pulse Intro para buscar en toda la sede.» | Ese mismo texto | La fila **no** es `role="option"`; `aria-expanded="true"` |
| **Error de red / sin conexión** | Misma fila no seleccionable: «No pudimos consultar las sugerencias. Pulse Intro para buscar.» | Igual | `role="status"`; no se reintenta solo (§4.5) |
| **429** | «Hizo muchas búsquedas seguidas. Espere N segundos para ver sugerencias; aun así puede pulsar Intro.» N viene de `Retry-After` (máx. 60) | Se anuncia **una vez**, sin cuenta atrás | Sugerencias pausadas hasta que pase `Retry-After` |
| **422** | No ocurre: el cliente no envía menos de 3 ni más de 120 caracteres. Si la API lo devolviera, se trata como «sin resultados» | — | — |

### 4.5 Reglas de las peticiones (lo que protege al servidor y al ciudadano)

1. **Mínimo 3 caracteres** tras `trim()` y colapsar espacios; **máximo 120**.
2. **Pausa de 350 ms** (D-I1) desde la última tecla. Una petición por pausa.
3. **Una sola respuesta vale:** cada petición lleva un contador y un `AbortController`; al lanzar una
   nueva se cancela la anterior y **se ignora cualquier respuesta cuyo contador no sea el último**.
   AC-22.12 lo exige: la respuesta de una consulta anterior nunca reemplaza a la última.
4. **Sin peticiones durante una composición** (`compositionstart` … `compositionend`): las tildes con
   tecla muerta producen composición en varios sistemas y no son texto terminado. (INFERENCIA: no probé
   el comportamiento en cada navegador; TC-BUS-25 lo comprueba con eventos simulados.)
5. **Memoria breve, solo en variables:** respuestas guardadas por consulta normalizada, 60 s y 30
   entradas como máximo, para no repetir la petición al borrar y reescribir. **Nada en
   `localStorage`, `sessionStorage`, cookies ni IndexedDB** (D-I6).
6. **Sin reintento automático** tras un error: la siguiente pulsación vuelve a intentar, pero no antes de
   2 s desde el fallo. Tras un 429, no antes de que pase `Retry-After`.
7. **Un clic no cierra la lista antes de llegar:** el cierre por pérdida de foco espera al `click` de la
   opción (en táctil, `pointerdown` no cierra).
8. **Nada de lo escrito sale de la petición:** no va a la analítica, a los eventos ni a la consola (§6).

### 4.6 Geometría y visual

- Altura de cada fila **≥ 48 px**; botones de limpiar y buscar **≥ 44 × 44 px** (criterio del
  proyecto; WCAG 2.5.8 pide 24 px).
- **Foco visible:** anillo de 3 px cobalto `#0943B5` con 2 px de separación (8,46:1 sobre blanco).
- **Opción activa:** fondo `#E6EDFA` (texto 14,80:1), borde izquierdo de 4 px cobalto.
- **Categoría:** `#4C4C4C` sobre el fondo (8,59:1), texto de 0,875 rem, siempre escrita.
- **Capas:** lista a `z-index: 1220`, por encima del círculo flotante (1210), para que éste no la tape
  (AC-22.12). Al cerrar la lista el círculo vuelve a ser accesible.
- **Altura máxima:** la lista nunca supera el área visible (se calcula con `visualViewport` por el
  teclado del móvil) y se desplaza por dentro, con `overscroll-behavior: contain`.
- **Reflujo a 320 px y tamaño de letra:** anchos en `%` y `rem`; sin desplazamiento horizontal. El
  control de tamaño de letra del sitio («A+») y el zoom al 200 % no cortan texto.
- **Movimiento:** sin animaciones. (Respeta `prefers-reduced-motion` por construcción.)
- **Alto contraste del sitio (`.contraste-govco`):** la lista y la fila activa mantienen borde y fondo
  propios; se comprueba en TC-BUS-34.

---

## 5. Página `/buscar`

### 5.1 Estructura

```
Inicio › Buscar
─────────────────────────────────────────────
<h1>Resultados de la búsqueda</h1>
[ licencia de construccion          ] [Buscar]        ← mismo componente, sin lista
Filtrar por:  Todo · Trámites · Políticas             ← enlaces; el actual con aria-current
─────────────────────────────────────────────
12 resultados para «licencia de construccion»         ← role="status"
┌─────────────────────────────────────────────┐
│ Trámite                                     │
│ Licencia de construcción        (enlace)    │
│ Autorización para construir, ampliar…       │  extracto ≤ 200 caracteres
│ Publicado el 14 de marzo de 2026            │
└─────────────────────────────────────────────┘
… (10 por página)
‹ Anterior   1  2  3 …  Siguiente ›              ← PaginacionGovco existente
```

- **URL:** `/buscar?q=…&tipo=tramite|politica&page=N`. Es la URL que se comparte.
- **Título de la pestaña:** «Resultados para «{q}» · {nombre actual del sitio}» según el patrón de
  títulos existente. **`noindex`** (una búsqueda no es contenido que indexar).
- **Sin JavaScript** el formulario envía por GET y la página se genera completa. (Depende de D-I4.)
- **Resultado:** categoría en texto; título como enlace a `url`; extracto; fecha con etiqueta según el
  tipo: **«Publicado el …»** para trámites (la fecha en que la sede lo publicó, no la de la ficha de
  GOV.CO) y **«Vigente desde …»** para políticas. `autor` y `miniatura` valen `null` hoy: **no se
  dibuja nada**, ni un hueco.
- **Foco al cambiar de página o de filtro:** pasa al `<h1>` (`tabindex="-1"`) y el resumen de
  resultados se anuncia por `role="status"`. Se apoya en el anunciador de rutas de Nuxt.
- **Filtro por tipo:** tres enlaces en un `<nav aria-label="Filtrar por tipo">`; el actual lleva
  `aria-current="true"`. Sin contadores (§3).

### 5.2 Estados de la página

| Estado | Texto | Marcado |
|---|---|---|
| **Sin término** (`/buscar` a secas) | «Escriba lo que busca: un trámite, una política o un servicio.» y el campo enfocado | `<p>` |
| **Cargando** (cambio de página o filtro en el navegador) | «Buscando…» con el contenido anterior atenuado y `aria-busy="true"` | región de resultados |
| **Con resultados** | «N resultados para «q».» («1 resultado para «q».») | `role="status"` |
| **Sin resultados** | «No encontramos resultados para «q».» + «Revise la ortografía o pruebe con otras palabras. También puede [ver todos los trámites].» | `<h2>` + `<p>` |
| **Consulta demasiado corta** (422) | «Escriba al menos 3 caracteres para buscar.» | `role="alert"` |
| **Consulta demasiado larga** (422) | «Use menos de 120 caracteres.» | `role="alert"` |
| **Error del servidor / red** | «No pudimos realizar la búsqueda en este momento. Intente de nuevo en unos minutos.» + botón «Reintentar» | `role="alert"` |
| **429** | «Hizo muchas búsquedas seguidas. Espere N segundos e intente de nuevo.» N de `Retry-After` | `role="alert"` |

El eco de lo buscado en «No encontramos resultados para «q»» se renderiza como **texto** (Vue lo
escapa); nunca como HTML.

---

## 6. Privacidad y límite

- **Lo que se escribe no se guarda** en el navegador (§4.5, regla 5), **no viaja a la analítica**
  (AC-26.1 mantiene la analítica apagada hasta el consentimiento y aun con él no se envía la consulta) y
  no se escribe en consola. El registro de acceso ya omite `q` (§2.2 de `busqueda.md`).
- **Límite de peticiones:** 120 por minuto y por IP (D-I8). Criterio de revisión: si tras salir a
  producción los 429 de las dos operaciones superan el **1 %** de las peticiones, o una IP concentra más
  de 10 personas, subir `BUSQUEDA_LIMITE_POR_MINUTO` a 240 sin tocar código. Se cuenta en el registro de
  acceso; la consulta no está, pero el estado y la ruta sí.

---

## 7. Textos (todos en un solo sitio para que Jurídica y comunicaciones los revisen)

| Clave | Texto |
|---|---|
| etiqueta del campo | Buscar en el sitio |
| marcador del campo | Buscar trámites, servicios y noticias |
| ayuda (descripción) | Escriba al menos 3 letras para ver sugerencias. |
| botón limpiar | Limpiar la búsqueda |
| botón buscar | Buscar |
| lista (nombre) | Sugerencias de búsqueda |
| buscando | Buscando sugerencias. |
| n sugerencias | N sugerencias disponibles. / 1 sugerencia disponible. |
| sin sugerencias | No hay sugerencias. Pulse Intro para buscar en toda la sede. |
| error de sugerencias | No pudimos consultar las sugerencias. Pulse Intro para buscar. |
| 429 (sugerencias) | Hizo muchas búsquedas seguidas. Espere N segundos para ver sugerencias; aun así puede pulsar Intro. |
| página: sin término | Escriba lo que busca: un trámite, una política o un servicio. |
| página: resultados | N resultados para «q». / 1 resultado para «q». |
| página: sin resultados | No encontramos resultados para «q». Revise la ortografía o pruebe con otras palabras. También puede ver todos los trámites. |
| página: corta | Escriba al menos 3 caracteres para buscar. |
| página: larga | Use menos de 120 caracteres. |
| página: error | No pudimos realizar la búsqueda en este momento. Intente de nuevo en unos minutos. |
| página: 429 | Hizo muchas búsquedas seguidas. Espere N segundos e intente de nuevo. |
| categorías | Trámite · Política |
| fechas | Publicado el {día} de {mes} de {año} · Vigente desde {día} de {mes} de {año} |

La redacción en lenguaje claro **no la ha revisado nadie de la Entidad**.

---

## 8. Cómo se comprueba (se ejecuta contra esta especificación)

| TC | Verifica | Contra qué sección | Tipo |
|---|---|---|---|
| TC-BUS-25 *(ya existe, se ajusta a 350 ms si D-I1 = sí)* | < 3 caracteres no consulta; ≥ 3 consulta **una vez** tras la pausa; la respuesta vieja no pisa a la última (se resuelven las promesas **en desorden**); caché sin repetir; composición sin peticiones; `Retry-After` pausa y no reintenta | §4.5 | Componente, temporizadores simulados |
| TC-BUS-26 | axe-core sobre `/buscar` y el encabezado con la lista **abierta, cerrada, sin resultados y con error**: 0 violaciones críticas o serias | §4.2, §4.6 | Auto. |
| TC-BUS-27 | Atributos ARIA exactos de §4.2; teclado de §4.3 (↑ ↓ Alt+↓ Intro Esc Tab, el recorrido por el campo); el foco visible no se pierde | §4.2, §4.3 | Navegador |
| TC-BUS-28 | Los siete estados de §4.4 y los ocho de §5.2 con su texto; **objetivos ≥ 44 px medidos por su caja**; capturas a **360 y 1280 px**; el círculo flotante **no tapa** la lista (comprobar que el elemento bajo el centro de cada fila es la fila) | §4.4, §4.6, §5.2 | Navegador + capturas |
| TC-BUS-32 | `/buscar?q=`: campos, enlaces, paginación, filtro por tipo, texto de «sin resultados», **sin resultado si no hay JavaScript** (si D-I4 lo permite) | §5 | Auto. |
| **TC-BUS-33** *(nuevo)* | Reflujo a 320 px y zoom al 200 %: sin desplazamiento horizontal ni texto cortado | §4.6 | Navegador |
| **TC-BUS-34** *(nuevo)* | Alto contraste del sitio y tamaño «A+»: la lista y la fila activa se distinguen; contrastes ≥ los de §2 | §4.6 | Navegador |
| **TC-BUS-35** *(nuevo)* | Tras varias búsquedas, `localStorage`, `sessionStorage`, cookies e IndexedDB **no cambian**; ninguna petición de analítica lleva la consulta | §4.5, §6 | Navegador |
| **TC-BUS-36** *(nuevo)* | Perfil de escritura lento (500 ms entre teclas, 20 caracteres): el número de peticiones coincide con el modelo (≈ una por tecla desde la 3.ª con pausa de 350 ms) y ninguna respuesta vieja se muestra | §4.5, `busqueda.md` §2.4 | Navegador |
| TC-BUS-29 | Recorrido con lector de pantalla real (NVDA o VoiceOver) | todo | **Manual, lo hace una persona.** Hoy es un límite declarado. |

---

## 9. Límites de esta especificación

1. **Sin pruebas con ciudadanos ni con lector de pantalla.** Cumplir el patrón de la APG y pasar axe
   **no** garantiza que el uso sea bueno. TC-BUS-29 lo cubre solo si alguien lo ejecuta.
2. **Las cifras de carga son un modelo** (`busqueda.md` §2.4): poblaciones de estudios internacionales,
   supuestos de variación míos y una consulta por minuto y persona activa. No hay datos de la
   ciudadanía de Santa Marta.
3. **El tamaño del teclado del móvil** (≈ 300–340 px) y los 48 px por fila son estimaciones de diseño, no
   medidas en dispositivos reales.
4. **No comparé con el Kit GOV.CO predictivo** (no está en el repositorio).
5. **Los textos no los revisó la Entidad.**
6. **D-I4 puede cambiar la implementación de la página**, no su comportamiento: si se elige D
   (resultados pedidos por el navegador), «sin JavaScript» deja de funcionar y TC-BUS-32 se ajusta.
7. **Los contrastes de la opción activa (`#E6EDFA`) son un color nuevo** que no está en `DESIGN.md`;
   si prefieres uno de la paleta, hay que recalcularlo.

---

## 10. Qué pasa cuando respondas

1. Con tus respuestas a D-I1…D-I9, ajusto este documento y lo dejo en **«Aprobada»** con la fecha.
2. Ajusto AC-22.2 y la tabla «Parámetros fijados» de la auditoría si D-I1 cambia la pausa.
3. Recién entonces empieza la entrega 3, en PR separados: componente, página, pruebas de navegador,
   capturas, medición del p95 de la página.
4. Si D-I4 elige A, B o C, ese cambio va en **su propio PR** y antes que la página, porque toca el
   sitio entero.
