<script setup lang="ts">
/** Lista desplegable con etiqueta visible, ayuda y error asociados (misma apariencia que `FormField`). */
import { computed, useId } from 'vue'

interface Opcion {
  valor: string
  etiqueta: string
}

const props = withDefaults(
  defineProps<{
    modelValue: string
    label: string
    opciones: Opcion[]
    required?: boolean
    error?: string
    hint?: string
    /** Texto de la opción vacía. */
    vacio?: string
  }>(),
  { required: false, error: undefined, hint: undefined, vacio: 'Seleccione…' },
)
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const id = useId()
const describedBy = computed(() => {
  const ids: string[] = []
  if (props.hint) ids.push(`${id}-hint`)
  if (props.error) ids.push(`${id}-error`)
  return ids.join(' ') || undefined
})
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label :for="id" class="text-sm font-medium text-slate-700">
      {{ label }}<span v-if="required" class="ml-0.5 text-red-500" aria-hidden="true">*</span>
    </label>
    <select
      :id="id"
      :value="modelValue"
      :required="required"
      :aria-invalid="Boolean(error) || undefined"
      :aria-describedby="describedBy"
      :class="[
        'h-10 w-full rounded-lg border bg-white px-3 text-sm text-slate-900 focus:outline-none focus:ring-2',
        error ? 'border-red-300 focus:border-red-500 focus:ring-red-200' : 'border-slate-300 focus:border-gov-blue focus:ring-gov-blue/30',
      ]"
      @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option value="">{{ vacio }}</option>
      <option v-for="o in opciones" :key="o.valor" :value="o.valor">{{ o.etiqueta }}</option>
    </select>
    <p v-if="hint && !error" :id="`${id}-hint`" class="text-xs text-slate-500">{{ hint }}</p>
    <p v-if="error" :id="`${id}-error`" class="text-xs font-medium text-red-700" role="alert">{{ error }}</p>
  </div>
</template>
