<script setup lang="ts">
/**
 * Una noticia — `/noticias/{slug}` (M01-6, RF-01-013).
 *
 * Título, fecha, imagen con su `alt` y el cuerpo en párrafos. El cuerpo es **texto
 * plano**: se pinta párrafo a párrafo con interpolación, nunca como HTML, así que
 * nada de lo que se escriba en el panel puede ejecutarse en el sitio.
 *
 * Como en la ficha de trámite, el `404` y el fallo de la API se distinguen: una
 * noticia que no existe (o aún no se publica) responde `404` de verdad; que la API
 * no conteste no se disfraza de «no existe».
 */
import { useMetadatosComparticion } from '~/composables/useMetadatosComparticion'
import { useFichaPrecargada, leerFicha } from '~/composables/useFichaPrecargada'
import { fichaDeRuta } from '~/utils/miga'
import { fechaLarga, parrafosDeNoticia, urlDeImagen, type NoticiaResumen } from '~/utils/noticias'

const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')
const traer = useRequestFetch()
const slug = String(useRoute().params.slug ?? '')

const precargada = useFichaPrecargada()
const ficha = fichaDeRuta(useRoute().path)

/*
 * Si el middleware ya leyó esta noticia para la miga de pan, se reutiliza esa lectura:
 * una sola consulta a la API por visita. Si no (fallo previo, o ruta sin pasar por él),
 * se lee aquí.
 */
const { data, status, error } = await useAsyncData<{ data: NoticiaResumen } | null>(
  `noticia-${slug}`,
  async () => {
    if (precargada.value?.clave === `noticia-${slug}`) {
      return precargada.value.respuesta as { data: NoticiaResumen } | null
    }
    if (!ficha) return null
    return await leerFicha<{ data: NoticiaResumen }>(traer as never, urlApi, ficha)
  },
)

const noticia = computed(() => data.value?.data ?? null)

if (!error.value && status.value === 'success' && noticia.value === null) {
  throw createError({ statusCode: 404, statusMessage: 'Not Found', fatal: true })
}

const imagen = computed(() => (noticia.value ? urlDeImagen(noticia.value.imagen) : null))
const fecha = computed(() => (noticia.value ? fechaLarga(noticia.value.fecha_publicacion) : ''))
const parrafos = computed(() => parrafosDeNoticia(noticia.value?.contenido ?? ''))

useMetadatosComparticion({
  titulo: () => noticia.value?.titulo ?? 'Noticias',
  descripcion: () => noticia.value?.descripcion_corta ?? undefined,
})

useHead({
  title: () => `${noticia.value?.titulo ?? 'Noticia'} · Noticias · Sede Electrónica`,
  meta: [{ name: 'description', content: () => noticia.value?.descripcion_corta ?? 'Noticia de la Alcaldía Distrital de Santa Marta.' }],
})
</script>

<template>
  <div class="container py-5">
    <p v-if="error" role="alert" class="mb-0">
      No se pudo cargar la noticia en este momento. Puede intentarlo de nuevo en unos minutos.
    </p>

    <article v-else-if="noticia" class="noticia">
      <h1>{{ noticia.titulo }}</h1>
      <p class="noticia__fecha">
        <time :datetime="noticia.fecha_publicacion">{{ fecha }}</time>
      </p>
      <img
        v-if="imagen"
        :src="imagen"
        :alt="noticia.imagen?.alt ?? ''"
        class="noticia__imagen"
        decoding="async"
      >
      <p class="lead">{{ noticia.descripcion_corta }}</p>
      <p v-for="(parrafo, i) in parrafos" :key="i">{{ parrafo }}</p>
      <p class="mt-4 mb-0"><NuxtLink to="/noticias">Volver a las noticias</NuxtLink></p>
    </article>
  </div>
</template>

<style scoped>
.noticia {
  max-width: 68ch;
}

.noticia__fecha {
  margin: 0 0 var(--espacio-4);
  color: var(--color-texto);
  font-size: 0.875rem;
}

/* Reserva su caja y se recorta al mostrar, como en la tarjeta. */
.noticia__imagen {
  display: block;
  width: 100%;
  aspect-ratio: 16 / 9;
  object-fit: cover;
  margin-bottom: var(--espacio-4);
  border-radius: var(--radio-md);
}
</style>
