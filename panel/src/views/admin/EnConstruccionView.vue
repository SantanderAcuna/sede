<script setup lang="ts">
/**
 * Pantalla de módulo pendiente.
 *
 * Una sola vista sirve a los dieciocho módulos del menú: el título sale de la
 * ruta (`meta.titulo`), así que añadir un módulo es añadir una línea al
 * enrutador y no un archivo nuevo. Cuando un módulo se implemente, se sustituye
 * el componente de esa ruta y este marcador deja de usarse para ella.
 */
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import VistaEnConstruccion from '@/components/feedback/VistaEnConstruccion.vue'

const ruta = useRoute()
const titulo = computed(() => ruta.meta.titulo ?? 'Módulo')
const subtitulo = computed(() => ruta.meta.subtitulo)
</script>

<template>
  <VistaEnConstruccion :titulo="titulo" :subtitulo="subtitulo" />
</template>
