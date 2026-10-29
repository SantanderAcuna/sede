<script setup lang="ts">
/**
 * Dónde se dibujan los avisos emergentes. Va **una sola vez**, en la disposición
 * (`layouts/default.vue`); quien quiere avisar no lo instancia, llama a
 * `useToast().mostrar`.
 *
 * El contenedor existe siempre, aunque esté vacío: un `role="status"` que se
 * inserta ya con el texto dentro lo anuncian de forma irregular algunos lectores
 * de pantalla, mientras que un contenedor que ya estaba en el documento no da
 * ese problema. **HIPÓTESIS** (no se probó con lector de pantalla real); es la
 * razón de que se prefiera esta forma.
 */
import { useToast } from '../../composables/useToast'
import ToastGovco from './ToastGovco.vue'

const { avisos, cerrar } = useToast()
</script>

<template>
  <div class="container-toast-interactivo contenedor-toasts-govco">
    <ToastGovco
      v-for="aviso in avisos"
      :key="aviso.id"
      :tipo="aviso.tipo"
      :titulo="aviso.titulo"
      :mensaje="aviso.mensaje"
      :duracion-ms="aviso.duracionMs"
      @cerrar="cerrar(aviso.id)"
    />
  </div>
</template>
