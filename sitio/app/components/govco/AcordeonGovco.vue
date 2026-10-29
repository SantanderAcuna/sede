<script setup lang="ts">
/**
 * Acordeón del Kit UI de GOV.CO (RF-B3-062).
 *
 * Es **un solo panel desplegable**; una lista de pasos o de preguntas es una
 * lista de acordeones. Se hizo así —y no con un componente que recibiera todos
 * los elementos— porque cada panel de la ficha de un trámite lleva dentro
 * contenido propio (requisitos, enlaces) que la página ya sabe dibujar, y un
 * componente de datos obligaría a pasar todo eso por una ranura por elemento.
 *
 * Lo que exige el requisito y dónde se cumple:
 *
 *  - **`aria-expanded` en el botón** del encabezado y `aria-controls` hacia la
 *    región. Es lo que anuncia el lector de pantalla: «contraído» / «expandido».
 *  - **No se abre al recibir el foco.** El panel cambia sólo con `click` del
 *    botón —que el navegador dispara también con Intro y Espacio—; no hay
 *    manejador de `focus`. Un acordeón que se abre al tabular sobre él mueve el
 *    contenido bajo los pies de quien navega con teclado.
 *  - **Los cuatro estados:** normal, `:hover`, `:focus-visible` y `disabled`,
 *    los del CSS del Kit para `.button-accordion-govco`.
 *
 * El contenido contraído sigue en el HTML con el atributo `hidden`: lo ven los
 * buscadores y no entra en el recorrido de teclado ni en el árbol de
 * accesibilidad. Es lo mismo que hacía el `<details>` que sustituye, sin la
 * diferencia de que `<details>` no expone `aria-expanded` en el botón.
 *
 * Los estilos propios van en `sitio.css` (sección «Componentes del Kit UI»), no
 * aquí: AGENTS.md prohíbe el color en crudo fuera de `tokens.css` y esa hoja es la
 * que el linter del proyecto revisa.
 */
import { computed, useId } from 'vue'

interface Props {
  /** Texto del encabezado: lo que el botón dice. */
  titulo: string
  /** Nivel del encabezado semántico que envuelve al botón. */
  nivel?: 2 | 3 | 4 | 5 | 6
  /** Número o marca que se dibuja en el círculo de la izquierda (opcional). */
  numero?: number | string
  /** Con `true` el panel no responde: queda cerrado y fuera del teclado. */
  deshabilitado?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  nivel: 3,
  numero: undefined,
  deshabilitado: false,
})

/**
 * Estado abierto/cerrado, enlazable con `v-model:abierto`. Con `defineModel` el
 * componente funciona igual sin que el padre lo controle: guarda su propio
 * estado local, que es el uso normal en una lista de pasos.
 */
const abierto = defineModel<boolean>('abierto', { default: false })

const id = useId()
const idBoton = `${id}-boton`
const idRegion = `${id}-region`

const etiquetaEncabezado = computed(() => `h${props.nivel}`)

function alternar(): void {
  if (props.deshabilitado) return
  abierto.value = !abierto.value
}
</script>

<template>
  <div class="accordion-govco acordeon-govco">
    <div class="item-accordion-govco">
      <component :is="etiquetaEncabezado" class="accordion-header">
        <button
          :id="idBoton"
          type="button"
          class="button-accordion-govco"
          :class="{ collapsed: !abierto, 'button-number-accordion-govco': numero !== undefined }"
          :aria-expanded="abierto ? 'true' : 'false'"
          :aria-controls="idRegion"
          :disabled="deshabilitado"
          @click="alternar"
        >
          <span
            v-if="numero !== undefined"
            class="icon-button-accordion-govco"
            aria-hidden="true"
          >{{ numero }}</span>
          <span class="text-button-accordion-govco">{{ titulo }}</span>
        </button>
      </component>
      <div
        :id="idRegion"
        class="accordion-collapse acordeon-govco-region"
        role="region"
        :aria-labelledby="idBoton"
        :hidden="!abierto"
      >
        <div class="body-accordion-govco">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
