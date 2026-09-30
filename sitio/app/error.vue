<script setup lang="ts">
import type { NuxtError } from '#app'

/**
 * Página de error del sitio público.
 *
 * Existe porque hasta ahora el sitio devolvía la MISMA página con estado 200 para
 * cualquier dirección: no había ni páginas ni manejo de errores. Comprobado contra
 * el despliegue, `/esto-no-existe-12345/` respondía 200.
 *
 * Nuxt devuelve al cliente el estado que lleva el error, así que una dirección
 * inventada responde 404 de verdad: un buscador no la indexa y un ciudadano sabe
 * que se equivocó de dirección en lugar de creer que el trámite existe.
 */
const props = defineProps<{ error: NuxtError }>()

const esNoEncontrada = computed(() => props.error.statusCode === 404)
</script>

<template>
  <div>
    <header>
      <h1 v-if="esNoEncontrada">Página no encontrada</h1>
      <h1 v-else>No se pudo completar la operación</h1>
    </header>

    <main>
      <p v-if="esNoEncontrada">
        La dirección que abrió no corresponde a ninguna página de la Sede
        Electrónica. Puede que el enlace esté mal escrito o que la página se haya
        movido.
      </p>
      <p v-else>
        Ha ocurrido un error al atender su solicitud. Vuelva a intentarlo en unos
        minutos.
      </p>

      <!-- El código se muestra, y no un mensaje amable que lo oculte: quien
           reporte el problema necesitará saber qué pasó. -->
      <p>
        Código: <strong>{{ error.statusCode }}</strong>
      </p>

      <p>
        <NuxtLink to="/">Volver a la portada</NuxtLink>
      </p>
    </main>
  </div>
</template>
