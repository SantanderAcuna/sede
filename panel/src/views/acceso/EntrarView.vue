<script setup lang="ts">
/**
 * Entrada al panel.
 *
 * Formulario de autenticación que conecta con el backend:
 * POST /api/v1/panel/login
 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import FormField from '@/components/base/FormField.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import { useSesionStore } from '@/stores/sesion'
import { esErrorApi } from '@/services/http'

const router = useRouter()
const sesion = useSesionStore()

const email = ref('')
const password = ref('')
const recordar = ref(false)
const error = ref<string | null>(null)
const cargando = ref(false)

async function handleSubmit() {
  if (cargando.value) return

  cargando.value = true
  error.value = null

  try {
    await sesion.iniciarSesion({ email: email.value, password: password.value })
    router.push({ name: 'panel.inicio' })
  } catch (e) {
    if (esErrorApi(e)) {
      error.value = e.message
    } else {
      error.value = 'No se pudo conectar con el servidor.'
    }
  } finally {
    cargando.value = false
  }
}
</script>

<template>
  <form class="space-y-5" novalidate aria-labelledby="titulo-acceso" @submit.prevent="handleSubmit">
    <header class="space-y-1">
      <h1 id="titulo-acceso" class="text-2xl font-bold text-ink">Iniciar sesión</h1>
      <p class="text-sm text-ink-muted">Acceso al panel administrativo</p>
    </header>

    <FormField
      v-model="email"
      label="Correo electrónico"
      type="email"
      autocomplete="username"
      required
    />

    <FormField
      v-model="password"
      label="Contraseña"
      type="password"
      autocomplete="current-password"
      required
    />

    <div class="flex items-center justify-between text-sm">
      <label class="inline-flex items-center gap-2 text-ink-muted">
        <input
          v-model="recordar"
          type="checkbox"
          class="h-4 w-4 rounded border-line-strong text-gov-blue focus:ring-gov-blue"
        />
        Recordarme
      </label>
      <RouterLink to="/recuperar" class="text-gov-blue hover:underline">
        ¿Olvidaste tu contraseña?
      </RouterLink>
    </div>

    <BaseButton
      type="submit"
      block
      size="lg"
      :disabled="cargando || !email || !password"
    >
      <template v-if="cargando">Iniciando sesión…</template>
      <template v-else>Ingresar</template>
    </BaseButton>

    <p
      v-if="error"
      class="flex items-start gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 ring-1 ring-red-200"
      role="alert"
    >
      <FaIcon icon="circle-exclamation" class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
      <span>{{ error }}</span>
    </p>
  </form>
</template>
