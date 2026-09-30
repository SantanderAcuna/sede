<script setup lang="ts">
/**
 * Verificación en dos pasos.
 *
 * Pantalla de diseño. No hay verificación real: el módulo de identidad todavía
 * no existe, así que este formulario **no** concede acceso a nada.
 */
import { ref } from 'vue'

import FormField from '@/components/base/FormField.vue'
import BaseButton from '@/components/base/BaseButton.vue'

const codigo = ref('')
</script>

<template>
  <form class="space-y-5" novalidate aria-labelledby="titulo-mfa" @submit.prevent>
    <header class="space-y-1">
      <h1 id="titulo-mfa" class="text-2xl font-bold text-ink">Verificación en dos pasos</h1>
      <p class="text-sm text-ink-muted">Ingresa el código de 6 dígitos de tu app autenticadora.</p>
    </header>

    <FormField
      v-model="codigo"
      label="Código de verificación"
      placeholder="000000"
      autocomplete="one-time-code"
      required
    />

    <BaseButton type="submit" block size="lg">Verificar e ingresar</BaseButton>
  </form>
</template>
