<script setup lang="ts">
/**
 * Barra de accesibilidad (Kit UI, componente transversal 1).
 *
 * Ofrece contraste y tamaño de letra. Es obligatoria en todas las páginas y se
 * oculta en pantallas estrechas, como manda el Kit (`d-none d-lg-flex`).
 *
 * El comportamiento que el Kit resuelve con `script.js` vive aquí en el estado
 * reactivo de `useAccesibilidad`. Dos notas sobre por qué se aparta:
 *
 *  - El botón de contraste del Kit alterna `contrast-govco`, una clase cuyas
 *    dos reglas en `all.css` sólo afectan a su caja de demostración: en una
 *    página real no hace nada. Aquí hay un modo de alto contraste de verdad.
 *  - El tamaño de letra del Kit recorre todos los elementos y les escribe un
 *    `font-size` en línea. Aquí se escala la raíz, que además alcanza al
 *    contenido que se dibuja después.
 */
const { preferencias, alternarContraste, moverLetra, puedeAumentar, puedeReducir } =
  useAccesibilidad()

// El Kit marca con `active` el último botón pulsado. Se conserva esa señal
// visual, y en el contraste se añade `aria-pressed`, que es lo que un lector de
// pantalla necesita para saber que es un interruptor y en qué posición está.
const ultimoPulsado = ref<'contraste' | 'reducir' | 'aumentar' | null>(null)

function alAlternarContraste() {
  ultimoPulsado.value = 'contraste'
  alternarContraste()
}

function alMoverLetra(paso: 1 | -1) {
  ultimoPulsado.value = paso > 0 ? 'aumentar' : 'reducir'
  moverLetra(paso)
}
</script>

<template>
  <div class="posicion-barra-accesibilidad">
    <div class="barra-accesibilidad-govco d-none d-lg-flex">
      <button
        type="button"
        class="contrast"
        :class="{ active: ultimoPulsado === 'contraste' }"
        aria-label="Cambiar contraste"
        :aria-pressed="preferencias.contraste"
        @click="alAlternarContraste"
      >
        <span class="govco-contrast" aria-hidden="true" />
      </button>

      <button
        type="button"
        class="decrease-font-size"
        :class="{ active: ultimoPulsado === 'reducir' }"
        aria-label="Disminuir letra"
        :disabled="!puedeReducir"
        @click="alMoverLetra(-1)"
      >
        <span class="govco-font-minimize" aria-hidden="true" />
      </button>

      <button
        type="button"
        class="increase-font-size"
        :class="{ active: ultimoPulsado === 'aumentar' }"
        aria-label="Aumentar letra"
        :disabled="!puedeAumentar"
        @click="alMoverLetra(1)"
      >
        <span class="govco-font-maximize" aria-hidden="true" />
      </button>
    </div>
  </div>
</template>

<style scoped>
/*
 * Posición fija a la derecha y centrada verticalmente, como el ejemplo del Kit.
 * El Kit lo resolvía con utilidades de Bootstrap (`position-fixed top-50
 * translate-middle-y`) más estilos en línea; aquí va en la hoja del componente,
 * que es donde corresponde y permite ajustarlo sin tocar la plantilla.
 */
.posicion-barra-accesibilidad {
  position: fixed;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  z-index: 1200;
}
</style>
