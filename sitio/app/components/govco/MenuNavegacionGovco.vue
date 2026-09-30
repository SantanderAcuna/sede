<script setup lang="ts">
/**
 * Menú de navegación principal (Kit UI, componente general 5).
 *
 * Sigue el marcado de `general/menu-de-navegacion.html` y de
 * `src/general/menu-de-navegacion.css`. Los ítems llegan por props: el menú del
 * ejemplo es contenido de relleno y aquí no se copia.
 *
 * **El comportamiento del Kit no se carga.** Su `script.js` se autoinicializa
 * sobre selectores que en nuestras páginas no existen y no se enlaza desde
 * `nuxt.config.ts`; además el menú del Kit no se sostiene solo: delega el
 * despliegue de los paneles y el colapso en el JavaScript de Bootstrap, que
 * tampoco se carga. Todo eso se reimplementa aquí con estado reactivo. La
 * correspondencia con las funciones del Kit es:
 *
 *  - `initMenu` / `addEventsMenu`: el ciclo de vida del componente. Vue monta,
 *    enlaza los manejadores y los retira al desmontar; no hay que marcar los
 *    nodos con `actived-events-govco` para no enlazarlos dos veces.
 *  - `closeItemsMenu`: el estado. En vez de recorrer el DOM cerrando hermanos,
 *    `indiceAbierto` es un único índice, así que abrir un panel cierra el
 *    anterior por construcción. Lo mismo con `indiceAnidadoAbierto` dentro del
 *    menú extendido y con `menuExtendidoAbierto`.
 *  - `resizeMenu`: `anchoVentana` y los `computed` que reparten los ítems. El Kit
 *    movía nodos con `insertBefore` en cada evento `resize`, sin espera ni
 *    medida; aquí los ítems se dibujan donde toca según el ancho.
 *  - `eventClickItemMenu`: la clase `active` ya no se quita a mano; se deduce de
 *    la ruta, que es la fuente de verdad de dónde está uno.
 *
 * **El botón de hamburguesa va aquí, no en la cabecera.** El ejemplo del Kit lo
 * dibuja entre los logotipos y apunta con `data-bs-target` al colapso del menú;
 * al separar los dos componentes, el botón viaja con lo que gobierna.
 *
 * **Sin `role="menu"` ni `role="menuitem"`.** El ejemplo del Kit los pone, pero
 * son roles de aplicación —menús de barra, con foco atrapado y flechas— que no
 * corresponden a una lista de enlaces de navegación, y sin sus `menuitem`
 * obligatorios dejan el árbol accesible peor de lo que estaba. Esto es un patrón
 * de divulgación: un botón con `aria-expanded` y un panel de enlaces. Se conserva
 * `aria-haspopup` —el criterio lo pide— porque avisa de que el botón abre algo,
 * que es cierto, aunque el panel no sea un menú de aplicación.
 *
 * El teclado se resuelve con lo que el patrón necesita: `Tab` recorre, `Intro` y
 * `Espacio` despliegan —son botones nativos—, las flechas verticales abren y
 * recorren el panel, `Inicio` y `Fin` van a los extremos, y `Escape` cierra y
 * devuelve el foco al botón que abrió. No se secuestran las flechas laterales
 * entre los ítems de primer nivel: el Kit tampoco lo hacía —delegaba en
 * Bootstrap, que tampoco las implementa— y añadirlo convertiría una lista de
 * enlaces en algo que se comporta como una barra de menús sin serlo.
 */
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  useId,
  type ComponentPublicInstance,
} from 'vue'

/* ---------------------------------------------------------------------------
 * Contrato público
 * ------------------------------------------------------------------------ */

/** Enlace de destino dentro de una sección. */
interface EnlaceMenuGovco {
  /** Texto visible. Es también el nombre accesible del enlace. */
  etiqueta: string
  /** Ruta de destino: absoluta del sitio (`/tramites`) o externa (`https://…`). */
  ruta: string
}

/**
 * Sección interna de un submenú. Equivale al `ul[title]` del Kit, que dibuja el
 * encabezado con un `::before` a partir del atributo.
 */
interface SubseccionMenuGovco {
  /** Encabezado de la sección. */
  titulo: string
  enlaces: EnlaceMenuGovco[]
}

/**
 * Ítem de primer nivel.
 *
 * `ruta` es opcional a propósito: un ítem con subsecciones es un botón que
 * despliega el panel —así lo dibuja el Kit— y no navega a ninguna parte. Un ítem
 * sin `ruta` y sin `subsecciones` no es pulsable ni navegable, y no se dibuja.
 */
interface ItemMenuGovco {
  etiqueta: string
  ruta?: string
  /** Hasta cuatro por ítem (CAG-09). */
  subsecciones?: SubseccionMenuGovco[]
}

interface Props {
  items: ItemMenuGovco[]
  /**
   * Nombre accesible del `nav` (CAG-10). Hay uno por defecto, pero conviene
   * nombrarlo según la página cuando conviven varios menús.
   */
  etiquetaAccesible?: string
}

const { items, etiquetaAccesible = 'Menú principal' } = defineProps<Props>()

/**
 * Forma interna, ya validada y acotada. Al dibujar no se vuelve a preguntar por
 * los límites: lo que llega a la plantilla ya los cumple.
 */
interface ItemMenuNormalizado {
  etiqueta: string
  /** `null` sólo en los ítems que despliegan subsecciones. */
  ruta: string | null
  /** Vacío en los ítems que enlazan directamente. */
  subsecciones: SubseccionMenuGovco[]
}

/* ---------------------------------------------------------------------------
 * Criterios y límites
 * ------------------------------------------------------------------------ */

/** CAG-09: siete ítems de primer nivel como máximo. */
const MAX_ITEMS = 7

/** CAG-09: cuatro secciones internas como máximo por ítem. */
const MAX_SECCIONES = 4

/** El Kit conserva cuatro ítems en la barra y desborda el resto. */
const ITEMS_EN_BARRA = 4

/** Ancho hasta el cual el Kit reparte los ítems sobrantes al menú extendido. */
const ANCHO_MENU_EXTENDIDO = 992

/** Punto de ruptura `md`: por debajo, el Kit colapsa el menú en la hamburguesa. */
const ANCHO_ESCRITORIO = 768

/**
 * Los datos se acotan antes de dibujarse. Se recorta y se avisa en lugar de
 * fallar: un menú con ocho ítems es un error de contenido —que se corrige en los
 * datos, no en el código— y no debe tumbar una página pública. El aviso queda
 * sólo en desarrollo.
 */
const itemsDelMenu = computed<ItemMenuNormalizado[]>(() => {
  const itemsDeMas = Math.max(0, items.length - MAX_ITEMS)
  const seccionesDeMas = items.reduce(
    (total, item) => total + Math.max(0, (item.subsecciones?.length ?? 0) - MAX_SECCIONES),
    0,
  )

  if (import.meta.dev && (itemsDeMas > 0 || seccionesDeMas > 0)) {
    console.warn(
      `[MenuNavegacionGovco] CAG-09: el menú admite ${MAX_ITEMS} ítems de primer nivel y ` +
        `${MAX_SECCIONES} secciones internas por ítem. ` +
        `Se recortaron ${itemsDeMas} ítem(s) y ${seccionesDeMas} sección(es).`,
    )
  }

  return (
    items
      .slice(0, MAX_ITEMS)
      .map((item) => ({
        etiqueta: item.etiqueta,
        ruta: item.ruta ?? null,
        // Una sección sin enlaces dejaría un encabezado suelto sobre la nada.
        subsecciones: (item.subsecciones ?? [])
          .slice(0, MAX_SECCIONES)
          .filter((subseccion) => subseccion.enlaces.length > 0),
      }))
      .filter((item) => item.ruta !== null || item.subsecciones.length > 0)
  )
})

/* ---------------------------------------------------------------------------
 * Ancho de ventana
 * ------------------------------------------------------------------------ */

/**
 * El servidor no conoce el ancho de la ventana y no debe inventarlo: se parte de
 * un valor que reproduce la disposición de escritorio —la única que se puede
 * renderizar en HTML estático— y se corrige al montar. Adivinar móvil aquí
 * mandaría los ítems 5 y siguientes al menú extendido para devolverlos después,
 * con un salto visible en cada carga.
 */
const anchoVentana = ref(Number.POSITIVE_INFINITY)

function medirAncho() {
  anchoVentana.value = window.innerWidth
}

const esEscritorio = computed(() => anchoVentana.value >= ANCHO_ESCRITORIO)

/**
 * Reparto de ítems, que es lo que el Kit hacía moviendo nodos en `resizeMenu`.
 *
 * Sólo se desborda a partir de `md`: el botón del menú extendido está oculto por
 * debajo —así lo marca el propio Kit con `d-none d-md-block`— y su `resizeMenu`,
 * que se dispara con cualquier ancho por debajo de 992 px, dejaría los ítems 5 y
 * siguientes dentro de un panel que en un teléfono no se puede abrir. Por eso
 * allí no se reparte nada y el menú colapsado los muestra todos.
 */
const itemsEnBarra = computed(() =>
  esEscritorio.value && anchoVentana.value <= ANCHO_MENU_EXTENDIDO
    ? itemsDelMenu.value.slice(0, ITEMS_EN_BARRA)
    : itemsDelMenu.value,
)

const itemsExtendidos = computed(() =>
  esEscritorio.value && anchoVentana.value <= ANCHO_MENU_EXTENDIDO
    ? itemsDelMenu.value.slice(ITEMS_EN_BARRA)
    : [],
)

/* ---------------------------------------------------------------------------
 * Estado de apertura
 * ------------------------------------------------------------------------ */

/** Índice del panel abierto en la barra. Uno solo a la vez. */
const indiceAbierto = ref<number | null>(null)

/** Menú extendido («más ítems») abierto. */
const menuExtendidoAbierto = ref(false)

/** Submenú anidado abierto dentro del menú extendido. Uno solo a la vez. */
const indiceAnidadoAbierto = ref<number | null>(null)

/** Menú colapsado por la hamburguesa. */
const menuAbierto = ref(false)

function abrirSubmenu(indice: number) {
  // Abrir uno cierra el resto: es lo que hacía Bootstrap al desplegar, y lo que
  // `closeItemsMenu` garantizaba dentro del menú extendido.
  indiceAbierto.value = indice
  menuExtendidoAbierto.value = false
  indiceAnidadoAbierto.value = null
}

function alternarSubmenu(indice: number) {
  if (indiceAbierto.value === indice) {
    indiceAbierto.value = null
    return
  }
  abrirSubmenu(indice)
}

function alternarMenuExtendido() {
  menuExtendidoAbierto.value = !menuExtendidoAbierto.value
  indiceAbierto.value = null
  indiceAnidadoAbierto.value = null
}

function alternarSubmenuAnidado(indice: number) {
  indiceAnidadoAbierto.value = indiceAnidadoAbierto.value === indice ? null : indice
}

function alternarMenu() {
  menuAbierto.value = !menuAbierto.value
  if (!menuAbierto.value) {
    indiceAbierto.value = null
  }
}

function cerrarPaneles() {
  indiceAbierto.value = null
  menuExtendidoAbierto.value = false
  indiceAnidadoAbierto.value = null
}

function cerrarTodo() {
  cerrarPaneles()
  menuAbierto.value = false
}

/* ---------------------------------------------------------------------------
 * Referencias a elementos
 *
 * El foco sólo se puede mover sobre el DOM, así que hacen falta las referencias
 * a los controles que lo reciben. Se registran con `ref` de función y no con
 * `document.querySelector`: el componente no busca fuera de sí mismo.
 * ------------------------------------------------------------------------ */

const raiz = ref<HTMLElement | null>(null)
const disparadores = ref<(HTMLButtonElement | null)[]>([])
const paneles = ref<(HTMLElement | null)[]>([])
const disparadoresAnidados = ref<(HTMLButtonElement | null)[]>([])
const panelExtendido = ref<HTMLElement | null>(null)
const disparadorExtendido = ref<HTMLButtonElement | null>(null)
const disparadorMenu = ref<HTMLButtonElement | null>(null)

type ElementoRef = Element | ComponentPublicInstance | null

function registrarDisparador(elemento: ElementoRef, indice: number) {
  disparadores.value[indice] = elemento as HTMLButtonElement | null
}

function registrarPanel(elemento: ElementoRef, indice: number) {
  paneles.value[indice] = elemento as HTMLElement | null
}

function registrarDisparadorAnidado(elemento: ElementoRef, indice: number) {
  disparadoresAnidados.value[indice] = elemento as HTMLButtonElement | null
}

/* ---------------------------------------------------------------------------
 * Utilidades de la plantilla
 * ------------------------------------------------------------------------ */

const idBase = useId()

const idPanel = computed(() => `${idBase}-panel-menu`)
const idPanelExtendido = computed(() => `${idBase}-panel-extendido`)
const idDisparador = (indice: number) => `${idBase}-disparador-${indice}`
const idSubmenu = (indice: number) => `${idBase}-submenu-${indice}`
const idSubmenuAnidado = (indice: number) => `${idBase}-submenu-anidado-${indice}`

function tieneSubsecciones(item: ItemMenuNormalizado): boolean {
  return item.subsecciones.length > 0
}

/**
 * Dentro del menú extendido no caben las columnas: sus secciones se funden en
 * una sola lista, que es la forma que usa la «versión extendida» del ejemplo.
 */
function aplanarSubsecciones(item: ItemMenuNormalizado): EnlaceMenuGovco[] {
  return item.subsecciones.flatMap((subseccion) => subseccion.enlaces)
}

const rutaActual = useRoute()

/** ¿Es este destino la sección en la que se está? */
function esActivo(destino: string | null): boolean {
  if (destino === null || !destino.startsWith('/')) {
    // Los enlaces externos no son «la página actual» de este sitio.
    return false
  }
  return destino === '/'
    ? rutaActual.path === '/'
    : rutaActual.path === destino || rutaActual.path.startsWith(`${destino}/`)
}

/**
 * Clases de anchura y alineación del panel, con la misma correspondencia que el
 * ejemplo del Kit: dos columnas, tres columnas, panel ancho a partir de cuatro y
 * alineación a la derecha cuando el ítem cierra la barra, para que su panel no
 * se salga por el borde de la ventana.
 */
function clasesDelPanel(item: ItemMenuNormalizado, indice: number): Record<string, boolean> {
  const secciones = item.subsecciones.length
  const cierraLaBarra = indice === itemsEnBarra.value.length - 1

  return {
    show: indiceAbierto.value === indice,
    'col-2-menu-govco': secciones === 2,
    'dropdown-menu-md-center-govco': secciones === 2,
    'col-3-menu-govco': secciones === 3,
    'dropdown-menu-xl-center-govco': secciones === 3,
    'megamenu-menu-govco': secciones >= MAX_SECCIONES,
    'dropdown-menu-end': secciones === 1 && cierraLaBarra,
  }
}

/** El panel ancho se ancla a la barra entera, no al ítem que lo abre. */
function esMegamenu(item: ItemMenuNormalizado): boolean {
  return item.subsecciones.length >= MAX_SECCIONES
}

/* ---------------------------------------------------------------------------
 * Foco
 * ------------------------------------------------------------------------ */

/**
 * Enlaces de un panel. Es la única consulta al DOM del componente y va acotada
 * al panel que él mismo ha dibujado: mover el foco sin tocar el DOM no es
 * posible, y duplicar los enlaces en referencias paralelas sería una fuente de
 * desajustes.
 *
 * Se descartan los enlaces que pertenecen a un subpanel anidado —el caso del
 * menú extendido—: cuando ese subpanel está cerrado sus enlaces no se ven, y
 * enfocar algo invisible deja el foco perdido.
 */
function enlacesDe(panel: HTMLElement | null): HTMLAnchorElement[] {
  if (panel === null) {
    return []
  }
  return Array.from(panel.querySelectorAll<HTMLAnchorElement>('a[href]')).filter(
    (enlace) => enlace.closest('.dropdown-menu') === panel,
  )
}

function indiceSiguiente(total: number, actual: number, paso: 1 | -1): number {
  return (actual + paso + total) % total
}

/**
 * `Escape` cierra lo que esté abierto y devuelve el foco al botón que lo abrió
 * (WCAG 2.1, 2.4.3: el foco no se queda huérfano al desaparecer lo que tenía
 * delante). Vive en el `nav` y no en cada control porque el foco puede estar en
 * cualquier punto del menú.
 */
function alPulsarEscape(evento: KeyboardEvent) {
  if (evento.key !== 'Escape') {
    return
  }

  if (indiceAnidadoAbierto.value !== null) {
    const abierto = indiceAnidadoAbierto.value
    indiceAnidadoAbierto.value = null
    disparadoresAnidados.value[abierto]?.focus()
    return
  }

  if (menuExtendidoAbierto.value) {
    menuExtendidoAbierto.value = false
    disparadorExtendido.value?.focus()
    return
  }

  if (indiceAbierto.value !== null) {
    const abierto = indiceAbierto.value
    indiceAbierto.value = null
    disparadores.value[abierto]?.focus()
    return
  }

  if (menuAbierto.value) {
    menuAbierto.value = false
    disparadorMenu.value?.focus()
  }
}

/**
 * Teclado sobre un botón que despliega: las flechas verticales abren el panel y
 * entran en él por el primer o el último enlace. El `nextTick` no es adorno: el
 * panel está en `display: none` hasta que cambia la clase, y no se puede enfocar
 * lo que no se ve.
 */
function alTeclearDisparador(evento: KeyboardEvent, indice: number) {
  if (evento.key !== 'ArrowDown' && evento.key !== 'ArrowUp') {
    return
  }

  evento.preventDefault()
  abrirSubmenu(indice)

  nextTick(() => {
    const enlaces = enlacesDe(paneles.value[indice])
    enlaces[evento.key === 'ArrowDown' ? 0 : enlaces.length - 1]?.focus()
  })
}

/** Igual, para el botón del menú extendido, que no tiene índice de barra. */
function alTeclearDisparadorExtendido(evento: KeyboardEvent) {
  if (evento.key !== 'ArrowDown' && evento.key !== 'ArrowUp') {
    return
  }

  evento.preventDefault()
  menuExtendidoAbierto.value = true
  indiceAbierto.value = null
  indiceAnidadoAbierto.value = null

  nextTick(() => {
    const enlaces = enlacesDe(panelExtendido.value)
    enlaces[evento.key === 'ArrowDown' ? 0 : enlaces.length - 1]?.focus()
  })
}

/** Dentro de un panel: recorrido circular y saltos al principio y al fin. */
function alTeclearPanel(evento: KeyboardEvent) {
  const panel = evento.currentTarget as HTMLElement | null
  const origen = evento.target as HTMLElement | null

  // En el menú extendido conviven enlaces y botones que despliegan. Si la tecla
  // viene de un botón, gobierna su propio manejador y aquí no se toca nada.
  if (origen === null || origen.tagName !== 'A') {
    return
  }

  const enlaces = enlacesDe(panel)
  if (enlaces.length === 0) {
    return
  }

  if (evento.key === 'Home' || evento.key === 'End') {
    evento.preventDefault()
    enlaces[evento.key === 'Home' ? 0 : enlaces.length - 1]?.focus()
    return
  }

  const paso = evento.key === 'ArrowDown' ? 1 : evento.key === 'ArrowUp' ? -1 : 0
  if (paso === 0) {
    return
  }

  evento.preventDefault()
  const posicion = enlaces.indexOf(origen as HTMLAnchorElement)
  // Si el foco no estaba en un enlace del panel, se entra por el extremo que
  // corresponde al sentido de la flecha.
  const base = posicion === -1 ? (paso === 1 ? -1 : 0) : posicion
  enlaces[indiceSiguiente(enlaces.length, base, paso)]?.focus()
}

/**
 * Al abandonar el menú —tabulando hacia el resto de la página— se cierra lo que
 * quedara abierto. Se escucha en la lista y no en cada ítem a propósito: cerrar
 * el panel de un ítem al pulsar otro desplazaría lo que hay debajo —los paneles
 * dentro del menú colapsado y los anidados del menú extendido fluyen en la
 * página en lugar de flotar— y el clic acabaría cayendo sobre un elemento que ya
 * no está donde estaba. Con un solo punto de escucha, el cambio de panel lo
 * resuelve el estado, sin mover nada antes de tiempo. Pasar de un ítem al
 * siguiente, por tanto, no cierra el panel: abrir otro —o salir del menú— sí, que
 * es lo que hace también el menú del Kit cuando lo gobierna Bootstrap.
 */
function alSalirFocoMenu(evento: FocusEvent) {
  const lista = evento.currentTarget as HTMLElement | null
  const destino = evento.relatedTarget as Node | null

  if (lista !== null && destino !== null && lista.contains(destino)) {
    return
  }

  cerrarPaneles()
}

/**
 * Cierre al pulsar fuera. El panel del Kit se cerraba desde Bootstrap, que
 * escucha en el documento; `focusout` no basta, porque al pulsar sobre algo que
 * no recibe el foco —el fondo de la página— no hay cambio de foco que observar.
 */
function alClicFuera(evento: MouseEvent) {
  const destino = evento.target as Node | null
  if (destino !== null && raiz.value?.contains(destino)) {
    return
  }
  cerrarTodo()
}

onMounted(() => {
  medirAncho()
  window.addEventListener('resize', medirAncho, { passive: true })
  document.addEventListener('click', alClicFuera)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', medirAncho)
  document.removeEventListener('click', alClicFuera)
})
</script>

<template>
  <nav
    ref="raiz"
    class="navbar navbar-expand-md menu-govco"
    :aria-label="etiquetaAccesible"
    @keydown="alPulsarEscape"
  >
    <div class="container-fluid">
      <!--
        Fila del móvil. El Kit pone la hamburguesa junto a los logotipos, pero
        como aquí la cabecera es otro componente, el botón se queda con lo que
        gobierna: este menú.
      -->
      <div class="barra-menu-movil-govco">
        <button
          ref="disparadorMenu"
          class="navbar-toggler btn-menu-govco"
          type="button"
          :aria-expanded="menuAbierto"
          :aria-controls="idPanel"
          :aria-label="menuAbierto ? 'Cerrar el menú de navegación' : 'Abrir el menú de navegación'"
          @click="alternarMenu"
        >
          <span class="icon-menu-govco" aria-hidden="true" />
        </button>
      </div>

      <div :id="idPanel" class="collapse navbar-collapse" :class="{ show: menuAbierto }">
        <ul class="navbar-nav" @focusout="alSalirFocoMenu">
          <template v-for="(item, indice) in itemsEnBarra" :key="item.etiqueta">
            <!-- Ítem con subsecciones: el Kit lo dibuja como botón que despliega. -->
            <li
              v-if="tieneSubsecciones(item)"
              class="nav-item dropdown"
              :class="{ 'position-static': esMegamenu(item) }"
            >
              <button
                :id="idDisparador(indice)"
                :ref="(elemento) => registrarDisparador(elemento, indice)"
                class="nav-link dropdown-toggle"
                type="button"
                aria-haspopup="true"
                :aria-expanded="indiceAbierto === indice"
                :aria-controls="idSubmenu(indice)"
                @click="alternarSubmenu(indice)"
                @keydown="alTeclearDisparador($event, indice)"
              >
                <span>{{ item.etiqueta }}</span>
              </button>

              <!--
                El `title` de cada `ul` no es decorativo: el Kit dibuja con CSS
                el encabezado de la sección (`ul[title]::before`), lo que lo deja
                fuera del árbol accesible. Por eso el mismo texto va también en
                `aria-label`.
              -->
              <div
                :id="idSubmenu(indice)"
                :ref="(elemento) => registrarPanel(elemento, indice)"
                class="dropdown-menu"
                :class="clasesDelPanel(item, indice)"
                @keydown="alTeclearPanel"
              >
                <ul
                  v-for="subseccion in item.subsecciones"
                  :key="subseccion.titulo"
                  :title="subseccion.titulo"
                  :aria-label="subseccion.titulo"
                >
                  <li v-for="enlace in subseccion.enlaces" :key="`${enlace.ruta}|${enlace.etiqueta}`">
                    <NuxtLink
                      class="dropdown-item"
                      :to="enlace.ruta"
                      :class="{ active: esActivo(enlace.ruta) }"
                      @click="cerrarTodo()"
                    >
                      {{ enlace.etiqueta }}
                    </NuxtLink>
                  </li>
                </ul>
              </div>
            </li>

            <!-- Ítem simple: enlace directo, sin panel. -->
            <li v-else-if="item.ruta !== null" class="nav-item">
              <NuxtLink
                class="nav-link"
                :to="item.ruta"
                :class="{ active: esActivo(item.ruta) }"
                @click="cerrarTodo()"
              >
                <span>{{ item.etiqueta }}</span>
              </NuxtLink>
            </li>
          </template>

          <!--
            Menú extendido: los ítems que no caben en la barra. Sólo se dibuja si
            hay alguno, para no dejar un botón que no abre nada.
          -->
          <li
            v-if="itemsExtendidos.length > 0"
            class="nav-item dropdown ext-menu-govco"
          >
            <button
              ref="disparadorExtendido"
              class="nav-link dropdown-toggle btn-menu-govco"
              type="button"
              aria-haspopup="true"
              :aria-expanded="menuExtendidoAbierto"
              :aria-controls="idPanelExtendido"
              :aria-label="
                menuExtendidoAbierto
                  ? 'Cerrar el menú de más opciones'
                  : 'Abrir el menú de más opciones'
              "
              @click="alternarMenuExtendido"
              @keydown="alTeclearDisparadorExtendido"
            >
              <span class="icon-menu-govco" aria-hidden="true" />
            </button>

            <ul
              :id="idPanelExtendido"
              ref="panelExtendido"
              class="dropdown-menu dropdown-menu-end"
              :class="{ show: menuExtendidoAbierto }"
              @keydown="alTeclearPanel"
            >
              <template v-for="(item, indice) in itemsExtendidos" :key="item.etiqueta">
                <li
                  v-if="tieneSubsecciones(item)"
                  class="nav-item dropdown"
                >
                  <button
                    :ref="(elemento) => registrarDisparadorAnidado(elemento, indice)"
                    class="nav-link dropdown-toggle"
                    type="button"
                    aria-haspopup="true"
                    :aria-expanded="indiceAnidadoAbierto === indice"
                    :aria-controls="idSubmenuAnidado(indice)"
                    @click="alternarSubmenuAnidado(indice)"
                  >
                    <span>{{ item.etiqueta }}</span>
                  </button>

                  <ul
                    :id="idSubmenuAnidado(indice)"
                    class="dropdown-menu"
                    :class="{ show: indiceAnidadoAbierto === indice }"
                    @keydown="alTeclearPanel"
                  >
                    <li
                      v-for="enlace in aplanarSubsecciones(item)"
                      :key="`${enlace.ruta}|${enlace.etiqueta}`"
                    >
                      <NuxtLink
                        class="dropdown-item"
                        :to="enlace.ruta"
                        :class="{ active: esActivo(enlace.ruta) }"
                        @click="cerrarTodo()"
                      >
                        {{ enlace.etiqueta }}
                      </NuxtLink>
                    </li>
                  </ul>
                </li>

                <li v-else-if="item.ruta !== null" class="nav-item">
                  <NuxtLink
                    class="nav-link"
                    :to="item.ruta"
                    :class="{ active: esActivo(item.ruta) }"
                    @click="cerrarTodo()"
                  >
                    <span>{{ item.etiqueta }}</span>
                  </NuxtLink>
                </li>
              </template>
            </ul>
          </li>
        </ul>

        <!--
          Buscador del móvil. En el ejemplo va dentro del menú desplegado y en
          escritorio en la cabecera; el componente de búsqueda rellena ambos
          huecos. Si no hay contenido no se dibuja el contenedor, que quedaría
          como una franja blanca vacía con relleno.
        -->
        <div v-if="$slots.buscador" class="container-search-menu">
          <slot name="buscador" />
        </div>
      </div>
    </div>
  </nav>
</template>

<style scoped>
/*
 * ===========================================================================
 * Lo que ni el Kit ni Bootstrap resuelven solos
 * ===========================================================================
 *
 * El marcado del Kit presupone Bootstrap —sus ejemplos lo cargan desde un CDN— y
 * aquí está enlazado (`/govco/bootstrap.min.css`), así que de él vienen la caja
 * de la barra, el colapso, el `display: none` de los paneles sin desplegar, su
 * posicionamiento dentro del ítem y utilidades como `d-md-none` o
 * `position-static`.
 *
 * Lo que falta es lo que Bootstrap escribe **por JavaScript**, añadiendo el
 * atributo `data-bs-popper` al panel que abre, y que por tanto no está en su
 * hoja: su `top` y su alineación a la derecha. Ese JavaScript no se carga —el
 * despliegue lo gobierna el estado reactivo—, así que esas dos cosas se ponen
 * aquí. Van con la especificidad suficiente para no depender del orden en que se
 * carguen las hojas.
 */

.menu-govco .navbar-nav .dropdown-menu {
  top: 100%;
}

.menu-govco .navbar-nav li .dropdown-menu-end {
  right: 0;
}

/* --- Visibilidad propia del componente ----------------------------------- */

/* La fila de la hamburguesa sólo existe mientras el menú está colapsado. */
.barra-menu-movil-govco {
  display: flex;
  width: 100%;
  justify-content: flex-end;
}

@media (min-width: 768px) {
  .barra-menu-movil-govco {
    display: none;
  }

  /* El buscador del ejemplo va dentro del menú desplegado, y en escritorio en la
     cabecera; aquí sólo tiene sentido cuando el menú está colapsado. */
  .menu-govco .container-search-menu {
    display: none;
  }
}
</style>
