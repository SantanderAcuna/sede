<script setup lang="ts">
/**
 * Volver arriba (Kit UI, componente transversal 8).
 *
 * Botón flotante en la esquina inferior derecha para páginas con scroll largo.
 *
 * El Kit salta al principio de golpe (`scrollTop = 0`). Aquí se desplaza con
 * suavidad, salvo si la persona ha pedido reducir el movimiento en su sistema:
 * un desplazamiento largo y animado marea a quien tiene sensibilidad vestibular,
 * y respetar esa preferencia es parte de la accesibilidad, no un adorno.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue'

const visible = ref(false)

function alDesplazar() {
  // Aparece tras algo más de una pantalla, que es cuando de verdad estorba
  // tener que subir a mano.
  visible.value = window.scrollY > window.innerHeight * 0.75
}

function subir() {
  const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  window.scrollTo({ top: 0, behavior: reducir ? 'auto' : 'smooth' })
}

onMounted(() => {
  alDesplazar()
  window.addEventListener('scroll', alDesplazar, { passive: true })
})

onBeforeUnmount(() => window.removeEventListener('scroll', alDesplazar))
</script>

<template>
  <!--
    Vive fuera de `.contenido-filtrable` —lo monta así la disposición—, porque
    los modos de contraste «colores invertidos» y «escala de grises» aplican un
    `filter` a ese envoltorio y un `filter` convierte a su elemento en bloque
    contenedor de los descendientes con `position: fixed`: dentro, este botón
    dejaría de estar anclado a la ventana y bajaría con el documento.
  -->
  <div v-show="visible" class="posicion-volver-arriba">
    <button type="button" class="volver-arriba-govco" aria-label="Volver arriba" @click="subir">
      <span class="govco-expand_circle_up" aria-hidden="true" />
    </button>
  </div>
</template>

<style scoped>
.posicion-volver-arriba {
  position: fixed;
  bottom: 10%;
  right: 10%;
  z-index: 1200;
}
</style>
