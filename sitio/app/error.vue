<script setup lang="ts">
import type { NuxtError } from '#app'

/**
 * Página de error del sitio público.
 *
 * Existe porque hasta ahora el sitio devolvía la MISMA página con estado 200 para
 * cualquier dirección: no había ni páginas ni manejo de errores.
 *
 * Nuxt devuelve al cliente el estado que lleva el error, así que una dirección
 * inventada responde 404 de verdad: un buscador no la indexa y un ciudadano sabe
 * que se equivocó de dirección en lugar de creer que el trámite existe.
 *
 * **La disposición se monta aquí a mano.** Nuxt no aplica `layouts/` a este
 * archivo —sustituye a la aplicación entera cuando hay un error—, así que sin
 * este `<NuxtLayout>` el 404 salía sin barra superior, sin cabecera, sin menú,
 * sin pie y sin el enlace de salto al contenido. Y no es un detalle estético:
 * la auditoría de accesibilidad marcaba `document-title` como falta grave porque
 * la página tampoco tenía título, y una página pública sin armazón institucional
 * deja a quien llega ahí sin ninguna forma de seguir navegando salvo el botón de
 * volver, que era lo único que ofrecía.
 */
const props = defineProps<{ error: NuxtError }>()

const esNoEncontrada = computed(() => props.error.statusCode === 404)

useHead({
  title: esNoEncontrada.value
    ? 'Página no encontrada · Sede Electrónica'
    : 'Error · Sede Electrónica',
  // Una página de error no aporta nada a un buscador y no debe indexarse.
  meta: [{ name: 'robots', content: 'noindex, follow' }],
})
</script>

<template>
  <NuxtLayout>
    <div class="container py-5">
      <h1 v-if="esNoEncontrada">Página no encontrada</h1>
      <h1 v-else>No se pudo completar la operación</h1>

      <p v-if="esNoEncontrada">
        La dirección que abrió no corresponde a ninguna página de la Sede Electrónica. Puede que el
        enlace esté mal escrito o que la página se haya movido.
      </p>
      <p v-else>
        Ha ocurrido un error al atender su solicitud. Vuelva a intentarlo en unos minutos.
      </p>

      <!-- El código se muestra, y no un mensaje amable que lo oculte: quien
           reporte el problema necesitará saber qué pasó. -->
      <p>Código: <strong>{{ error.statusCode }}</strong></p>

      <p class="mt-4 mb-0">
        <NuxtLink class="btn btn-outline-primary" to="/">Volver a la portada</NuxtLink>
      </p>
    </div>
  </NuxtLayout>
</template>
