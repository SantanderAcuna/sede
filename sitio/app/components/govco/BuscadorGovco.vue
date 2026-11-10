<script setup lang="ts">
/**
 * Buscador (Kit UI, componente general 3).
 *
 * Portado de `examples/general/buscador.html`, en su variante básica, con una
 * mejora opcional: las **sugerencias mientras se escribe** (`conSugerencias`), que
 * el Kit trae como buscador predictivo pero sin ningún servicio detrás. Aquí las
 * sirve `GET /busqueda/sugerencias` (T-22) y su interacción está fijada en
 * `docs/busqueda-interaccion.md` §4, que es la fuente de cada decisión de abajo.
 *
 * Sin `conSugerencias` el componente no busca: **sólo recoge el término y lo
 * emite**. La búsqueda real la decide la página que lo use. Con ellas, el
 * formulario además lleva `action="/buscar" method="get"` y el campo
 * `name="q"`: si el JavaScript no llega, el envío nativo abre la página de
 * resultados y las sugerencias son una mejora, no un requisito (§4.2).
 *
 * Lo que el Kit resolvía con `inicializarBuscador()` → `InitSearchDefault()` está
 * aquí en estado reactivo. Aquel código consultaba el DOM en cada pulsación de
 * tecla para mostrar u ocultar la línea y el botón de limpiar, y para devolver el
 * foco al campo cuando quedaba vacío; aquí eso es una `computed` (`hayTexto`) y un
 * atributo de clase, sin tocar el DOM.
 *
 * CAG-15: el campo tiene un nombre claro —etiqueta para lectores de pantalla y
 * `placeholder` a la vista—, se puede vaciar con el botón de limpiar o con `Esc`,
 * y se recorre con el teclado: el campo, el botón de limpiar y el de buscar son
 * elementos nativos, en ese orden, dentro de un `<form>`, de modo que `Enter`
 * envía sin necesidad de ninguna tecla capturada a mano.
 *
 * Combobox (patrón «List Autocomplete with Manual Selection» de la WAI-ARIA APG):
 * el foco **no sale nunca del campo**; la opción activa se indica con
 * `aria-activedescendant`. La lista se ofrece a los lectores de pantalla como
 * `listbox`; los mensajes («No hay sugerencias…») no son opciones y van fuera de
 * ella, porque un `listbox` sólo puede contener `option`.
 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'

import {
  esRutaInterna,
  MAXIMO_CARACTERES,
  MINIMO_CARACTERES,
  normalizarConsulta,
  useSugerenciasBusqueda,
  type PedirSugerencias,
} from '~/composables/useSugerenciasBusqueda'

interface PropsBuscador {
  /** Nombre del campo. Es invisible, pero es el que anuncia el lector de pantalla. */
  etiqueta?: string
  placeholder?: string
  /** Texto del botón de envío, que va sólo con icono. */
  textoBoton?: string
  textoLimpiar?: string
  valorInicial?: string
  /**
   * Enseña sugerencias al escribir y envía el formulario a `/buscar` aunque falle
   * el JavaScript. Sólo el buscador general de la cabecera lo activa: el del
   * catálogo filtra su propia lista y no debe llevar a otra página.
   */
  conSugerencias?: boolean
  /** Sólo para las pruebas: sustituye la petición real a la API. */
  pedirSugerencias?: PedirSugerencias
}

const props = withDefaults(defineProps<PropsBuscador>(), {
  etiqueta: 'Buscar en el sitio',
  placeholder: 'Buscar trámites, servicios y noticias',
  textoBoton: 'Buscar',
  textoLimpiar: 'Limpiar la búsqueda',
  valorInicial: '',
  conSugerencias: false,
  pedirSugerencias: undefined,
})

const emit = defineEmits<{
  buscar: [termino: string]
  limpiar: []
  /** Se eligió una sugerencia: `url` es una ruta interna del sitio ya comprobada. */
  seleccionar: [url: string]
}>()

/**
 * Textos de §7 de la especificación, en un solo sitio para que Jurídica y
 * comunicaciones los revisen. La redacción no la ha revisado nadie de la Entidad.
 */
const TEXTOS = {
  ayuda: 'Escriba al menos 3 letras para ver sugerencias.',
  lista: 'Sugerencias de búsqueda',
  buscando: 'Buscando sugerencias.',
  sinSugerencias: 'No hay sugerencias. Pulse Intro para buscar en toda la sede.',
  error: 'No pudimos consultar las sugerencias. Pulse Intro para buscar.',
  unaSugerencia: '1 sugerencia disponible.',
  variasSugerencias: (n: number) => `${n} sugerencias disponibles.`,
  limitado: (n: number) =>
    `Hizo muchas búsquedas seguidas. Espere ${n} ${n === 1 ? 'segundo' : 'segundos'} para ver sugerencias; aun así puede pulsar Intro.`,
}

const termino = ref(props.valorInicial)
const activo = ref(false)
const contenedor = ref<HTMLElement | null>(null)
const formulario = ref<HTMLFormElement | null>(null)
const campo = ref<HTMLInputElement | null>(null)

// Identificador único por instancia: en una portada puede haber más de un
// buscador y la etiqueta tiene que apuntar al suyo, también en el HTML del servidor.
const idCampo = useId()
const idLista = `${idCampo}-lista`
const idAyuda = `${idCampo}-ayuda`
const idOpcion = (indice: number): string => `${idCampo}-op-${indice}`

const hayTexto = computed(() => termino.value !== '')

// ---------------------------------------------------------------------------
// Sugerencias (§4)
// ---------------------------------------------------------------------------

const motor = useSugerenciasBusqueda({ pedir: props.pedirSugerencias })

/** La persona cerró la lista (Esc, Tab, envío, elegir). Se reabre al escribir o con ↓. */
const cerradaPorLaPersona = ref(false)
/** Posición de la opción activa; −1 es «el campo», sin ninguna activa. */
const indiceActivo = ref(-1)
/** Hay un puntero apretado sobre la lista: perder el foco ahora no debe cerrarla (§4.5, 7). */
const punteroEnLista = ref(false)

const consultaValida = computed(() => normalizarConsulta(termino.value).length >= MINIMO_CARACTERES)

/** ¿Hay algo que enseñar? Una lista, o uno de los tres mensajes. */
const hayContenido = computed(() => {
  switch (motor.estado.value) {
    case 'listo':
      return motor.sugerencias.value.length > 0
    case 'inactivo':
      return false
    default:
      return true
  }
})

const abierta = computed(
  () =>
    props.conSugerencias &&
    activo.value &&
    !cerradaPorLaPersona.value &&
    consultaValida.value &&
    hayContenido.value,
)

const mostrarLista = computed(() => abierta.value && motor.estado.value === 'listo')

const mensaje = computed(() => {
  if (!abierta.value) return ''
  switch (motor.estado.value) {
    case 'sin-resultados':
      return TEXTOS.sinSugerencias
    case 'error':
      return TEXTOS.error
    case 'limitado':
      return TEXTOS.limitado(motor.esperaSegundos.value)
    default:
      return ''
  }
})

/**
 * Lo que se anuncia a los lectores de pantalla (`role="status"`). Nada al teclear;
 * sólo cuando hay una lista o un mensaje abiertos. «Buscando» aparece si pasan
 * 200 ms sin respuesta, y el 429 se anuncia una vez, sin cuenta atrás.
 */
const anuncio = computed(() => {
  if (!props.conSugerencias || !activo.value || cerradaPorLaPersona.value) return ''
  if (motor.cargandoVisible.value) return TEXTOS.buscando
  if (!abierta.value) return ''
  if (mensaje.value !== '') return mensaje.value
  const n = motor.sugerencias.value.length
  return n === 1 ? TEXTOS.unaSugerencia : TEXTOS.variasSugerencias(n)
})

const descendienteActivo = computed(() =>
  mostrarLista.value && indiceActivo.value >= 0 ? idOpcion(indiceActivo.value) : undefined,
)

// Una lista nueva borra la opción activa: apuntaba a otra cosa.
watch(motor.sugerencias, () => {
  indiceActivo.value = -1
})

function cerrarLista(): void {
  cerradaPorLaPersona.value = true
  indiceActivo.value = -1
}

// ---------------------------------------------------------------------------
// Alto máximo de la lista: nunca más que el área visible (§4.6). `visualViewport`
// descuenta el teclado en pantalla de los móviles, que `innerHeight` no hace.
// ---------------------------------------------------------------------------

const alturaMaxima = ref<number | null>(null)

function medirAltura(): void {
  const caja = formulario.value?.getBoundingClientRect()
  if (!caja || typeof window === 'undefined') return
  const visible = window.visualViewport
  const borde = visible ? visible.offsetTop + visible.height : window.innerHeight
  // Al menos dos filas, para que la lista siga siendo utilizable en pantallas muy bajas.
  alturaMaxima.value = Math.max(Math.floor(borde - caja.bottom - 8), 96)
}

function alCambiarVisor(): void {
  if (abierta.value) medirAltura()
}

onMounted(() => {
  window.visualViewport?.addEventListener('resize', alCambiarVisor)
  window.visualViewport?.addEventListener('scroll', alCambiarVisor)
  window.addEventListener('resize', alCambiarVisor)
})

onBeforeUnmount(() => {
  window.visualViewport?.removeEventListener('resize', alCambiarVisor)
  window.visualViewport?.removeEventListener('scroll', alCambiarVisor)
  window.removeEventListener('resize', alCambiarVisor)
})

watch(abierta, async (ahoraAbierta) => {
  if (!ahoraAbierta) return
  await nextTick()
  medirAltura()
})

// ---------------------------------------------------------------------------
// Envío, limpieza y teclado
// ---------------------------------------------------------------------------

function buscar(): void {
  const limpio = termino.value.trim()
  // En blanco no es una búsqueda: se devuelve el foco al campo en vez de emitir
  // un evento que la página no sabría qué hacer con él.
  if (limpio === '') {
    campo.value?.focus()
    return
  }
  // Una respuesta que llegue ahora no debe abrir una lista sobre la página nueva.
  motor.cancelar()
  cerrarLista()
  emit('buscar', limpio)
}

function limpiar(): void {
  termino.value = ''
  motor.reiniciar()
  indiceActivo.value = -1
  emit('limpiar')
  // El Kit también devuelve el foco al campo al limpiar.
  campo.value?.focus()
}

function alEscribir(evento: Event): void {
  termino.value = (evento.target as HTMLInputElement).value
  if (!props.conSugerencias) return
  cerradaPorLaPersona.value = false
  indiceActivo.value = -1
  motor.escribir(termino.value)
}

function alEmpezarComposicion(): void {
  motor.empezarComposicion()
}

function alTerminarComposicion(evento: CompositionEvent): void {
  termino.value = (evento.target as HTMLInputElement).value
  cerradaPorLaPersona.value = false
  motor.terminarComposicion(termino.value)
}

/** ↓ con la lista cerrada: la abre y pregunta ya, sin esperar la pausa (§4.3). */
function abrirLista(): void {
  if (!consultaValida.value) return
  cerradaPorLaPersona.value = false
  indiceActivo.value = -1
  motor.pedirYa(termino.value)
}

function moverActivo(paso: 1 | -1): void {
  const total = motor.sugerencias.value.length
  if (!mostrarLista.value || total === 0) return
  // El recorrido pasa por el campo entre la última opción y la primera (§4.3). ↑
  // desde el campo salta a la última: es la simetría de ese recorrido, que la
  // especificación no escribe.
  const siguiente = indiceActivo.value + paso
  indiceActivo.value = siguiente >= total ? -1 : siguiente < -1 ? total - 1 : siguiente
}

function elegir(indice: number): void {
  const elegida = motor.sugerencias.value[indice]
  if (!elegida) return
  // La API sólo manda rutas internas y el composable ya descarta las demás; se
  // vuelve a comprobar aquí porque este es el punto donde se navega.
  if (!esRutaInterna(elegida.url)) return
  motor.cancelar()
  cerrarLista()
  emit('seleccionar', elegida.url)
}

function alPulsarTecla(evento: KeyboardEvent): void {
  // Una tecla pulsada para componer (un acento, un carácter de IME) no es una orden.
  if (evento.isComposing) return

  if (!props.conSugerencias) {
    if (evento.key === 'Escape' && hayTexto.value) {
      evento.preventDefault()
      limpiar()
    }
    return
  }

  switch (evento.key) {
    case 'ArrowDown':
      evento.preventDefault()
      if (!abierta.value) abrirLista()
      else if (!evento.altKey) moverActivo(1)
      break
    case 'ArrowUp':
      if (abierta.value) {
        evento.preventDefault()
        moverActivo(-1)
      }
      break
    case 'Escape':
      evento.preventDefault()
      // Primer Esc cierra la lista y deja el texto; el segundo, o el primero con
      // la lista cerrada, lo borra.
      if (abierta.value) cerrarLista()
      else if (hayTexto.value) limpiar()
      break
    case 'Enter':
      // Con una opción activa va a su página; sin ella, el formulario se envía.
      if (mostrarLista.value && indiceActivo.value >= 0) {
        evento.preventDefault()
        elegir(indiceActivo.value)
      }
      break
    case 'Tab':
      cerrarLista()
      break
    case 'ArrowLeft':
    case 'ArrowRight':
    case 'Home':
    case 'End':
      indiceActivo.value = -1
      break
  }
}

function alEntrarFoco(): void {
  activo.value = true
}

function alSalirFoco(evento: FocusEvent): void {
  // El contorno de foco del Kit rodea todo el contenedor, no el campo suelto: hay
  // que distinguir «se fue a otro control del buscador» de «se fue del buscador».
  const destino = evento.relatedTarget
  if (destino instanceof Node && contenedor.value?.contains(destino)) return
  // Un clic sobre una opción quita el foco antes de que llegue el `click`: si la
  // lista se cerrara aquí, la opción desaparecería y el clic se perdería.
  if (punteroEnLista.value) return
  activo.value = false
  indiceActivo.value = -1
}

function alApretarLista(): void {
  punteroEnLista.value = true
}

function alSoltarLista(): void {
  punteroEnLista.value = false
  // Si el foco se había ido, el cierre que se aplazó se aplica ahora.
  if (!formulario.value?.contains(document.activeElement)) activo.value = false
}
</script>

<template>
  <form
    ref="formulario"
    class="govco-search-basic"
    :class="{ 'con-sugerencias': conSugerencias }"
    role="search"
    :aria-label="etiqueta"
    :action="conSugerencias ? '/buscar' : undefined"
    :method="conSugerencias ? 'get' : undefined"
    @submit.prevent="buscar"
  >
    <div
      ref="contenedor"
      class="container-govco"
      :class="{ active: activo }"
      @focusin="alEntrarFoco"
      @focusout="alSalirFoco"
    >
      <label :for="idCampo" class="etiqueta-buscador-govco">{{ etiqueta }}</label>
      <input
        :id="idCampo"
        ref="campo"
        class="input-search-basic-govco"
        type="text"
        autocomplete="off"
        :value="termino"
        :name="conSugerencias ? 'q' : undefined"
        :maxlength="conSugerencias ? MAXIMO_CARACTERES : undefined"
        :enterkeyhint="conSugerencias ? 'search' : undefined"
        :role="conSugerencias ? 'combobox' : undefined"
        :aria-autocomplete="conSugerencias ? 'list' : undefined"
        :aria-expanded="conSugerencias ? abierta : undefined"
        :aria-controls="conSugerencias ? idLista : undefined"
        :aria-activedescendant="descendienteActivo"
        :aria-describedby="conSugerencias ? idAyuda : undefined"
        :placeholder="placeholder"
        @input="alEscribir"
        @compositionstart="alEmpezarComposicion"
        @compositionend="alTerminarComposicion"
        @keydown="alPulsarTecla"
      >

      <!-- Visible sólo cuando hay algo que borrar: así tampoco es una parada de
           tabulador de más. -->
      <button
        type="button"
        class="btn-clean-basic-govco"
        :class="{ active: hayTexto }"
        :aria-label="textoLimpiar"
        @click="limpiar"
      >
        <span class="govco-svg govco-times" aria-hidden="true" />
      </button>
      <div class="line-basic-govco" :class="{ active: hayTexto }" />

      <!-- Aparece si la respuesta tarda más de 200 ms; la lista previa se conserva
           debajo y el anuncio «Buscando sugerencias.» lo da la región de estado. -->
      <span v-if="conSugerencias && motor.cargandoVisible.value" class="indicador-carga-buscador" aria-hidden="true" />

      <button type="submit" class="btn-search-basic-govco" :aria-label="textoBoton">
        <span class="govco-svg govco-search" aria-hidden="true" />
      </button>
    </div>

    <template v-if="conSugerencias">
      <span :id="idAyuda" class="etiqueta-buscador-govco">{{ TEXTOS.ayuda }}</span>

      <!-- `mousedown.prevent` mantiene el foco en el campo al pulsar la lista: sin
           él, el clic en una opción primero cerraría la lista y se perdería. -->
      <div
        v-show="abierta"
        class="sugerencias-busqueda-govco"
        :style="alturaMaxima === null ? undefined : { maxHeight: `${alturaMaxima}px` }"
        @mousedown.prevent
        @pointerdown="alApretarLista"
        @pointerup="alSoltarLista"
        @pointercancel="alSoltarLista"
        @pointerleave="alSoltarLista"
      >
        <ul
          :id="idLista"
          role="listbox"
          class="lista-sugerencias-govco"
          :aria-label="TEXTOS.lista"
          :aria-busy="motor.cargandoVisible.value"
          :hidden="!mostrarLista"
        >
          <li
            v-for="(sugerencia, indice) in motor.sugerencias.value"
            :id="idOpcion(indice)"
            :key="sugerencia.id"
            role="option"
            class="opcion-sugerencia-govco"
            :class="{ activa: indice === indiceActivo }"
            :aria-selected="indice === indiceActivo ? 'true' : 'false'"
            @click="elegir(indice)"
          >
            <span class="titulo-sugerencia-govco">{{ sugerencia.titulo }}</span>
            <span class="etiqueta-buscador-govco">, </span>
            <span class="categoria-sugerencia-govco">{{ sugerencia.categoria }}</span>
          </li>
        </ul>

        <!-- No son opciones: no se eligen, se leen. -->
        <p v-if="mensaje" class="mensaje-sugerencias-govco">{{ mensaje }}</p>
      </div>

      <div class="etiqueta-buscador-govco" role="status" aria-live="polite">{{ anuncio }}</div>
    </template>
  </form>
</template>

<style scoped>
/* El Kit dimensiona el buscador con `width: -webkit-fill-available`, que sólo
   entiende WebKit: en Firefox la declaración se descarta y el ancho quedaría a
   merced del contexto. */
.govco-search-basic {
  position: relative;
  width: 100%;
}

/* El `display: flex` de los ejemplos del Kit viene de la utilidad `d-flex` de
   Bootstrap; aquí se declara en la hoja del componente para no depender de que
   esa clase esté cargada. */
.govco-search-basic .container-govco {
  display: flex;
  align-items: center;
}

/* Etiqueta sólo para lectores de pantalla: el campo se explica con el
   `placeholder`, que desaparece al escribir y no vale como nombre accesible. Se
   escribe aquí en vez de usar la utilidad `visually-hidden` de Bootstrap para no
   arrastrar sus `!important` ni depender de ella. */
.etiqueta-buscador-govco {
  position: absolute;
  overflow: hidden;
  clip-path: inset(50%);
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  border: 0;
  white-space: nowrap;
}

/**
 * Estilos del buscador - cumplimiento CC4, CC5, CC17
 *
 * CC4: Texto e imágenes ampliables hasta 200% sin deformación.
 *      Tamaño base mínimo: 16px / 12pt
 * CC5: Contraste de color suficiente (4.5:1 texto normal, 3:1 componentes UI)
 * CC17: Foco visible con contraste mínimo 3:1
 */

/* Campo de entrada - tamaño base mínimo 16px (CC4) */
.input-search-basic-govco {
  flex: 1;
  min-width: 0;
  height: 2.75rem;
  padding: 0.5rem 0.75rem;
  border: none;
  border-bottom: 0.125rem solid #767676;
  background-color: transparent;
  color: #1a1a1a;
  font-family: 'Nunito_Sans-Regular', system-ui, sans-serif;
  font-size: 1rem; /* 16px mínimo - CC4 */
  line-height: 1.5;
  outline: none;
  transition: border-color 0.2s ease;
}

/* Placeholder con contraste 4.5:1 (CC5) */
.input-search-basic-govco::placeholder {
  color: #4a4a4a;
  opacity: 1;
}

/* Foco en el campo - indicador visible (CC17) */
.input-search-basic-govco:focus {
  border-bottom-color: #00ade7;
  border-bottom-width: 0.1875rem;
}

/* Botón de limpiar */
.btn-clean-basic-govco {
  display: flex;
  align-items: center;
  justify-content: center;
  /* 44 × 44 px (criterio del proyecto; WCAG 2.5.8 pide 24). El icono sigue
     siendo de 1 rem: lo que crece es la zona que se puede pulsar. */
  width: 2.75rem;
  height: 2.75rem;
  padding: 0;
  border: none;
  background: transparent;
  color: #767676;
  cursor: pointer;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, color 0.2s ease;
}

.btn-clean-basic-govco.active {
  opacity: 1;
  visibility: visible;
}

/* Hover del botón limpiar */
.btn-clean-basic-govco:hover {
  color: #1a1a1a;
}

/* Foco visible en botón limpiar - CC17 (contraste 3:1) */
.btn-clean-basic-govco:focus-visible {
  outline: 0.125rem solid #00ade7;
  outline-offset: 0.125rem;
  border-radius: 50%;
}

/* Línea decorativa */
.line-basic-govco {
  width: 0;
  height: 0.125rem;
  background-color: #00ade7;
  transition: width 0.2s ease;
}

.line-basic-govco.active {
  width: 1.5rem;
}

/* Botón de búsqueda */
.btn-search-basic-govco {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.75rem;
  height: 2.75rem;
  padding: 0;
  border: none;
  background-color: transparent;
  color: #00ade7;
  cursor: pointer;
  transition: background-color 0.2s ease, color 0.2s ease;
}

/* Hover del botón buscar */
.btn-search-basic-govco:hover {
  background-color: var(--govcolor-solitude, #e5ecf8);
  border-radius: 50%;
}

/* Foco visible en botón buscar - CC17 (contraste 3:1) */
.btn-search-basic-govco:focus-visible {
  outline: 0.1875rem solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 0.125rem;
  border-radius: 50%;
}

/* Estado activo del contenedor (cuando tiene foco) - CC17 */
.container-govco.active .input-search-basic-govco {
  border-bottom-color: var(--govcolor-cobalt, #0943b5);
}

/* Escalado al 200% sin deformación - CC4 */
@media (max-width: 576px) {
  .input-search-basic-govco {
    font-size: 1rem; /* 16px mínimo en móvil también */
  }
}

/* El icono de la lupa del Kit se muestra con clase govco-search */
:deep(.govco-search) {
  width: 1.25rem;
  height: 1.25rem;
}

/* El icono de X del Kit */
:deep(.govco-times) {
  width: 1rem;
  height: 1rem;
}

/* ---------------------------------------------------------------------------
   Sugerencias (docs/busqueda-interaccion.md §4.6)
   --------------------------------------------------------------------------- */

/* Indicador de carga: un anillo con un tramo de color, **quieto**. La
   especificación no admite animaciones, y con ellas se respeta
   `prefers-reduced-motion` sin tener que mirarlo. */
.indicador-carga-buscador {
  flex: none;
  box-sizing: border-box;
  width: 1.25rem;
  height: 1.25rem;
  margin-inline-end: 0.25rem;
  border: 0.1875rem solid var(--govcolor-solitude, #e5ecf8);
  border-top-color: var(--govcolor-cobalt, #0943b5);
  border-radius: 50%;
}

/* z-index 1220: por encima del círculo de accesibilidad (1210), para que no tape
   la lista (AC-22.12). Sin `max-height` fijo: lo calcula el componente con
   `visualViewport` y esta cifra es sólo el respaldo si todavía no se midió. */
.sugerencias-busqueda-govco {
  position: absolute;
  inset-inline: 0;
  top: 100%;
  z-index: 1220;
  box-sizing: border-box;
  max-height: 60vh;
  overflow-y: auto;
  overscroll-behavior: contain;
  border: 0.0625rem solid #767676;
  background-color: #fff;
  color: #1a1a1a;
  font-family: 'Nunito_Sans-Regular', system-ui, sans-serif;
  font-size: 1rem;
  line-height: 1.5;
}

.lista-sugerencias-govco {
  margin: 0;
  padding: 0;
  list-style: none;
}

/* Fila de al menos 48 px. El borde izquierdo de 4 px, además del fondo, indica la
   opción activa sin depender sólo del color. */
.opcion-sugerencia-govco {
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 3rem;
  padding: 0.5rem 0.75rem;
  border-inline-start: 0.25rem solid transparent;
  cursor: pointer;
}

.opcion-sugerencia-govco + .opcion-sugerencia-govco {
  border-top: 0.0625rem solid var(--govcolor-solitude, #e5ecf8);
}

.opcion-sugerencia-govco:hover,
.opcion-sugerencia-govco.activa {
  background-color: #e6edfa;
}

.opcion-sugerencia-govco.activa {
  border-inline-start-color: var(--govcolor-cobalt, #0943b5);
}

.titulo-sugerencia-govco {
  color: #1a1a1a;
}

.categoria-sugerencia-govco {
  color: #4c4c4c;
  font-size: 0.875rem;
}

.mensaje-sugerencias-govco {
  box-sizing: border-box;
  display: flex;
  align-items: center;
  min-height: 3rem;
  margin: 0;
  padding: 0.5rem 0.75rem;
}
</style>
