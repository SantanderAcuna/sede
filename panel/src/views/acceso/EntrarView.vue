<script setup lang="ts">
/**
 * Entrada al panel.
 *
 * Pantalla de diseño: los campos se enlazan a estado local y el envío **no
 * autentica**, porque el módulo de identidad todavía no existe. Cuando llegue,
 * este formulario pasa a invocar el servicio real y a mostrar el error de la
 * API en `FormField`.
 *
 * Por eso mismo la pantalla **no puede afirmar que protege nada**. Publicaba una
 * garantía de doble factor (Decreto 1078) que hoy es falsa —ni hay sesión ni hay
 * segundo factor— y una promesa de seguridad incumplida es peor que el silencio.
 * En su lugar va el aviso de abajo.
 */
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

import FormField from '@/components/base/FormField.vue'
import BaseButton from '@/components/base/BaseButton.vue'

const usuario = ref('')
const contrasena = ref('')
const recordar = ref(false)
</script>

<template>
  <form class="space-y-5" novalidate aria-labelledby="titulo-acceso" @submit.prevent>
    <header class="space-y-1">
      <h1 id="titulo-acceso" class="text-2xl font-bold text-ink">Iniciar sesión</h1>
      <p class="text-sm text-ink-muted">Acceso al panel administrativo</p>
    </header>

    <FormField
      v-model="usuario"
      label="Usuario o cédula"
      autocomplete="username"
      required
    />

    <FormField
      v-model="contrasena"
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

    <BaseButton type="submit" block size="lg" disabled>Ingresar</BaseButton>

    <!--
      El botón queda deshabilitado porque no hay nada que enviar: un control que
      se puede pulsar y no hace nada es una promesa que la interfaz no cumple.
    -->
    <p
      class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900 ring-1 ring-amber-200"
      role="note"
    >
      <FaIcon icon="triangle-exclamation" class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
      <span>
        Previsualización: el módulo de identidad todavía no está conectado, así que este
        formulario no inicia sesión ni concede acceso. La autenticación con doble factor llegará
        cuando ese módulo exista.
      </span>
    </p>
  </form>
</template>
