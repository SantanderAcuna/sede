<script setup lang="ts">
/**
 * Galería de aplicaciones (Kit UI, componente general 4).
 *
 * Portado de `examples/general/galeria-de-aplicaciones.html` y de
 * `activeItemCandy()` del `script.js`. La bandeja del Kit es un desplegable de
 * Bootstrap: arrastra `data-bs-toggle="dropdown"` y `onclick` en línea, y confía
 * en que el JavaScript de Bootstrap la abra y la cierre. Ese JavaScript no se
 * carga —se autoinicializa sobre selectores que en nuestras páginas no existen—,
 * así que la apertura y el cierre son estado reactivo. `activeItemCandy()`, que
 * recorría el documento con `getElementById` para marcar el elemento pulsado, es
 * ahora un único `ref` con el identificador de la aplicación activa.
 *
 * Accesibilidad (CAG-31):
 *
 *  - El disparador es un `<button>` con `aria-expanded` y `aria-controls`, así que
 *    `Enter` y `Espacio` abren y cierran sin nada capturado a mano.
 *  - `Esc` cierra la bandeja y devuelve el foco al disparador.
 *  - Los elementos son enlaces o botones nativos y están en el DOM en el mismo
 *    orden que en pantalla —de izquierda a derecha y de arriba abajo—, de modo que
 *    el tabulador ya los recorre en ese orden. Las flechas, además, mueven el foco
 *    por esa misma rejilla.
 *  - Las entradas deshabilitadas no reciben foco; el recorrido con flechas las
 *    salta en vez de quedarse atascado en ellas.
 *
 * Se aparta del Kit en dos puntos. La bandeja tiene alto fijo (9,375rem) para una
 * sola fila de tres: con más aplicaciones la cuarta quedaría fuera, así que se deja
 * crecer, y la rejilla es de tres columnas —las que caben en el ancho que el Kit
 * fija— con las filas que hagan falta. Y el Kit cuelga la bandeja del borde derecho
 * del disparador, de modo que con el botón separado del borde de la página se
 * escapa 18rem hacia la izquierda; aquí se centra respecto al botón —como la dibuja
 * su propio ejemplo— y se corrige al abrir para que no se salga de la ventana.
 */
import type { ComponentPublicInstance } from 'vue'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId } from 'vue'

export type IconoGaleria = 'govco' | 'carpeta' | 'ciiu'

export interface AplicacionGaleria {
  id: string
  nombre: string
  /** Sin enlace, la entrada es un botón que avisa por el evento. */
  enlace?: string
  icono?: IconoGaleria
  deshabilitada?: boolean
}

/** Columnas de la rejilla: es también el salto de una fila en la navegación. */
const COLUMNAS = 3

const CLASE_ICONO: Record<IconoGaleria, string> = {
  govco: 'icon-govco-govco',
  carpeta: 'icon-folder-user-govco',
  ciiu: 'icon-ciiu-govco',
}

const PASO_FLECHA: Record<string, number> = {
  ArrowRight: 1,
  ArrowLeft: -1,
  ArrowDown: COLUMNAS,
  ArrowUp: -COLUMNAS,
}

interface PropsGaleria {
  aplicaciones?: AplicacionGaleria[]
  /** Título de la bandeja. */
  titulo?: string
  /** Nombre del botón. El globo de ayuda va aparte, como descripción. */
  etiquetaBoton?: string
  ayudaBoton?: string
}

const props = withDefaults(defineProps<PropsGaleria>(), {
  // Aplicaciones de ejemplo, sin enlace: así el componente se puede ver solo y las
  // entradas avisan por el evento en vez de llevar a ninguna parte. Van en línea
  // porque `defineProps()` se compila fuera de `setup()` y no puede referenciar
  // variables locales del archivo.
  aplicaciones: () => [
    { id: 'ejemplo-1', nombre: 'Aplicación de ejemplo 1', icono: 'govco' as IconoGaleria },
    { id: 'ejemplo-2', nombre: 'Aplicación de ejemplo 2', icono: 'carpeta' as IconoGaleria },
    { id: 'ejemplo-3', nombre: 'Aplicación de ejemplo 3', icono: 'ciiu' as IconoGaleria },
  ],
  titulo: 'Aplicaciones y servicios',
  etiquetaBoton: 'Abrir las aplicaciones y servicios',
  ayudaBoton: 'Acceso a las aplicaciones y servicios de la sede electrónica.',
})

const emit = defineEmits<{ seleccionar: [aplicacion: AplicacionGaleria] }>()

const abierto = ref(false)
const activaId = ref<string | null>(null)
const indiceFoco = ref(0)
const raiz = ref<HTMLElement | null>(null)
const boton = ref<HTMLButtonElement | null>(null)
const panel = ref<HTMLElement | null>(null)
const items = ref<(HTMLElement | null)[]>([])

/** Corrección horizontal de la bandeja para que quepa en la ventana. */
const desplazamientoPanel = ref(0)

const estiloAnclaje = computed(() =>
  desplazamientoPanel.value === 0
    ? {}
    : { transform: `translate(calc(-50% + ${desplazamientoPanel.value}px), 2.625rem)` },
)

const idBase = useId()
const idPanel = `${idBase}-panel`
const idAyuda = `${idBase}-ayuda`

const total = computed(() => props.aplicaciones.length)

function claseIcono(icono?: IconoGaleria): string {
  return CLASE_ICONO[icono ?? 'govco']
}

function registrarItem(elemento: Element | ComponentPublicInstance | null, indice: number): void {
  items.value[indice] = elemento instanceof HTMLElement ? elemento : null
}

/**
 * Enlaza la referencia de cada ítem con su índice.
 *
 * La función se declara aquí y no en la plantilla porque la plantilla no infiere
 * el tipo del parámetro y TypeScript lo marca como `any` implícito —que en este
 * proyecto está prohibido—. Devuelta así, el tipo viaja con la función.
 */
function registrarItemEn(indice: number) {
  return (elemento: Element | ComponentPublicInstance | null): void =>
    registrarItem(elemento, indice)
}

function etiquetaItem(aplicacion: AplicacionGaleria): 'a' | 'button' {
  return aplicacion.enlace !== undefined && !aplicacion.deshabilitada ? 'a' : 'button'
}

function atributosItem(aplicacion: AplicacionGaleria): Record<string, string | boolean> {
  if (aplicacion.deshabilitada) return { type: 'button', disabled: true }
  if (aplicacion.enlace !== undefined) return { href: aplicacion.enlace }
  return { type: 'button' }
}

/** Índice que se pide, siempre dentro de la lista. */
function normalizar(indice: number): number {
  return ((indice % total.value) + total.value) % total.value
}

function enfocarItem(indice: number): void {
  indiceFoco.value = indice
  items.value[indice]?.focus()
}

/** Avanza o retrocede por la rejilla saltando las entradas deshabilitadas. */
function moverFoco(paso: number): void {
  if (total.value === 0) return
  for (let salto = 1; salto <= total.value; salto += 1) {
    const indice = normalizar(indiceFoco.value + paso * salto)
    if (props.aplicaciones[indice]?.deshabilitada === true) continue
    enfocarItem(indice)
    return
  }
}

/**
 * Mantiene la bandeja dentro de la ventana: centrada respecto al botón, pero sin
 * salirse por los lados cuando el botón queda pegado a un borde —o cuando la
 * pantalla es más estrecha que la bandeja—. Se mide al abrir, que es cuando la
 * bandeja ya tiene tamaño; cerrada no lo tiene, porque su anclaje está oculto.
 */
function ajustarAnclaje(): void {
  const cajaBoton = boton.value?.getBoundingClientRect()
  const anchoPanel = panel.value?.getBoundingClientRect().width ?? 0
  if (cajaBoton === undefined || anchoPanel === 0) return

  const MARGEN = 8
  const centro = cajaBoton.x + cajaBoton.width / 2
  const izquierdaCentrada = centro - anchoPanel / 2
  const izquierdaMaxima = Math.max(MARGEN, window.innerWidth - anchoPanel - MARGEN)
  const izquierda = Math.min(Math.max(izquierdaCentrada, MARGEN), izquierdaMaxima)
  desplazamientoPanel.value = Math.round(izquierda - izquierdaCentrada)
}

function cerrar(): void {
  abierto.value = false
}

function cerrarYDevolverFoco(): void {
  cerrar()
  boton.value?.focus()
}

async function alternar(): Promise<void> {
  if (abierto.value) {
    cerrar()
    return
  }
  abierto.value = true
  // La bandeja tiene que estar visible antes de poder medirla y llevar el foco
  // dentro.
  await nextTick()
  ajustarAnclaje()
  // Desde una posición anterior a la primera, el salto deja el foco en la primera
  // entrada disponible; si estuviera deshabilitada, el recorrido sigue buscando.
  indiceFoco.value = -1
  moverFoco(1)
}

function alActivar(aplicacion: AplicacionGaleria): void {
  if (aplicacion.deshabilitada === true) return
  // `activeItemCandy()` del Kit: marca la entrada pulsada y desmarca el resto.
  activaId.value = aplicacion.id
  cerrar()
  emit('seleccionar', aplicacion)
}

function alPulsarTecla(evento: KeyboardEvent): void {
  if (evento.key === 'Escape') {
    if (abierto.value) cerrarYDevolverFoco()
    return
  }
  if (!abierto.value) return

  const paso = PASO_FLECHA[evento.key]
  if (paso === undefined) return
  evento.preventDefault()
  moverFoco(paso)
}

function alPulsarFuera(evento: MouseEvent): void {
  const destino = evento.target
  if (!(destino instanceof Node)) return
  if (raiz.value?.contains(destino) === true) return
  cerrar()
}

onMounted(() => document.addEventListener('click', alPulsarFuera))
onBeforeUnmount(() => document.removeEventListener('click', alPulsarFuera))
</script>

<template>
  <div ref="raiz" class="contenedor-galeria-govco" @keydown="alPulsarTecla">
    <div class="dropdown-container-govco">
      <div class="dropdown">
        <button
          ref="boton"
          type="button"
          class="button-rounded-menu-govco"
          :aria-label="etiquetaBoton"
          :aria-expanded="abierto"
          :aria-controls="idPanel"
          :aria-describedby="idAyuda"
          @click="alternar"
        >
          <!-- El Kit necesita dos flechas porque tiene una regla para cada estado y
               cada una apunta a una relación distinta: la de dentro se ve al pasar
               el ratón con la bandeja cerrada, y la de fuera, al estar abierta. -->
          <span class="dropdown-arrow-govco" aria-hidden="true" />
          <span :id="idAyuda" class="tooltip-text-govco">{{ ayudaBoton }}</span>
        </button>
        <span class="dropdown-arrow-govco" aria-hidden="true" />

        <div v-show="abierto" class="anclaje-panel-govco" :style="estiloAnclaje">
          <div :id="idPanel" ref="panel" class="dropdown-menu dropdown-menu-candy-box-govco">
            <h6 class="dropdown-title dropdown-title-govco">{{ titulo }}</h6>
            <ul class="dropdown-item-menu-ul">
              <li
                v-for="(aplicacion, indice) in aplicaciones"
                :key="aplicacion.id"
                class="dropdown-item-menu-li"
              >
                <component
                  :is="etiquetaItem(aplicacion)"
                  :ref="registrarItemEn(indice)"
                  v-bind="atributosItem(aplicacion)"
                  class="dropdown-item dropdown-item-govco"
                  :class="{ 'active-select': activaId === aplicacion.id, disabled: aplicacion.deshabilitada }"
                  @focus="indiceFoco = indice"
                  @click="alActivar(aplicacion)"
                >
                  <span class="item-active-icon-background-govco" aria-hidden="true" />
                  <span class="item-active-icon-govco" aria-hidden="true" />
                  <span class="dropdown-item-icon-govco">
                    <span :class="claseIcono(aplicacion.icono)" aria-hidden="true" />
                  </span>
                  <span>{{ aplicacion.nombre }}</span>
                </component>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Referencia para colgar la bandeja del disparador. */
.contenedor-galeria-govco {
  position: relative;
  display: inline-block;
}

/*
 * El Kit coloca la bandeja con un `inset` y un `transform` que sólo tienen sentido
 * con la posición absoluta que le da Bootstrap, y la alinea contra el borde derecho
 * del disparador: si el botón no va pegado al borde de la página, la bandeja se
 * escapa 18rem hacia la izquierda. Aquí se ancla en una caja propia, centrada
 * respecto al botón —la colocación que muestra el ejemplo del Kit—, y la bandeja
 * vuelve al flujo para que ese `inset`, que el Kit marca como `!important`, no
 * tenga efecto.
 */
.anclaje-panel-govco {
  position: absolute;
  top: 0;
  left: 50%;
  z-index: 30;
  transform: translate(-50%, 2.625rem);
}

.anclaje-panel-govco .dropdown-menu-candy-box-govco {
  position: static;
  /* Bootstrap esconde los desplegables hasta que llevan `.show`; aquí la bandeja
     la enseña y la esconde el `v-show` del anclaje, así que se muestra siempre y
     el desplazamiento de 42 px que el Kit aplica con `.show` no estorba. */
  display: block;
  height: auto;
  /* El Kit fija 9,375rem de alto, que es una fila de tres; se deja crecer para que
     la cuarta aplicación no quede fuera. Su margen superior de 10 px se conserva:
     es la separación con el botón. */
  min-height: 9.375rem;
  max-width: calc(100vw - 2rem);
}

/* Tres columnas, como el Kit, pero con tantas filas como hagan falta. */
.dropdown-item-menu-ul {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  justify-content: initial;
}

.dropdown-item-menu-li {
  width: 100%;
}

/* Bootstrap estiliza `.dropdown-item` para enlaces; para el botón, lo único que
   falta es el puntero, porque los botones no lo traen de serie. */
button.dropdown-item-govco {
  cursor: pointer;
}

button.dropdown-item-govco:disabled {
  cursor: default;
}
</style>
