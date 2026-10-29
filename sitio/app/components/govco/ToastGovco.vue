<script setup lang="ts">
/**
 * Un aviso emergente del Kit UI (RF-B3-064). Se dibuja con las clases
 * `container-toast-govco` del Kit; los tres tipos son las tres variantes del Kit
 * (`info`, `success`, `error`).
 *
 * Accesibilidad:
 *
 *  - **`role="status"`** para lo informativo y lo positivo —se anuncia sin
 *    interrumpir— y **`role="alert"`** para lo negativo, que sí debe
 *    interrumpir. Ambos roles llevan implícito `aria-live` y `aria-atomic`.
 *  - **Duración suficiente y reloj que se detiene** (WCAG 2.2.1): mientras el
 *    cursor o el foco están sobre el aviso, el tiempo restante no corre; al
 *    salir, sigue desde donde se quedó y no desde cero. Sin esto, quien tarda en
 *    llegar con el teclado lo ve desaparecer.
 *  - **Se puede cerrar** con el botón y con `Esc` cuando el foco está dentro.
 *
 * `duracionMs: 0` deja el aviso fijo hasta que se cierre (es lo que usa el error).
 */
import { computed, onBeforeUnmount, onMounted } from 'vue'

import type { TipoToast } from '../../composables/useToast'

interface Props {
  tipo?: TipoToast
  titulo: string
  mensaje: string
  duracionMs?: number
}

const props = withDefaults(defineProps<Props>(), { tipo: 'informativa', duracionMs: 0 })
const emit = defineEmits<{ (e: 'cerrar'): void }>()

const VARIANTE_KIT: Record<TipoToast, 'info' | 'success' | 'error'> = {
  informativa: 'info',
  positiva: 'success',
  negativa: 'error',
}

const variante = computed(() => VARIANTE_KIT[props.tipo])
const rol = computed(() => (props.tipo === 'negativa' ? 'alert' : 'status'))

let restanteMs = props.duracionMs
let inicioMs = 0
let temporizador: ReturnType<typeof setTimeout> | undefined
let encima = false
let conFoco = false

function arrancar(): void {
  if (restanteMs <= 0 || temporizador !== undefined) return
  inicioMs = Date.now()
  temporizador = setTimeout(() => emit('cerrar'), restanteMs)
}

function pausar(): void {
  if (temporizador === undefined) return
  clearTimeout(temporizador)
  temporizador = undefined
  restanteMs = Math.max(0, restanteMs - (Date.now() - inicioMs))
}

function alEntrarElCursor(): void {
  encima = true
  pausar()
}
function alSalirElCursor(): void {
  encima = false
  if (!conFoco) arrancar()
}
function alRecibirFoco(): void {
  conFoco = true
  pausar()
}
function alPerderFoco(): void {
  conFoco = false
  if (!encima) arrancar()
}

onMounted(arrancar)
onBeforeUnmount(() => {
  if (temporizador !== undefined) clearTimeout(temporizador)
})
</script>

<template>
  <div
    class="toast show container-toast-govco toast-govco"
    :class="variante"
    :role="rol"
    aria-atomic="true"
    @mouseenter="alEntrarElCursor"
    @mouseleave="alSalirElCursor"
    @focusin="alRecibirFoco"
    @focusout="alPerderFoco"
    @keydown.esc="emit('cerrar')"
  >
    <div class="toast-header" :class="`toast-header-${variante}-govco`">
      <strong class="me-auto toast-title-govco">{{ titulo }}</strong>
      <button
        type="button"
        class="toast-govco-cerrar"
        aria-label="Cerrar notificación"
        @click="emit('cerrar')"
      >
        <span class="govco-svg govco-times" :class="variante" aria-hidden="true" />
      </button>
    </div>
    <div class="toast-body toast-body-govco">
      <p>{{ mensaje }}</p>
    </div>
  </div>
</template>
