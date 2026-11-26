<script setup lang="ts">
/**
 * Aviso de sesión vencida (T9.8, H43, D-0C.8).
 *
 * Un `419` al guardar significa que la sesión (o su token CSRF) se perdió. Antes la persona
 * seguía «dentro» con un error al guardar y sin saber por qué. Ahora se abre este diálogo,
 * sin navegar y sin tocar lo que escribió: vuelve a entrar con su contraseña y pulsa
 * «Guardar» otra vez.
 *
 * Solo pide la contraseña. La cuenta es la de la sesión y no se puede cambiar: si pudiera,
 * lo escrito por una persona se guardaría con la identidad de otra.
 */
import { computed, ref, watch } from 'vue'

import BaseButton from '@/components/base/BaseButton.vue'
import BaseModal from '@/components/base/BaseModal.vue'
import FormField from '@/components/base/FormField.vue'
import { mensajeDeError } from '@/api/client'
import { useSesionStore } from '@/stores/sesion'

const sesion = useSesionStore()

const password = ref('')
const error = ref<string | null>(null)
const cargando = ref(false)

const abierto = computed({
  get: () => sesion.avisoSesionVencida && sesion.iniciada,
  set: (valor: boolean) => {
    if (!valor) sesion.descartarAvisoSesionVencida()
  },
})

// Cada vez que se abre, el formulario empieza limpio: una contraseña escrita en un aviso
// anterior no debe quedar en memoria.
watch(abierto, (ahora) => {
  if (ahora) {
    password.value = ''
    error.value = null
  }
})

async function entrar() {
  if (cargando.value || !password.value) return

  cargando.value = true
  error.value = null
  try {
    await sesion.reautenticar(password.value)
    password.value = ''
  } catch (e) {
    error.value = mensajeDeError(e, e instanceof Error && e.message ? e.message : undefined)
  } finally {
    cargando.value = false
  }
}
</script>

<template>
  <BaseModal v-model="abierto" title="Tu sesión venció" size="sm" :close-on-backdrop="false">
    <form class="space-y-4" novalidate @submit.prevent="entrar">
      <p class="text-sm text-slate-600">
        Vuelve a entrar para seguir. Lo que escribiste no se perdió: después de entrar, pulsa
        «Guardar» otra vez.
      </p>

      <p class="text-sm text-slate-700">
        Cuenta: <strong>{{ sesion.usuario?.email }}</strong>
      </p>

      <FormField
        v-model="password"
        label="Contraseña"
        type="password"
        autocomplete="current-password"
        required
      />

      <p
        v-if="error"
        class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 ring-1 ring-red-200"
        role="alert"
      >
        {{ error }}
      </p>

      <BaseButton type="submit" block :disabled="cargando || !password">
        <template v-if="cargando">Entrando…</template>
        <template v-else>Volver a entrar</template>
      </BaseButton>
    </form>
  </BaseModal>
</template>
