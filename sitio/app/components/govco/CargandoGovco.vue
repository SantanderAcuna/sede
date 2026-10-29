<script setup lang="ts">
/**
 * Indicador de carga del Kit UI (RF-B3-070, CAG-29).
 *
 * El requisito pide dos cosas: un spinner mientras se espera, y que **si la
 * espera pasa de diez segundos se informe del estado**. Quien lo mira sin saber
 * si el sistema trabaja o se cayó no tiene de dónde agarrarse, y a los diez
 * segundos ya hay motivo para decirlo.
 *
 * El componente existe **sólo mientras se carga** (el padre lo pone con `v-if`),
 * así que el reloj nace al montarse y muere al desmontarse: no hay que reiniciar
 * nada cuando la carga termina y empieza otra, porque es otro componente.
 *
 * `role="status"` anuncia el mensaje al aparecer y, como es una región viva, el
 * aviso de espera prolongada que se añade después también se anuncia.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue'

interface Props {
  mensaje?: string
  /** Segundos de espera tras los cuales se añade el aviso. El requisito dice 10. */
  segundosAntesDeAvisar?: number
  avisoLargo?: string
}

const props = withDefaults(defineProps<Props>(), {
  mensaje: 'Cargando…',
  segundosAntesDeAvisar: 10,
  avisoLargo:
    'La consulta está tardando más de lo habitual. Seguimos esperando la respuesta del servicio; si no llega, podrá intentarlo de nuevo.',
})

const tardaDemasiado = ref(false)
let temporizador: ReturnType<typeof setTimeout> | undefined

onMounted(() => {
  temporizador = setTimeout(() => {
    tardaDemasiado.value = true
  }, props.segundosAntesDeAvisar * 1000)
})

onBeforeUnmount(() => clearTimeout(temporizador))
</script>

<template>
  <p class="cargando-govco" role="status">
    <span class="govco-svg govco-spinner cargando-govco-indicador" aria-hidden="true" />
    <span class="cargando-govco-mensaje">{{ mensaje }}</span>
    <span v-if="tardaDemasiado" class="aviso-carga-larga">{{ avisoLargo }}</span>
  </p>
</template>
