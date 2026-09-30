<script setup lang="ts">
/**
 * Pantalla de «sin permiso».
 *
 * Existe para que la persona sepa qué pasa y qué puede hacer, en lugar de
 * recibir un error en crudo que no explica nada. Recuerda además que el panel
 * no es el control de seguridad: el backend vuelve a comprobar el permiso.
 */
import { RouterLink } from 'vue-router'

import VistaEnConstruccion from '@/components/feedback/VistaEnConstruccion.vue'
</script>

<template>
  <div class="min-h-screen grid place-items-center bg-slate-50 p-6">
    <div class="w-full max-w-lg">
      <VistaEnConstruccion
        titulo="No tiene permiso para ver esta página"
        subtitulo="Acceso restringido"
        mensaje="Su cuenta no tiene habilitada esta sección. Si cree que es un error, contacte con la entidad."
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
