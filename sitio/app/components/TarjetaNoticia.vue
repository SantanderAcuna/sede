<script setup lang="ts">
/**
 * Tarjeta de una noticia (M01-6, RF-01-013): imagen con su `alt`, título,
 * descripción, fecha y un solo enlace al detalle.
 *
 * Mismo lenguaje que las tarjetas de trámite de la portada —borde celeste del
 * Kit, filete cobalto, `stretched-link`— y sus mismos tokens: no hay un color
 * nuevo. Es **un solo enlace** (una parada de tabulador por noticia; el lector
 * de pantalla oye el título y no la tarjeta entera). El título no se recorta: el
 * requisito admite hasta 150 caracteres.
 *
 * La imagen reserva su caja 16:9 antes de cargar (no salta la maquetación) y se
 * recorta al mostrar con `object-fit: cover`, sin deformarse. Si la noticia no
 * tiene imagen no se inventa una.
 */
import { computed } from 'vue'

import { fechaLarga, urlDeImagen, type NoticiaResumen } from '~/utils/noticias'

const props = defineProps<{ noticia: NoticiaResumen }>()

const imagen = computed(() => urlDeImagen(props.noticia.imagen))
const fecha = computed(() => fechaLarga(props.noticia.fecha_publicacion))
</script>

<template>
  <article class="tarjeta-noticia">
    <img
      v-if="imagen"
      :src="imagen"
      :alt="noticia.imagen?.alt ?? ''"
      class="tarjeta-noticia__imagen"
      loading="lazy"
      decoding="async"
    />
    <div class="tarjeta-noticia__cuerpo">
      <h3 class="tarjeta-noticia__titulo">
        <NuxtLink :to="`/noticias/${noticia.slug}`" class="stretched-link">
          {{ noticia.titulo }}
        </NuxtLink>
      </h3>
      <p class="tarjeta-noticia__fecha">
        <time :datetime="noticia.fecha_publicacion">{{ fecha }}</time>
      </p>
      <p class="tarjeta-noticia__descripcion">{{ noticia.descripcion_corta }}</p>
    </div>
  </article>
</template>

<style scoped>
.tarjeta-noticia {
  position: relative; /* ancla el área pulsable del enlace extendido */
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  background: var(--alcaldia-blanco);
  border: 0.0625rem solid var(--tarjeta-tramite-borde);
  border-top: 0.25rem solid var(--tarjeta-tramite-filete);
  border-radius: var(--radio-md);
  transition: background-color 0.15s ease, box-shadow 0.15s ease;
}

.tarjeta-noticia:hover {
  background: var(--tarjeta-tramite-fondo-hover);
  box-shadow: var(--tarjeta-tramite-sombra);
}

/* El foco rodea toda la tarjeta, no sólo la línea del título. */
.tarjeta-noticia:has(a:focus-visible) {
  outline: 0.1875rem solid var(--color-enlace);
  outline-offset: 0.125rem;
}

.tarjeta-noticia__imagen {
  display: block;
  width: 100%;
  aspect-ratio: 16 / 9;
  object-fit: cover;
}

.tarjeta-noticia__cuerpo {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-2);
  padding: var(--espacio-4) var(--espacio-5) var(--espacio-5);
}

.tarjeta-noticia__titulo {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  line-height: 1.35;
}

.tarjeta-noticia__titulo a {
  color: var(--color-enlace);
  text-decoration: none;
}

.tarjeta-noticia:hover .tarjeta-noticia__titulo a {
  text-decoration: underline;
}

.tarjeta-noticia__fecha,
.tarjeta-noticia__descripcion {
  margin: 0;
  color: var(--color-texto);
  line-height: 1.5;
}

.tarjeta-noticia__fecha {
  font-size: 0.875rem;
}

.tarjeta-noticia__descripcion {
  font-size: 0.9375rem;
}
</style>
