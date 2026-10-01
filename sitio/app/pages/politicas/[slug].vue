<script setup lang="ts">
/**
 * Las cinco políticas obligatorias del pie de página (SEG-006).
 *
 * Son un **conjunto cerrado**, no una lista abierta de páginas: sus slugs salen
 * de los nombres que el expediente fija y del texto con el que el pie las
 * enlaza. Por eso un slug que no está en el catálogo no es «una política todavía
 * sin contenido», es una dirección inventada, y se responde con un 404 de verdad
 * en lugar de servir una página vacía con estado 200.
 *
 * **El 404 se decide aquí, en el montaje de la página.** Es donde vive el estado
 * HTTP: una dirección equivocada tiene que responder 404 al buscador y al
 * ciudadano, no una página que parezca correcta. Lo mismo hace la ruta comodín
 * `[...ruta].vue`, y por el mismo motivo.
 *
 * Ninguna de las cinco tiene documento publicado todavía: el propio pie lo
 * advierte. La Entidad tiene además un incumplimiento abierto en esta materia —la
 * política de derechos de autor no existe—, así que inventar aquí un texto legal
 * sería lo último que conviene hacer.
 */
/** El catálogo canónico: slug, rótulo y estado de publicación (fuente única). */
import { POLITICAS as POLITICAS_DEL_SITIO } from '~/config/sitemap'
import { useMetadatosComparticion } from '~/composables/useMetadatosComparticion'

interface Politica {
  /** Nombre con el que la política se publica y se enlaza desde el pie. */
  titulo: string
  /** Qué contendrá el documento cuando la Entidad lo publique. */
  proposito: string
}

/**
 * Lo que dirá cada política. **La prosa vive aquí**: es contenido de esta página
 * y nadie más lo necesita.
 *
 * Lo que **no** vive aquí es el slug ni el nombre con el que se enlaza. Eso se
 * declara una sola vez en `config/sitemap.ts` —que es también quien decide si la
 * política se anuncia al buscador— y lo consumen el pie y el `sitemap.xml`. Antes
 * estaba escrito en los tres sitios a la vez, que es la forma más barata de que un
 * día el pie llame a una política de una manera y esta página de otra (D-31).
 */
const PROPOSITOS: Record<string, string> = {
  'terminos-y-condiciones-de-uso':
    'Las condiciones que rigen el uso de este sitio y de los servicios digitales de la Entidad, y las obligaciones de quien los utiliza.',
  'seguridad-y-privacidad':
    'Las medidas con las que la Entidad protege la información del sitio y la privacidad de quien lo usa, y el tratamiento que da a los registros de acceso.',
  'proteccion-y-tratamiento-de-datos-personales':
    'La política de tratamiento de datos personales de la Entidad conforme a la Ley 1581 de 2012: finalidades, derechos de los titulares y el procedimiento para ejercerlos.',
  'uso-de-cookies':
    'Qué cookies usa el sitio, con qué finalidad y durante cuánto tiempo, y cómo se administra el consentimiento de quien navega.',
  'derechos-de-autor-y-uso-sobre-contenidos':
    'La titularidad de los contenidos publicados y las condiciones en que pueden reutilizarse, con la licencia que los acompaña y la forma de citarlos.',
}

/**
 * El catálogo que sirve esta página: el slug y el nombre salen de la fuente única
 * y aquí sólo se les añade la prosa. Así es imposible que esta página publique una
 * política que el pie no enlace, o al revés.
 */
const POLITICAS: Record<string, Politica> = Object.fromEntries(
  POLITICAS_DEL_SITIO.map((politica) => [
    politica.slug,
    {
      titulo: politica.etiqueta,
      proposito:
        PROPOSITOS[politica.slug] ??
        'La Entidad publicará aquí el documento de esta política con su acto administrativo de adopción.',
    },
  ]),
)

const ruta = useRoute()

/**
 * `params` puede traer un arreglo cuando la ruta se declara con comodines; se
 * normaliza a cadena para poder consultar el catálogo.
 */
function primerValor(valor: string | string[] | undefined): string {
  const primero = Array.isArray(valor) ? valor[0] : valor
  return (primero ?? '').trim()
}

const slug = computed<string>(() => primerValor(ruta.params.slug))
const politica = computed<Politica | null>(() => POLITICAS[slug.value] ?? null)

if (politica.value === null) {
  throw createError({
    statusCode: 404,
    // La frase viaja en la línea de estado del protocolo, que sólo admite ASCII
    // (es el mismo motivo por el que `[...ruta].vue` usa «Not Found»).
    statusMessage: 'Not Found',
    fatal: true,
  })
}

useHead({
  title: computed(() =>
    politica.value === null
      ? 'Políticas del sitio · Sede Electrónica'
      : `${politica.value.titulo} · Sede Electrónica`,
  ),
})

/*
 * La vista previa de una política compartida dice cuál es (D-29). La descripción
 * es la misma que la página publica como propósito: no se escribe una nueva para
 * la ocasión.
 */
useMetadatosComparticion({
  titulo: () => politica.value?.titulo ?? 'Políticas del sitio',
  descripcion: () => politica.value?.proposito,
})

/**
 * Las demás políticas, para que quien llega a una pueda pasar a las otras. Se
 * excluye la actual: un enlace a la página en la que ya se está no ayuda a nadie.
 */
const otras = computed(() =>
  Object.entries(POLITICAS)
    .filter(([clave]) => clave !== slug.value)
    .map(([clave, valor]) => ({ slug: clave, titulo: valor.titulo })),
)
</script>

<template>
  <div v-if="politica" class="container py-5">
    <h1>{{ politica.titulo }}</h1>

    <div class="row">
      <div class="col-lg-8">
        <p class="lead">{{ politica.proposito }}</p>

        <div class="aviso-preparacion" role="status">
          <p class="mb-0">
            Este documento está en preparación. La política se publicará aquí con su
            texto completo y con el acto administrativo que la adopta, para que pueda
            citarse y verificarse.
          </p>
        </div>

        <h2 class="h4 mt-5">Las demás políticas</h2>
        <ul>
          <li v-for="otra in otras" :key="otra.slug">
            <NuxtLink :to="`/politicas/${otra.slug}`">{{ otra.titulo }}</NuxtLink>
          </li>
        </ul>

        <p class="mt-4 mb-0">
          <NuxtLink class="btn btn-outline-primary" to="/">Volver a la portada</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/*
  Aviso de documento en preparación. Mismos tokens y mismo criterio de contraste
  que el aviso de `SeccionEnPreparacion`: el amarillo institucional
  (`--govcolor-vis-vis`) con el Matterhorn para el texto da 7,6:1, por encima del
  4,5:1 que exige WCAG 2.1 AA.
*/
.aviso-preparacion {
  margin-top: 1.5rem;
  padding: 1rem 1.25rem;
  border-left: 4px solid var(--govcolor-golden-brown, #9d7700);
  background-color: var(--govcolor-vis-vis, #fee697);
  color: var(--govcolor-matterhorn, #4c4c4c);
}
</style>
