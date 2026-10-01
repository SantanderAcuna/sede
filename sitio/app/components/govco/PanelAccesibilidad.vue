<script setup lang="ts">
/**
 * Panel de ajustes de accesibilidad (brief §5.2.2 y §7).
 *
 * Es un `<dialog>` nativo abierto con `showModal()`. Se eligió el elemento nativo
 * y no un `<div role="dialog">` porque `showModal()` da **gratis y bien** las tres
 * cosas que a mano se hacen mal: trampa de foco, fondo inerte y cierre con
 * `Escape`. Además, al abrirse en la *capa superior*, el diálogo escapa de
 * cualquier `overflow` o `transform` de un ancestro.
 *
 * **Los once controles del brief viven aquí**, agrupados en cuatro categorías con
 * título: Contraste (4 modos excluyentes), Tamaño de texto (100 %–200 %), Lectura
 * (espaciado, dislexia, resaltar enlaces, guía de lectura) y Movimiento (detener
 * animaciones), más Restablecer y la ayuda con los atajos.
 *
 * **Decisiones de accesibilidad que no son evidentes:**
 *
 *  - **Radios nativos** (`<input type="radio">`) para los modos de contraste: dan
 *    la navegación con flechas sin programarla y el grupo con `fieldset`/`legend`
 *    queda anunciado como un todo.
 *  - **Interruptores con `role="switch"`** sobre una casilla nativa: el estado se
 *    anuncia como «activado/desactivado» y se opera con `Espacio` sin capturar
 *    ninguna tecla a mano. Se usa `role="switch"` + `checked` y no `aria-pressed`
 *    porque `aria-pressed` sobre un `switch` es una combinación inválida.
 *  - **El aviso de que los cambios se guardan solos va ANTES de los controles**,
 *    como exige CC15.
 *  - **Una sola región `role="status"`** anuncia los cambios; no se pone
 *    `aria-live` a cada control, que produciría anuncios duplicados.
 *  - **Nada actúa al recibir el foco** (CC22): todos los cambios son por
 *    activación explícita.
 *  - **El panel vive fuera del contenido filtrable.** Los modos inverso y escala
 *    de grises aplican un `filter` al envoltorio del contenido de la página; si
 *    el panel estuviera dentro, sus colores se invertirían y, peor, un `filter`
 *    convierte al elemento en bloque contenedor de los descendientes con
 *    `position: fixed`. El panel se monta fuera de ese envoltorio y además se
 *    teletransporta a `body` como garantía frente a recortes por `overflow`.
 *
 * **Criterios:** CAG-07 (barra de accesibilidad) · RF-B1-044 (barra persistente
 * con tamaño de fuente, contraste, salto al contenido y Centro de Relevo) ·
 * RF-B3-014 (espaciado configurable) · RNF-B3-005 (44 × 44 px) · CC1–CC32 del
 * Anexo 1 de la Resolución 1519 de 2020.
 */
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'

import type { AjusteBooleano, ModoContraste } from '~/composables/useAccesibilidad'

const {
  preferencias,
  panelAbierto,
  sugerenciaContrasteAlto,
  porcentajeLetra,
  hayAjustes,
  puedeAumentar,
  puedeReducir,
  definirContraste,
  moverLetra,
  alternar,
  restablecer,
  cerrarPanel,
} = useAccesibilidad()

/** Identificador que los disparadores anuncian con `aria-controls`. */
const ID_PANEL = 'panel-accesibilidad'
const ID_TITULO = 'panel-accesibilidad-titulo'

/** Texto visible de cada modo de contraste. */
const ETIQUETA_MODO: Record<ModoContraste, string> = {
  normal: 'Normal',
  alto: 'Alto contraste',
  inverso: 'Colores invertidos',
  grises: 'Escala de grises',
}

const MODOS: readonly ModoContraste[] = ['normal', 'alto', 'inverso', 'grises']

/** Los cuatro ajustes de lectura, en el orden en que se muestran. */
const AJUSTES_LECTURA = [
  { clave: 'espaciado', etiqueta: 'Más espaciado entre letras y líneas' },
  { clave: 'dislexia', etiqueta: 'Fuente y disposición para dislexia' },
  { clave: 'resaltarEnlaces', etiqueta: 'Resaltar los enlaces' },
  { clave: 'guiaLectura', etiqueta: 'Guía de lectura' },
] as const

const dialogo = ref<HTMLDialogElement | null>(null)
const titulo = ref<HTMLHeadingElement | null>(null)

/** Quien abrió el panel, para devolverle el foco al cerrar. */
let invocador: HTMLElement | null = null
let temporizadorAnuncio: ReturnType<typeof setTimeout> | null = null

/** Texto que la región `role="status"` anuncia. */
const anuncio = ref('')

/**
 * Anuncia un cambio. Se vacía primero porque los lectores de pantalla ignoran un
 * texto idéntico al anterior, y «Aumentar letra» dos veces seguidas es justo el
 * caso más común.
 */
function anunciar(texto: string): void {
  anuncio.value = ''
  if (temporizadorAnuncio !== null) clearTimeout(temporizadorAnuncio)
  temporizadorAnuncio = setTimeout(() => {
    anuncio.value = texto
  }, 60)
}

// Abrir y cerrar el diálogo nativo siguiendo el estado compartido.
watch(panelAbierto, async (abierto) => {
  const panel = dialogo.value
  if (panel === null) return

  if (abierto) {
    invocador = document.activeElement instanceof HTMLElement ? document.activeElement : null
    if (!panel.open) panel.showModal()
    await nextTick()
    // El foco entra en el título para que el lector anuncie el contexto antes
    // que el primer control. `tabindex="-1"` en el título lo permite.
    titulo.value?.focus()
    anuncio.value = ''
  } else if (panel.open) {
    panel.close()
  }
})

/**
 * Devuelve el foco a quien abrió el panel.
 *
 * Si al abrir no había nada enfocado —Safari no enfoca un botón al pulsarlo con
 * el ratón—, se busca el disparador por el contrato que ya está declarado en el
 * marcado: el `aria-controls` que apunta a este panel. Así el panel no necesita
 * conocer a sus disparadores.
 */
function devolverFoco(): void {
  const candidato = invocador
  invocador = null
  const destino =
    candidato instanceof HTMLElement && candidato !== document.body
      ? candidato
      : document.querySelector<HTMLElement>(`[aria-controls="${ID_PANEL}"]`)
  destino?.focus()
}

/** Se dispara con cualquier cierre: `Escape`, el botón o la tecla del sistema. */
function alCerrarDialogo(): void {
  if (panelAbierto.value) panelAbierto.value = false
  devolverFoco()
}

/** Cierra al pulsar el fondo: el objetivo del clic es el propio `<dialog>`. */
function alPulsarFondo(evento: MouseEvent): void {
  if (evento.target === dialogo.value) cerrarPanel()
}

function alElegirContraste(modo: ModoContraste): void {
  definirContraste(modo)
  anunciar(`Contraste: ${ETIQUETA_MODO[modo]}`)
}

function alMoverLetra(paso: 1 | -1): void {
  moverLetra(paso)
  // `moverLetra` es síncrono y `porcentajeLetra` es una `computed`, así que al
  // leerla aquí ya refleja el valor nuevo.
  anunciar(`Tamaño de texto: ${porcentajeLetra.value} %`)
}

function alAlternar(clave: AjusteBooleano, etiqueta: string): void {
  alternar(clave)
  const activado = preferencias.value[clave]
  anunciar(`${etiqueta}: ${activado ? 'activado' : 'desactivado'}`)
}

function alRestablecer(): void {
  restablecer()
  anunciar('Ajustes de accesibilidad restablecidos')
}

onBeforeUnmount(() => {
  if (temporizadorAnuncio !== null) clearTimeout(temporizadorAnuncio)
})

/** Hay ajustes activos: se usa para habilitar el botón de restablecer. */
const puedeRestablecer = computed(() => hayAjustes.value)
</script>

<template>
  <!--
    Se mantiene teletransportado a `body`. El panel se abre con `showModal()`, que
    ya lo lleva a la capa superior, así que el teletransporte es una garantía
    añadida: ningún `overflow` ni `transform` de un ancestro puede recortarlo.

    **No altera el orden de tabulación de la página.** Mientras está cerrado, un
    `<dialog>` sin el atributo `open` es `display: none` por la hoja del
    navegador, de modo que no recibe foco y el enlace «Saltar al contenido
    principal» sigue siendo el primer tabulable (RF-B3-022). Y mientras está
    abierto, la trampa de foco de `showModal()` mantiene el foco dentro, que es
    lo que se espera de un diálogo modal.
  -->
  <Teleport to="body">
    <dialog
      :id="ID_PANEL"
      ref="dialogo"
      class="panel-accesibilidad"
      :aria-labelledby="ID_TITULO"
      @close="alCerrarDialogo"
      @click="alPulsarFondo"
    >
      <div class="panel-cuerpo">
        <header class="panel-cabecera">
          <h2 :id="ID_TITULO" ref="titulo" class="panel-titulo" tabindex="-1">
            Ajustes de accesibilidad
          </h2>
          <button
            type="button"
            class="panel-cerrar"
            @click="cerrarPanel"
          >
            <span aria-hidden="true">×</span>
            <span class="panel-solo-lectores">Cerrar los ajustes de accesibilidad</span>
          </button>
        </header>

        <!--
          El aviso va ANTES de los controles, como exige CC15: quien navega con
          lector de pantalla tiene que saberlo antes de tocar nada.
        -->
        <p class="panel-aviso">
          Los cambios se aplican al instante y se guardan en este navegador para su
          próxima visita.
        </p>

        <div class="panel-contenido">
          <!-- ── Contraste ─────────────────────────────────────────────── -->
          <fieldset class="panel-grupo">
            <legend class="panel-grupo-titulo">Contraste</legend>

            <!--
              El sistema pide más contraste y el ciudadano no ha elegido nunca:
              se le propone, no se le impone (el brief prohíbe forzar cambios sin
              consentimiento).
            -->
            <p v-if="sugerenciaContrasteAlto && preferencias.contraste === 'normal'" class="panel-sugerencia">
              Su dispositivo está configurado para pedir más contraste. Puede
              activar «Alto contraste» cuando quiera.
            </p>

            <div class="panel-radios">
              <label
                v-for="modo in MODOS"
                :key="modo"
                class="panel-radio"
              >
                <input
                  type="radio"
                  name="modo-contraste"
                  class="panel-radio-campo"
                  :value="modo"
                  :checked="preferencias.contraste === modo"
                  @change="alElegirContraste(modo)"
                >
                <span class="panel-radio-marca" aria-hidden="true" />
                <span class="panel-radio-texto">{{ ETIQUETA_MODO[modo] }}</span>
              </label>
            </div>
          </fieldset>

          <!-- ── Tamaño de texto ───────────────────────────────────────── -->
          <div class="panel-grupo">
            <h3 id="panel-grupo-texto" class="panel-grupo-titulo">Tamaño de texto</h3>
            <div class="panel-tamano" role="group" aria-labelledby="panel-grupo-texto">
              <button
                type="button"
                class="panel-boton-tamano"
                :disabled="!puedeReducir"
                aria-label="Reducir el tamaño del texto"
                @click="alMoverLetra(-1)"
              >
                <span aria-hidden="true">A−</span>
              </button>

              <output class="panel-tamano-valor">{{ porcentajeLetra }} %</output>

              <button
                type="button"
                class="panel-boton-tamano"
                :disabled="!puedeAumentar"
                aria-label="Aumentar el tamaño del texto"
                @click="alMoverLetra(1)"
              >
                <span aria-hidden="true">A+</span>
              </button>
            </div>
          </div>

          <!-- ── Lectura ──────────────────────────────────────────────── -->
          <div class="panel-grupo">
            <h3 id="panel-grupo-lectura" class="panel-grupo-titulo">Lectura</h3>
            <ul class="panel-interruptores" aria-labelledby="panel-grupo-lectura">
              <li v-for="ajuste in AJUSTES_LECTURA" :key="ajuste.clave">
                <label class="panel-interruptor">
                  <input
                    type="checkbox"
                    role="switch"
                    class="panel-interruptor-campo"
                    :checked="preferencias[ajuste.clave]"
                    @change="alAlternar(ajuste.clave, ajuste.etiqueta)"
                  >
                  <span class="panel-interruptor-pista" aria-hidden="true">
                    <span class="panel-interruptor-bola" />
                  </span>
                  <span class="panel-interruptor-texto">{{ ajuste.etiqueta }}</span>
                </label>
              </li>
            </ul>
          </div>

          <!-- ── Movimiento ───────────────────────────────────────────── -->
          <div class="panel-grupo">
            <h3 id="panel-grupo-movimiento" class="panel-grupo-titulo">Movimiento</h3>
            <ul class="panel-interruptores" aria-labelledby="panel-grupo-movimiento">
              <li>
                <label class="panel-interruptor">
                  <input
                    type="checkbox"
                    role="switch"
                    class="panel-interruptor-campo"
                    :checked="preferencias.detenerAnimaciones"
                    @change="alAlternar('detenerAnimaciones', 'Detener las animaciones')"
                  >
                  <span class="panel-interruptor-pista" aria-hidden="true">
                    <span class="panel-interruptor-bola" />
                  </span>
                  <span class="panel-interruptor-texto">Detener las animaciones</span>
                </label>
              </li>
            </ul>
          </div>

          <!-- ── Restablecer y Centro de Relevo ───────────────────────── -->
          <div class="panel-pie">
            <button
              type="button"
              class="panel-boton panel-boton-primario"
              :disabled="!puedeRestablecer"
              @click="alRestablecer"
            >
              Restablecer todo
            </button>

            <!--
              RF-B1-044 exige que el enlace al Centro de Relevo viva en la barra
              de accesibilidad: es el servicio del Estado que atiende por
              video-llamada a la ciudadanía con discapacidad auditiva, y quien no
              puede oír el conmutador tiene que encontrarlo donde busca los
              ajustes de accesibilidad. Es un dominio del Estado, así que el aviso
              de salida no se interpone (RN-01-D03).
            -->
            <a
              class="panel-boton panel-boton-enlace"
              href="https://www.centroderelevo.gov.co"
              target="_blank"
              rel="noopener noreferrer"
            >
              Centro de Relevo
              <span class="panel-solo-lectores">(abre en una pestaña nueva)</span>
            </a>
          </div>

          <details class="panel-ayuda">
            <summary>Ayuda y atajos de teclado</summary>
            <ul>
              <li><kbd>Tab</kbd> y <kbd>Mayús</kbd> + <kbd>Tab</kbd>: moverse entre los controles.</li>
              <li><kbd>Intro</kbd> o <kbd>Espacio</kbd>: activar el control enfocado.</li>
              <li><kbd>↑</kbd> y <kbd>↓</kbd>: elegir el modo de contraste.</li>
              <li><kbd>Esc</kbd>: cerrar estos ajustes y volver a la página.</li>
            </ul>
            <p>
              Si necesita ayuda para usar la sede, puede llamar al conmutador o
              escribir al correo de atención; ambos datos están en el pie de página.
            </p>
          </details>
        </div>

        <!--
          Una sola región de anuncios para todo el panel. `polite` y no
          `assertive`: un ajuste de presentación no debe interrumpir lo que la
          persona esté leyendo (CC19 y buenas prácticas de ARIA).
        -->
        <div class="panel-solo-lectores" role="status" aria-live="polite">
          {{ anuncio }}
        </div>
      </div>
    </dialog>
  </Teleport>
</template>

<style scoped>
/* ── Tokens del panel ───────────────────────────────────────────────────── */
.panel-accesibilidad {
  --panel-superficie: #ffffff;
  --panel-texto: #1a1a1a;
  --panel-primario: var(--govcolor-cobalt, #0943b5);
  --panel-borde: #c9c9c9;
  --panel-solitude: var(--govcolor-solitude, #e5ecf8);
  --panel-foco: #1a1a1a;
  --panel-radio: 0.75rem;

  width: min(34rem, calc(100vw - 2rem));
  /*
   * El alto se ajusta para que los once controles quepan sin desplazar en una
   * pantalla de portátil corriente: la ayuda va plegada por defecto, así que lo
   * que manda es la rejilla de modos, la escala de texto y los cinco
   * interruptores. `92vh` deja ver el borde del panel y no confunde con una
   * página cortada; `dvh` acompaña cuando el navegador móvil oculta su barra.
   */
  max-height: min(92vh, 50rem);
  max-height: min(92dvh, 50rem);
  padding: 0;
  border: none;
  border-radius: var(--panel-radio);
  background-color: var(--panel-superficie);
  color: var(--panel-texto);
  box-shadow: 0 1.5rem 3rem rgba(0, 0, 0, 0.28);
  overflow: hidden;
}

/* El cuerpo es lo que se desplaza: la cabecera y el contenido quedan separados. */
.panel-cuerpo {
  display: flex;
  flex-direction: column;
  max-height: inherit;
}

.panel-cabecera {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.5rem 0.75rem;
  border-bottom: 0.0625rem solid var(--panel-borde);
}

.panel-titulo {
  margin: 0;
  font-family: 'Nunito_Sans-Bold', system-ui, sans-serif;
  font-size: 1.25rem;
  line-height: 1.3;
  color: var(--panel-primario);
}

.panel-titulo:focus-visible {
  outline: 0.1875rem solid var(--panel-foco);
  outline-offset: 0.125rem;
}

.panel-cerrar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  width: 2.75rem;
  height: 2.75rem;
  border: 0.125rem solid transparent;
  border-radius: 50%;
  background-color: transparent;
  color: var(--panel-texto);
  font-size: 1.75rem;
  line-height: 1;
  cursor: pointer;
}

.panel-cerrar:hover {
  background-color: var(--panel-solitude);
}

.panel-cerrar:focus-visible {
  outline: 0.1875rem solid var(--panel-foco);
  outline-offset: 0.125rem;
  box-shadow: 0 0 0 0.3125rem #ffbf00;
}

.panel-aviso {
  margin: 0;
  padding: 0.875rem 1.5rem 0;
  font-family: 'Verdana-Regular', system-ui, sans-serif;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #4c4c4c;
}

.panel-contenido {
  padding: 1rem 1.5rem 1.5rem;
  overflow-y: auto;
}

/* ── Grupos ─────────────────────────────────────────────────────────────── */
.panel-grupo {
  margin: 0 0 1.25rem;
  padding: 0;
  border: 0;
}

.panel-grupo-titulo {
  margin: 0 0 0.75rem;
  padding: 0;
  font-family: 'Nunito_Sans-SemiBold', system-ui, sans-serif;
  font-size: 0.8125rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #4c4c4c;
}

.panel-sugerencia {
  margin: 0 0 0.75rem;
  padding: 0.625rem 0.75rem;
  border-left: 0.25rem solid var(--panel-primario);
  background-color: var(--panel-solitude);
  font-family: 'Verdana-Regular', system-ui, sans-serif;
  font-size: 0.8125rem;
  line-height: 1.5;
}

/* ── Modos de contraste (radios nativos) ────────────────────────────────── */
.panel-radios {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
  gap: 0.5rem;
}

.panel-radio {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  min-height: 2.75rem;
  padding: 0.5rem 0.75rem;
  border: 0.125rem solid var(--panel-borde);
  border-radius: 0.5rem;
  cursor: pointer;
  font-family: 'Verdana-Regular', system-ui, sans-serif;
  font-size: 0.9375rem;
}

.panel-radio:hover {
  background-color: var(--panel-solitude);
}

/* La casilla nativa se mantiene en el flujo de accesibilidad y fuera de la vista. */
.panel-radio-campo {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

/*
 * La marca no depende del color: la opción elegida lleva además un punto relleno
 * y el borde engrosado, así que se distingue también en escala de grises y en
 * alto contraste (CC5).
 */
.panel-radio-marca {
  position: relative;
  flex: none;
  width: 1.25rem;
  height: 1.25rem;
  border: 0.125rem solid #4c4c4c;
  border-radius: 50%;
  background-color: #fff;
}

.panel-radio-campo:checked ~ .panel-radio-marca {
  border-color: var(--panel-primario);
  border-width: 0.375rem;
}

.panel-radio-campo:checked ~ .panel-radio-texto {
  font-weight: 700;
}

.panel-radio-campo:focus-visible ~ .panel-radio-marca {
  outline: 0.1875rem solid var(--panel-foco);
  outline-offset: 0.125rem;
  box-shadow: 0 0 0 0.3125rem #ffbf00;
}

/* ── Tamaño de texto ────────────────────────────────────────────────────── */
.panel-tamano {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.panel-boton-tamano {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 3rem;
  height: 2.75rem;
  border: 0.125rem solid var(--panel-borde);
  border-radius: 0.5rem;
  background-color: #fff;
  color: var(--panel-texto);
  font-family: 'Nunito_Sans-Bold', system-ui, sans-serif;
  font-size: 1rem;
  cursor: pointer;
}

.panel-boton-tamano:hover:not(:disabled) {
  background-color: var(--panel-solitude);
  border-color: var(--panel-primario);
}

.panel-boton-tamano:focus-visible {
  outline: 0.1875rem solid var(--panel-foco);
  outline-offset: 0.125rem;
  box-shadow: 0 0 0 0.3125rem #ffbf00;
}

.panel-boton-tamano:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.panel-tamano-valor {
  min-width: 4.5rem;
  font-family: 'Nunito_Sans-Bold', system-ui, sans-serif;
  font-size: 1.125rem;
  text-align: center;
  color: var(--panel-primario);
}

/* ── Interruptores ──────────────────────────────────────────────────────── */
.panel-interruptores {
  margin: 0;
  padding: 0;
  list-style: none;
}

.panel-interruptor {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-height: 2.75rem;
  padding: 0.25rem 0;
  cursor: pointer;
  font-family: 'Verdana-Regular', system-ui, sans-serif;
  font-size: 0.9375rem;
}

.panel-interruptor-campo {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

.panel-interruptor-pista {
  position: relative;
  flex: none;
  width: 2.75rem;
  height: 1.5rem;
  border: 0.125rem solid #4c4c4c;
  border-radius: 1rem;
  background-color: #fff;
}

.panel-interruptor-bola {
  position: absolute;
  top: 50%;
  left: 0.125rem;
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  background-color: #4c4c4c;
  transform: translateY(-50%);
}

/*
 * El estado activo no se distingue sólo por el color: la bola se desplaza y la
 * pista engrosa el borde. En alto contraste, donde el color se fuerza, el
 * desplazamiento sigue leyéndose.
 */
.panel-interruptor-campo:checked ~ .panel-interruptor-pista {
  border-color: var(--panel-primario);
  background-color: var(--panel-solitude);
}

.panel-interruptor-campo:checked ~ .panel-interruptor-pista .panel-interruptor-bola {
  left: 1.125rem;
  background-color: var(--panel-primario);
}

.panel-interruptor-campo:focus-visible ~ .panel-interruptor-pista {
  outline: 0.1875rem solid var(--panel-foco);
  outline-offset: 0.125rem;
  box-shadow: 0 0 0 0.3125rem #ffbf00;
}

/* ── Pie y ayuda ────────────────────────────────────────────────────────── */
.panel-pie {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.panel-boton {
  min-height: 2.75rem;
  padding: 0 1.25rem;
  border: 0.125rem solid var(--panel-primario);
  border-radius: 1.5rem;
  font-family: 'Nunito_Sans-SemiBold', system-ui, sans-serif;
  font-size: 0.9375rem;
  cursor: pointer;
}

.panel-boton-primario {
  background-color: var(--panel-primario);
  color: #fff;
}

.panel-boton-primario:hover:not(:disabled) {
  background-color: #06307f;
  border-color: #06307f;
}

/* El Centro de Relevo es un enlace, no un botón: se distingue por el subrayado,
   no sólo por el color (CC5). */
.panel-boton-enlace {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  background-color: transparent;
  color: var(--panel-primario);
  text-decoration: underline;
  text-underline-offset: 0.15em;
}

.panel-boton-enlace:hover {
  background-color: var(--panel-solitude);
}

.panel-boton:focus-visible {
  outline: 0.1875rem solid var(--panel-foco);
  outline-offset: 0.125rem;
  box-shadow: 0 0 0 0.3125rem #ffbf00;
}

.panel-boton:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.panel-ayuda {
  border-top: 0.0625rem solid var(--panel-borde);
  padding-top: 1rem;
  font-family: 'Verdana-Regular', system-ui, sans-serif;
  font-size: 0.875rem;
  line-height: 1.6;
}

.panel-ayuda summary {
  min-height: 2.75rem;
  display: flex;
  align-items: center;
  font-family: 'Nunito_Sans-SemiBold', system-ui, sans-serif;
  font-size: 0.9375rem;
  color: var(--panel-primario);
  cursor: pointer;
}

.panel-ayuda summary:focus-visible {
  outline: 0.1875rem solid var(--panel-foco);
  outline-offset: 0.125rem;
  box-shadow: 0 0 0 0.3125rem #ffbf00;
}

.panel-ayuda ul {
  margin: 0.5rem 0 0.75rem;
  padding-left: 1.25rem;
}

.panel-ayuda li {
  margin-bottom: 0.375rem;
}

.panel-ayuda kbd {
  padding: 0.0625rem 0.3125rem;
  border: 0.0625rem solid #4c4c4c;
  border-radius: 0.25rem;
  background-color: #f5f5f5;
  font-family: 'Verdana-Bold', system-ui, sans-serif;
  font-size: 0.8125rem;
}

.panel-ayuda p {
  margin: 0;
}

/* ── Utilidad de sólo lectores ──────────────────────────────────────────── */
/*
 * El Kit no trae `visually-hidden` ni `sr-only`, y `display: none` no sirve:
 * sacaría el texto del árbol de accesibilidad, que es justo lo contrario de lo
 * que se busca.
 */
.panel-solo-lectores {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
  border: 0;
}
</style>
