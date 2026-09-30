<script setup lang="ts">
/**
 * Pantalla de página no encontrada, con salida útil.
 *
 * Usa el marcador del diseño, pero con el texto propio de un 404: decir
 * "vista en construcción" ante una dirección equivocada confundiría.
 */
import { RouterLink } from 'vue-router'

import VistaEnConstruccion from '@/components/feedback/VistaEnConstruccion.vue'
</script>

<template>
  <div class="min-h-screen grid place-items-center bg-slate-50 p-6">
    <div class="w-full max-w-lg">
      <VistaEnConstruccion
        titulo="La página no existe"
        subtitulo="La ruta solicitada no está en el panel"
        mensaje="Compruebe la dirección o vuelva al inicio del panel."
      >
        <RouterLink
          to="/"
          class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-gov-blue hover:underline"
        >
          Volver al inicio
        </RouterLink>
      </VistaEnConstruccion>
    </div>
  </div>
</template>
