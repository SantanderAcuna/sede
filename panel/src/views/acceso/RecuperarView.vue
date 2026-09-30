<script setup lang="ts">
/**
 * Recuperación de contraseña.
 *
 * El enlace existe en la entrada del panel; esta pantalla lo cubre para que el
 * enlace no lleve a ninguna parte mientras el módulo de identidad no exista.
 */
import { RouterLink } from 'vue-router'

import VistaEnConstruccion from '@/components/feedback/VistaEnConstruccion.vue'
</script>

<template>
  <VistaEnConstruccion
    titulo="Recuperar contraseña"
    subtitulo="Restablecimiento de credenciales"
    mensaje="El restablecimiento llegará con el módulo de identidad."
  >
    <RouterLink
      to="/acceso"
      class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-gov-blue hover:underline"
    >
      Volver a la entrada
    </RouterLink>
  </VistaEnConstruccion>
</template>
