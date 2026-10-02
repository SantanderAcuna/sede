<script setup lang="ts">
/**
 * Carrusel (Kit UI, componente general 2).
 *
 * Portado de `examples/general/carrusel.html`, pero no es una copia del ejemplo:
 * el carrusel del Kit es una piel sobre el carrusel de Bootstrap 5, y el ejemplo
 * lo acciona con `data-bs-ride`, `data-bs-slide` y `data-bs-interval`. El CSS de
 * Bootstrap sí está cargado —el Kit no trae ni una de sus clases base—, pero su
 * **JavaScript no**, y no debe estarlo: se autoinicializa sobre selectores que en
 * nuestras páginas no existen. Por eso el comportamiento no viene de ahí.
 *
 * El comportamiento que el Kit reparte entre `script.js` y `carrusel.js` vive en
 * estado reactivo de Vue:
 *
 *  - `initCarrusel()` → el índice activo, el ciclo automático y, en lugar de
 *    `startStopCarousel()` con sus dos botones hermanos y `nextElementSibling`,
 *    un único botón de reproducir/pausar.
 *  - El `slid.bs.carousel` que reescribía `tabindex` en cada enlace → innecesario:
 *    las diapositivas inactivas están ocultas, así que no reciben el foco.
 *  - `windowSizeCarrusel()` → la clase `responsive-carrusel-govco` a partir del
 *    ancho real del componente, con el mismo corte de 652 px que usa el Kit.
 *
 * Tres apartamientos deliberados del Kit, por accesibilidad:
 *
 *  - **Los controles van en una barra propia, fuera de la imagen** (CAG-01). El
 *    Kit los superpone a la fotografía: flechas al 40 % de alto, indicadores al
 *    pie, pausa abajo a la izquierda, todo en blanco. Sobre una fotografía
 *    cualquiera eso no garantiza ni contraste ni ausencia de solape.
 *  - **Todo lo pulsable es blanco sobre azul cobalto (#0943B5): 8,46:1** (CAG-03).
 *    El Kit deja el hover de las flechas y del botón en negro, que sobre cobalto
 *    son 2,48:1; aquí el hover se marca con un fondo blanco translúcido (5,5:1) y
 *    el foco con un contorno blanco de 3 px. Los indicadores inactivos del Kit
 *    van en blanco al 60 % (3,9:1); aquí van huecos a opacidad plena (8,46:1) y el
 *    activo va relleno, de modo que distinguirlos no depende sólo del color.
 *    También se neutraliza lo que Bootstrap pone para superponer los controles a
 *    la imagen: las flechas nacen con `opacity: .5` y el pie con posicionamiento
 *    absoluto y márgenes del 15 %.
 *  - La leyenda usa una clase propia en vez de `carousel-caption`: las reglas del
 *    Kit presionan sobre el `position: absolute` de Bootstrap y una de ellas, a
 *    exactamente 1280 px de ancho, recoloca el bloque con `transform`.
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
// `NuxtLink` se importa para poder elegirlo dinámicamente con `<component :is>`:
// el destino de una diapositiva puede ser una ruta del sitio o una URL externa.
import { NuxtLink } from '#components'

export interface DiapositivaCarrusel {
  /** Identificador estable: es la `:key` de la lista. */
  id: string | number
  imagen: string
  /**
   * Texto alternativo (CAG-04). Obligatorio en el tipo a propósito: una
   * fotografía de portada siempre aporta información. Si la imagen fuera
   * decorativa de verdad, el sitio correcto es una cadena vacía explícita.
   */
  alt: string
  titulo?: string
  descripcion?: string
  enlace?: string
}

interface PropsCarrusel {
  diapositivas?: DiapositivaCarrusel[]
  /** La reproducción automática nunca arranca si el sistema pide reducir movimiento. */
  autoplay?: boolean
  /** Milisegundos entre diapositivas. El ejemplo del Kit usaba 2000: da tiempo a ver, no a leer. */
  intervalo?: number
  /** Nombre de la región para lectores de pantalla. */
  etiqueta?: string
}

/** El mismo corte que usa `windowSizeCarrusel()` del Kit. */
const UMBRAL_ESTRECHO = 652

/**
 * Un destino es interno si es una ruta del sitio. `//` se excluye porque una
 * «ruta» que empieza por doble barra es en realidad una URL de otro dominio.
 */
function esEnlaceInterno(destino: string | undefined): boolean {
  if (!destino) return false
  return destino.startsWith('/') && !destino.startsWith('//')
}

const props = withDefaults(defineProps<PropsCarrusel>(), {
  // Diapositivas de ejemplo —las imágenes son los marcadores del propio Kit— para
  // que el componente se pueda ver solo. El contenido real llega por props.
  //
  // Va en línea, y no en una constante del archivo, porque `defineProps()` se
  // compila fuera de `setup()` y no puede referenciar variables locales.
  diapositivas: () => [
    {
      id: 'ejemplo-1',
      imagen: '/govco/assets/images/fondo-gris-carrusel.jpg',
      alt: 'Imagen de ejemplo 1 (sustituir por su descripción real)',
      titulo: 'Diapositiva de ejemplo',
      descripcion: 'Texto de ejemplo. El contenido real llega por props.',
    },
    {
      id: 'ejemplo-2',
      imagen: '/govco/assets/images/fondo-gris-carrusel.jpg',
      alt: 'Imagen de ejemplo 2 (sustituir por su descripción real)',
      titulo: 'Segunda diapositiva de ejemplo',
    },
    {
      id: 'ejemplo-3',
      imagen: '/govco/assets/images/fondo-gris-carrusel.jpg',
      alt: 'Imagen de ejemplo 3 (sustituir por su descripción real)',
    },
  ],
  // Por defecto, el carrusel comienza PAUSADO. El criterio RF-B1-042 exige que
  // los controles incluyan Play/Stop y que la pausa sea el estado inicial; el
  // criterio RF-B1-051 (WCAG) refuerza que el movimiento automático respeta la
  // preferencia del sistema (prefers-reduced-motion). Arrancar reproduciendo sin
  // que el usuario lo pida explícitamenteincumple ambos.
  autoplay: false,
  intervalo: 6000,
  etiqueta: 'Carrusel de imágenes destacadas',
})

const activa = ref(0)
const estrecho = ref(false)
const reproduciendo = ref(false)
const raiz = ref<HTMLElement | null>(null)

const total = computed(() => props.diapositivas.length)

let temporizador: ReturnType<typeof setInterval> | null = null
let observador: ResizeObserver | null = null
let medirAncho: (() => void) | null = null
let consultaMovimiento: MediaQueryList | null = null

if (import.meta.dev) {
  // CAG-04: el tipo ya obliga a declarar `alt`, pero una cadena vacía pasaría el
  // tipo sin decir nada. En producción este aviso no existe.
  for (const diapositiva of props.diapositivas) {
    if (diapositiva.alt.trim() === '') {
      console.warn(`[CarruselGovco] La diapositiva «${diapositiva.id}» no tiene texto alternativo (CAG-04).`)
    }
  }
}

function detenerTemporizador(): void {
  if (temporizador !== null) {
    clearInterval(temporizador)
    temporizador = null
  }
}

function arrancarTemporizador(): void {
  detenerTemporizador()
  // Con una sola diapositiva no hay nada que rotar.
  if (total.value < 2) return
  temporizador = setInterval(siguiente, Math.max(1000, props.intervalo))
}

function irA(indice: number): void {
  if (indice === activa.value) return
  activa.value = indice
  // Un salto manual reinicia la cuenta: si no, la rotación podría adelantarse
  // justo después de que la persona eligiera diapositiva.
  if (reproduciendo.value) arrancarTemporizador()
}

function siguiente(): void {
  if (total.value === 0) return
  irA((activa.value + 1) % total.value)
}

function anterior(): void {
  if (total.value === 0) return
  irA((activa.value - 1 + total.value) % total.value)
}

function alternarReproduccion(): void {
  reproduciendo.value = !reproduciendo.value
}

function alCambiarMovimiento(evento: MediaQueryListEvent): void {
  // Si la preferencia se activa con la página abierta, se detiene en el acto.
  if (evento.matches) reproduciendo.value = false
}

watch(reproduciendo, (enMarcha) => {
  if (enMarcha) arrancarTemporizador()
  else detenerTemporizador()
})

watch(total, () => {
  // Las diapositivas pueden llegar después, de una API: el índice guardado tiene
  // que seguir siendo válido y la rotación reajustarse al nuevo número.
  if (activa.value >= total.value) activa.value = 0
  if (reproduciendo.value) arrancarTemporizador()
})

onMounted(() => {
  const elemento = raiz.value
  if (elemento !== null) {
    medirAncho = () => {
      estrecho.value = elemento.offsetWidth < UMBRAL_ESTRECHO
    }
    medirAncho()
    // Se mide el contenedor y no sólo la ventana: dentro de una rejilla el
    // carrusel puede encogerse sin que la ventana cambie de tamaño.
    if (typeof ResizeObserver !== 'undefined') {
      observador = new ResizeObserver(medirAncho)
      observador.observe(elemento)
    } else {
      window.addEventListener('resize', medirAncho)
    }
  }

  // `prefers-reduced-motion`: quien pidió reducir movimiento no debe encontrarse
  // contenido que se mueve solo. La reproducción automática no arranca; el botón
  // sigue ahí y pulsarlo es una petición explícita, que sí se respeta.
  consultaMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)')
  consultaMovimiento.addEventListener('change', alCambiarMovimiento)
  reproduciendo.value = props.autoplay && !consultaMovimiento.matches
})

onBeforeUnmount(() => {
  detenerTemporizador()
  observador?.disconnect()
  if (medirAncho !== null) window.removeEventListener('resize', medirAncho)
  consultaMovimiento?.removeEventListener('change', alCambiarMovimiento)
})
</script>

<template>
  <section
    ref="raiz"
    class="carrusel-govco"
    :class="{ 'responsive-carrusel-govco': estrecho }"
    role="region"
    aria-roledescription="carrusel"
    :aria-label="etiqueta"
    @keydown.left.prevent="anterior"
    @keydown.right.prevent="siguiente"
  >
    <!-- Al rotar en automático no se anuncia cada diapositiva (`off`); al parar,
         sí, porque entonces el cambio lo pidió la persona y quiere oírlo. -->
    <div class="carousel-inner" :aria-live="reproduciendo ? 'off' : 'polite'">
      <div
        v-for="(diapositiva, indice) in diapositivas"
        v-show="indice === activa"
        :key="diapositiva.id"
        class="carousel-item"
        :class="{ active: indice === activa }"
        role="group"
        aria-roledescription="diapositiva"
        :aria-label="`${indice + 1} de ${total}`"
      >
        <!--
          La imagen enlaza con `NuxtLink` cuando el destino es una ruta del sitio
          y con `<a>` cuando es una URL externa. Un `<a href="/algo">` interno
          descarga la aplicación entera en lugar de navegar dentro de ella, y en
          una sede con formularios a medio diligenciar eso se nota (D-22).
        -->
        <component
          :is="esEnlaceInterno(diapositiva.enlace) ? NuxtLink : 'a'"
          v-if="diapositiva.enlace"
          v-bind="
            esEnlaceInterno(diapositiva.enlace)
              ? { to: diapositiva.enlace }
              : { href: diapositiva.enlace, target: '_blank', rel: 'noopener noreferrer' }
          "
        >
          <img :src="diapositiva.imagen" :alt="diapositiva.alt">
        </component>
        <img v-else :src="diapositiva.imagen" :alt="diapositiva.alt">

        <div v-if="diapositiva.titulo || diapositiva.descripcion" class="leyenda-carrusel">
          <h5 v-if="diapositiva.titulo">{{ diapositiva.titulo }}</h5>
          <p v-if="diapositiva.descripcion">{{ diapositiva.descripcion }}</p>
        </div>
      </div>
    </div>

    <div class="barra-controles-carrusel">
      <button type="button" class="carousel-control-prev" aria-label="Diapositiva anterior" @click="anterior">
        <span class="carousel-control-prev-icon" aria-hidden="true" />
      </button>

      <div class="carousel-indicators">
        <button
          v-for="(diapositiva, indice) in diapositivas"
          :key="diapositiva.id"
          type="button"
          :class="{ active: indice === activa }"
          :aria-current="indice === activa ? 'true' : undefined"
          :aria-label="`Ir a la diapositiva ${indice + 1} de ${total}`"
          @click="irA(indice)"
        />
      </div>

      <!-- Se conserva el envoltorio `control-start-pause` para heredar del Kit el
           icono y el subrayado del texto. El Kit escondía el botón que no estaba
           en `.active` porque tenía dos; con uno solo se marca siempre activo. -->
      <div class="control-start-pause">
        <button
          type="button"
          class="active"
          :class="reproduciendo ? 'pause' : 'start'"
          :aria-label="reproduciendo ? 'Pausar la reproducción automática' : 'Reanudar la reproducción automática'"
          @click="alternarReproduccion"
        >
          <span>{{ reproduciendo ? 'Pausar' : 'Reproducir' }}</span>
        </button>
      </div>

      <button type="button" class="carousel-control-next" aria-label="Diapositiva siguiente" @click="siguiente">
        <span class="carousel-control-next-icon" aria-hidden="true" />
      </button>
    </div>
  </section>
</template>

<style scoped>
.carrusel-govco {
  position: relative;
}

.carousel-item {
  position: relative;
}

/* Altura fija para que la portada no dé un salto de tamaño al pasar de una
   fotografía apaisada a otra vertical; `cover` recorta, no deforma. */
.carousel-item img {
  display: block;
  height: 22rem;
  object-fit: cover;
}

.responsive-carrusel-govco .carousel-item img {
  /* En estrecho manda el mínimo del Kit (13,5rem) y el alto se ajusta a él. */
  height: 13.5rem;
}

.leyenda-carrusel {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  max-width: min(23.063rem, 80%);
  padding: 1rem 1.25rem;
  border-radius: 0.625rem;
  /* Placa opaca, no un velo translúcido: así el 8,46:1 del blanco sobre cobalto
     no depende de cómo salga la fotografía que hay debajo. */
  background-color: #0943b5;
  text-align: center;
}

.leyenda-carrusel h5 {
  margin: 0 0 0.5rem;
  color: #ffffff;
  font-size: 1.5rem;
}

.leyenda-carrusel p {
  margin: 0;
  color: #ffffff;
  font-size: 1rem;
  line-height: 1.5rem;
}

/* En estrecho la leyenda deja de flotar y fluye bajo la imagen: superpuesta a una
   caja de poco ancho, el texto se recorta. */
.responsive-carrusel-govco .leyenda-carrusel {
  position: static;
  transform: none;
  max-width: 100%;
  border-radius: 0;
}

.barra-controles-carrusel {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background-color: #0943b5;
}

/* El Kit superpone las flechas a la imagen (`top: 40%; bottom: 40%`) y les da un
   `width: 25%` pensado para esa posición; Bootstrap, además, las deja al 50 % de
   opacidad y con los márgenes del pie al 15 %. Dentro de la barra sobra todo eso,
   y la opacidad hay que devolverla a 1 o el blanco se queda en 3,9:1. */
/*
 * **La barra en pantallas estrechas: rejilla, no fila que se envuelve.**
 *
 * Tenía `flex-wrap: wrap` con `justify-content: space-between`, y eso funciona
 * mientras quepa. Medido a 320 px, los cuatro controles —flecha, puntos,
 * «Reproducir» y flecha— suman unos 350 px en una barra de 320, así que **se
 * envolvían sin criterio**: la barra pasaba de 72 px de alto a **136**, la
 * flecha «anterior» quedaba en una línea y la «siguiente» en otra, **64 px más
 * abajo**, montándose sobre «Reproducir». A 390 px caben y se veían alineadas,
 * que es por lo que el defecto sólo aparecía en móviles angostos.
 *
 * Una rejilla con áreas fijas resuelve la causa y no el síntoma: **las dos
 * flechas quedan siempre en la misma fila**, ancladas a los extremos, y
 * «Reproducir» baja a una segunda línea cuando no cabe. Deja de depender de si
 * el contenido entra por pocos píxeles.
 */
@media (max-width: 575px) {
  .barra-controles-carrusel {
    display: grid;
    grid-template-columns: auto 1fr auto;
    grid-template-areas:
      'anterior puntos siguiente'
      'reproducir reproducir reproducir';
    row-gap: 0.25rem;
    column-gap: 0.5rem;
    align-items: center;
  }

  .barra-controles-carrusel .carousel-control-prev { grid-area: anterior; justify-self: start; }
  .barra-controles-carrusel .carousel-control-next { grid-area: siguiente; justify-self: end; }

  /* Los puntos se centran en el hueco que queda entre las dos flechas, así que
     siguen quedando en el eje del carrusel y no desplazados a un lado. */
  .barra-controles-carrusel .carousel-indicators {
    grid-area: puntos;
    justify-self: center;
    margin: 0;
  }

  .barra-controles-carrusel .control-start-pause {
    grid-area: reproducir;
    justify-self: start;
  }
}

.carrusel-govco .carousel-control-prev,
.carrusel-govco .carousel-control-next {
  position: static;
  top: auto;
  bottom: auto;
  width: auto;
  padding: 0.25rem 0.5rem;
  border: 0;
  border-radius: 0.25rem;
  background-color: transparent;
  opacity: 1;
  cursor: pointer;
}

.carrusel-govco .carousel-control-prev:hover,
.carrusel-govco .carousel-control-next:hover {
  opacity: 1;
  background-color: rgb(255 255 255 / 18%);
}

/* El Kit pinta en negro el icono de las flechas al pasar el ratón o al enfocar
   (2,48:1 sobre cobalto). Se mantiene en blanco y el foco se marca con contorno. */
.carrusel-govco .carousel-control-prev:hover .carousel-control-prev-icon::after,
.carrusel-govco .carousel-control-next:hover .carousel-control-next-icon::after,
.carrusel-govco .carousel-control-prev:focus-visible .carousel-control-prev-icon::after,
.carrusel-govco .carousel-control-next:focus-visible .carousel-control-next-icon::after {
  color: #ffffff;
  border: 0;
}

.carrusel-govco .carousel-control-prev:focus-visible,
.carrusel-govco .carousel-control-next:focus-visible {
  outline: 0.188rem solid #ffffff;
  outline-offset: 0.125rem;
}

/* El Kit centra los indicadores sobre la imagen, con un 20 % de margen a cada
   lado; dentro de la barra la métrica es la del contenedor flexible. */
.carrusel-govco .carousel-indicators {
  position: static;
  display: flex;
  flex: 0 0 auto;
  margin: 0;
}

.carrusel-govco .carousel-indicators button {
  width: 1rem;
  height: 1rem;
  /* 1rem de punto más 0,35rem de margen deja 27 px entre centros: el área de
     pulsación de un indicador no invade la del vecino. */
  margin: 0.35rem;
  padding: 0;
  border: 0.125rem solid #ffffff;
  border-radius: 50%;
  cursor: pointer;
}

.carrusel-govco .carousel-indicators button:not(.active) {
  opacity: 1;
  background-color: transparent;
}

.carrusel-govco .carousel-indicators button.active {
  opacity: 1;
  background-color: #ffffff;
}

.carrusel-govco .carousel-indicators button:focus-visible {
  outline: 0.188rem solid #ffffff;
  outline-offset: 0.125rem;
}

/* El botón de pausa del Kit va pegado al borde inferior izquierdo de la imagen. */
.carrusel-govco .control-start-pause {
  position: static;
  flex: 0 0 auto;
  margin: 0;
}

.carrusel-govco .control-start-pause button {
  border-radius: 0.25rem;
  background-color: transparent;
  cursor: pointer;
}

.carrusel-govco .control-start-pause button:hover {
  color: #ffffff;
  background-color: rgb(255 255 255 / 18%);
}

.carrusel-govco .control-start-pause button:focus-visible {
  color: #ffffff;
  border-radius: 0.25rem;
  outline: 0.188rem solid #ffffff;
  outline-offset: 0.125rem;
}
</style>
