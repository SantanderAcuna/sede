<script setup lang="ts">
/**
 * Confirmación de una acción que no se puede deshacer.
 *
 * Reemplaza al doble clic en el mismo botón («Eliminar» → «Confirmar»): quien no conoce el
 * sistema no sabe que debe volver a pulsar, ni qué se va a quitar. Aquí se dice qué, en
 * un diálogo con foco atrapado, y el botón por defecto es el seguro (Cancelar).
 */
import BaseButton from './BaseButton.vue'
import BaseModal from './BaseModal.vue'

withDefaults(
  defineProps<{
    modelValue: boolean
    title: string
    confirmar: string
    cancelar?: string
    cargando?: boolean
    /** Motivo por el que la acción falló; se muestra dentro del diálogo. */
    error?: string | null
  }>(),
  { cancelar: 'Cancelar', cargando: false, error: null },
)

defineEmits<{ 'update:modelValue': [valor: boolean]; confirmar: [] }>()
</script>

<template>
  <BaseModal :model-value="modelValue" :title="title" size="sm" @update:model-value="$emit('update:modelValue', $event)">
    <div class="space-y-3 text-sm text-slate-700">
      <slot />
      <p v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-3 text-red-800" role="alert">{{ error }}</p>
    </div>
    <template #footer>
      <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <BaseButton variant="outline" :disabled="cargando" @click="$emit('update:modelValue', false)">{{ cancelar }}</BaseButton>
        <BaseButton variant="danger" :loading="cargando" :disabled="cargando" @click="$emit('confirmar')">{{ confirmar }}</BaseButton>
      </div>
    </template>
  </BaseModal>
</template>
