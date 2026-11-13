<script setup lang="ts">
/**
 * Elegir una imagen para subirla.
 *
 * El `<input type="file">` queda oculto sólo a la vista (`sr-only`: sigue disponible para teclado
 * y lectores de pantalla) y una etiqueta propia, en español, lo abre: el botón nativo dice «Choose File»
 * según el idioma del navegador y no se puede traducir. Valida en el cliente tipo y
 * tamaño para avisar al instante; el servidor vuelve a validar siempre.
 */
import { computed, onBeforeUnmount, ref, useId } from 'vue'

interface Props {
  /** Imagen que ya existe (se muestra hasta que se elija otra). */
  actual?: { url: string; alt: string | null } | null
  /** Con `true` pide la descripción (texto alternativo) junto a la imagen. */
  pedirAlt?: boolean
  alt?: string
  altMax?: number
  /** Error que viene de fuera (p. ej. del servidor). */
  error?: string
  /** Texto de ayuda bajo el botón. */
  ayuda?: string
}
const props = withDefaults(defineProps<Props>(), {
  actual: null, pedirAlt: false, alt: '', altMax: 250, error: undefined, ayuda: 'PNG, JPG o WEBP, hasta 5 MB. Puede ser de cualquier proporción.',
})
const emit = defineEmits<{ seleccionada: [archivo: File]; 'update:alt': [valor: string] }>()

const TIPOS = ['image/png', 'image/jpeg', 'image/webp']
const MAX_BYTES = 5 * 1024 * 1024

const id = useId()
const entrada = ref<HTMLInputElement | null>(null)
const archivo = ref<File | null>(null)
const vistaPrevia = ref<string | null>(null)
const errorLocal = ref('')

const mensajeError = computed(() => errorLocal.value || props.error || '')
const hayImagen = computed(() => archivo.value !== null || props.actual !== null)
const urlMostrada = computed(() => vistaPrevia.value ?? props.actual?.url ?? null)
const altMostrado = computed(() => (archivo.value ? 'Vista previa de la imagen elegida' : props.actual?.alt ?? ''))

function liberar() {
  if (vistaPrevia.value) URL.revokeObjectURL(vistaPrevia.value)
  vistaPrevia.value = null
}

function alElegir(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (!f) return
  errorLocal.value = ''
  if (!TIPOS.includes(f.type)) {
    errorLocal.value = 'Solo se aceptan imágenes PNG, JPG o WebP.'
    return
  }
  if (f.size > MAX_BYTES) {
    errorLocal.value = 'La imagen supera el tamaño máximo permitido (5 MB).'
    return
  }
  liberar()
  archivo.value = f
  vistaPrevia.value = URL.createObjectURL(f)
  emit('seleccionada', f)
}

/** Para que quien contiene el componente pueda reiniciarlo (p. ej. tras subir). */
function reiniciar() {
  liberar()
  archivo.value = null
  errorLocal.value = ''
  if (entrada.value) entrada.value.value = ''
}

onBeforeUnmount(liberar)
defineExpose({ reiniciar })
</script>

<template>
  <div class="space-y-3">
    <img
      v-if="urlMostrada"
      :src="urlMostrada"
      :alt="altMostrado"
      class="max-h-56 max-w-full rounded-lg border border-slate-200"
    >

    <div>
      <input
        :id="`${id}-archivo`"
        ref="entrada"
        type="file"
        class="sr-only"
        accept="image/png,image/jpeg,image/webp"
        :aria-describedby="`${id}-ayuda`"
        :aria-invalid="mensajeError ? 'true' : undefined"
        @change="alElegir"
      >
      <label
        :for="`${id}-archivo`"
        class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-800 hover:bg-slate-50 focus-within:ring-2 focus-within:ring-gov-blue/40"
      >
        {{ hayImagen ? 'Cambiar la imagen' : 'Elegir una imagen' }}
      </label>
      <span v-if="archivo" class="ml-3 break-all text-sm text-slate-700">{{ archivo.name }}</span>
      <p :id="`${id}-ayuda`" class="mt-1 text-xs text-slate-600">{{ ayuda }}</p>
      <p v-if="mensajeError" role="alert" class="mt-1 text-sm font-medium text-red-700">{{ mensajeError }}</p>
    </div>

    <div v-if="pedirAlt">
      <label :for="`${id}-alt`" class="block text-sm font-medium text-slate-700">
        Descripción de la imagen (para quien no puede verla) <span aria-hidden="true" class="text-red-600">*</span>
      </label>
      <textarea
        :id="`${id}-alt`"
        :value="alt"
        rows="2"
        :maxlength="altMax"
        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
        @input="emit('update:alt', ($event.target as HTMLTextAreaElement).value)"
      />
    </div>
  </div>
</template>
