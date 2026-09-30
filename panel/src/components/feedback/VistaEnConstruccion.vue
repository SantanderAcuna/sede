<script setup lang="ts">
/**
 * Marcador de vista pendiente.
 *
 * Es la pieza que el diseño usaba para toda pantalla aún no construida. Se
 * unifica en un solo componente en vez de repetir dieciocho archivos casi
 * idénticos: el título y el subtítulo llegan por parámetro.
 */
withDefaults(
  defineProps<{
    titulo: string
    subtitulo?: string
    mensaje?: string
  }>(),
  {
    subtitulo: undefined,
    mensaje: 'Vista en construcción · el módulo todavía no está implementado.',
  },
)
</script>

<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-2xl font-bold text-slate-900">{{ titulo }}</h1>
      <p v-if="subtitulo" class="text-sm text-slate-500">{{ subtitulo }}</p>
    </header>

    <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-8 text-center text-slate-500">
      <p class="text-sm">{{ mensaje }}</p>
      <slot />
    </div>
  </div>
</template>
