<script setup lang="ts">
/**
 * Raíz del panel.
 *
 * Decide la disposición según la ruta. Las rutas de panel traen `AdminLayout`
 * como componente padre del propio enrutador; las de acceso (entrada, doble
 * factor) se envuelven aquí, porque comparten la misma pantalla partida de
 * marca y no necesitan un registro de ruta propio sólo para eso.
 */
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'

import AuthLayout from '@/layouts/AuthLayout.vue'

const ruta = useRoute()
const disposicion = computed(() => ruta.meta.layout ?? 'blank')
</script>

<template>
  <AuthLayout v-if="disposicion === 'auth'">
    <RouterView />
  </AuthLayout>
  <RouterView v-else />
</template>
