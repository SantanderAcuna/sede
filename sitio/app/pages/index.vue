<script setup lang="ts">
/**
 * Portada de la Sede Electrónica.
 *
 * Sigue el mapa de la sección 2.5 del expediente para la página de inicio:
 * buscador, carrusel, tarjetas de información y botones.
 *
 * **Sobre el contenido.** Aquí no se inventa nada. Lo que se publica son las
 * tres secciones que el criterio FUN-013 obliga a mostrar —Transparencia y
 * acceso a la información pública, Servicios a la Ciudadanía y Participa— con
 * su nombre real y su enlace real. Lo único que es un marcador es la
 * **fotografía** de las diapositivas: se usa la imagen neutra del propio Kit
 * mientras la Entidad no entregue las suyas, porque una foto de archivo haría
 * pasar por institucional algo que no lo es.
 *
 * Tampoco se anuncia una carta de trámites que no existe: la Sede los publicará
 * cuando el CMS y la migración estén hechos. Poner contenido de relleno en una
 * sede electrónica es peor que no ponerlo, porque parece información oficial.
 *
 * La barra superior, la cabecera, el menú, el pie y el salto al contenido los
 * pone la disposición (`layouts/default.vue`), que es lo que garantiza que
 * estén en todas las páginas y no sólo en ésta.
 */
import type { DiapositivaCarrusel } from '~/components/govco/CarruselGovco.vue'
import type { components } from '~~/types/openapi'

useHead({
  title: 'Sede Electrónica · Alcaldía Distrital de Santa Marta',
  meta: [
    {
      name: 'description',
      content:
        'Sede Electrónica de la Alcaldía Distrital de Santa Marta. Punto de acceso electrónico a los trámites, servicios y canales de atención de la Entidad.',
    },
  ],
})

/**
 * Las diapositivas describen las secciones que de verdad existen y enlazan a
 * ellas. La imagen es la de relleno del Kit, y así se declara en el `alt`: quien
 * no ve la imagen debe saber que es un marcador y no una fotografía de la
 * Entidad.
 */
const diapositivas: DiapositivaCarrusel[] = [
  {
    id: 'transparencia',
    imagen: '/govco/assets/images/fondo-gris-carrusel.jpg',
    alt: 'Marcador de imagen: la Entidad aún no ha entregado la fotografía de esta sección',
    titulo: 'Transparencia y acceso a la información pública',
    descripcion:
      'Información de la Entidad a disposición de cualquier ciudadano, en formatos abiertos y accesibles.',
    enlace: '/transparencia',
  },
  {
    id: 'servicios',
    imagen: '/govco/assets/images/fondo-gris-carrusel.jpg',
    alt: 'Marcador de imagen: la Entidad aún no ha entregado la fotografía de esta sección',
    titulo: 'Servicios a la Ciudadanía',
    descripcion: 'Trámites y servicios de la Alcaldía Distrital, con sus requisitos y canales.',
    enlace: '/servicios',
  },
  {
    id: 'participa',
    imagen: '/govco/assets/images/fondo-gris-carrusel.jpg',
    alt: 'Marcador de imagen: la Entidad aún no ha entregado la fotografía de esta sección',
    titulo: 'Participa',
    descripcion: 'Noticias de la Entidad y portales de los programas transversales a su cargo.',
    enlace: '/participa',
  },
]

/** Las tres secciones obligatorias de FUN-013, para las tarjetas de la portada. */
const secciones = [
  {
    titulo: 'Transparencia',
    descripcion:
      'Acceso a la información pública de la Entidad, en formatos abiertos y con fecha de publicación.',
    enlace: '/transparencia',
  },
  {
    titulo: 'Servicios',
    descripcion:
      'Trámites y servicios de la Alcaldía Distrital. El catálogo se publica a medida que se integra.',
    enlace: '/servicios',
  },
  {
    titulo: 'Participa',
    descripcion: 'Noticias de la Entidad y portales de los programas transversales.',
    enlace: '/participa',
  },
]

/**
 * Los trámites que la portada adelanta (RF-B2-071, FUN-013).
 *
 * El requisito pide que la portada esté orientada a tareas y que **los tres
 * trámites más solicitados estén a dos clics**. El ranking de «más solicitados»
 * necesita la analítica de uso (RF-B2-089), que todavía no existe, así que
 * inventarlo sería publicar una estadística falsa. Lo que sí se puede hacer —y se
 * hace— es traer del catálogo los primeros trámites disponibles con su enlace
 * directo a la ficha: desde la portada, cualquier trámite publicado queda a un
 * clic.
 *
 * Cuando exista la analítica, esta llamada se cambia por la del ranking y el
 * requisito queda cumplido en su letra. Mientras tanto, la sección se rotula
 * «Trámites y servicios» y no «los más solicitados»: no se afirma lo que no se
 * sabe. Si el catálogo no responde, la portada **no** se rompe —es la página más
 * visitada—: el bloque explica que no está disponible y ofrece el catálogo y el
 * buscador.
 */
type TramiteApi = components['schemas']['TramiteItem']

const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')
const traer = useRequestFetch()

const { data: tramitesPortada } = await useAsyncData('portada-tramites', async () => {
  try {
    return await traer<{ data: TramiteApi[] }>(`${urlApi}/tramites`, {
      query: { per_page: 3 },
    })
  } catch {
    return null
  }
})

const tramitesDestacados = computed(() =>
  (tramitesPortada.value?.data ?? []).map((tramite) => ({
    id: tramite.id,
    nombre: tramite.nombre,
    slug: tramite.slug,
    resumen: tramite.resumen ?? null,
  })),
)
</script>

<template>
  <div class="container py-5">
    <h1>Sede Electrónica</h1>
    <p class="lead">
      Punto de acceso electrónico de la Alcaldía Distrital de Santa Marta, conforme al artículo
      14 del Decreto Ley 2106 de 2019.
    </p>

    <!--
      Aquí había un segundo buscador, y se retira: el general vive en la
      cabecera (FUN-011) y ya está en todas las páginas, incluida ésta. Dos
      campos de búsqueda idénticos en la misma pantalla no son una función de
      más, son una duda para quien los usa.
    -->

    <CarruselGovco :diapositivas="diapositivas" class="mt-4" />

    <!--
      Trámites en la portada: la sede se usa para hacer cosas, y hasta ahora la
      portada sólo ofrecía secciones. Con esto, cualquier trámite publicado está a
      un clic desde el inicio (RF-B2-071).
    -->
    <section aria-labelledby="titulo-tramites-portada" class="mt-5">
      <h2 id="titulo-tramites-portada" class="h4 mb-3">Trámites y servicios</h2>

      <p v-if="tramitesDestacados.length === 0" class="mb-2">
        El catálogo de trámites no está disponible en este momento. Puede
        intentarlo de nuevo en unos minutos o buscarlo con el buscador de la
        cabecera.
      </p>
      <template v-else>
        <ul class="lista-portada">
          <li v-for="tramite in tramitesDestacados" :key="tramite.id">
            <NuxtLink :to="`/tramites/${tramite.slug}`" class="enlace-portada">
              {{ tramite.nombre }}
            </NuxtLink>
            <p v-if="tramite.resumen" class="descripcion-portada">
              {{ tramite.resumen }}
            </p>
          </li>
        </ul>
      </template>

      <p class="mt-3 mb-0">
        <NuxtLink to="/tramites">Ver todos los trámites y servicios</NuxtLink>
      </p>
    </section>

    <h2 class="mt-5 mb-4">Secciones de la Sede</h2>

    <div class="row g-4">
      <div v-for="seccion in secciones" :key="seccion.enlace" class="col-md-4">
        <TarjetaInformacionGovco
          tipo="modulo"
          :titulo="seccion.titulo"
          :descripcion="seccion.descripcion"
          :enlace="seccion.enlace"
        />
      </div>
    </div>

    <aside class="mt-5 pt-4 border-top" aria-labelledby="titulo-ayuda">
      <h2 id="titulo-ayuda" class="h5">Ayuda</h2>
      <p class="mb-2">
        Si encuentra un problema de acceso o de accesibilidad en esta sede, repórtelo a la
        Entidad para que se corrija.
      </p>
      <!--
        Se dan los canales y no sólo la invitación: un aviso que pide reportar un
        problema y no dice por dónde es un callejón sin salida (D-48).
      -->
      <ul class="mb-0">
        <li>
          <NuxtLink to="/accesibilidad">Cómo reportar una barrera de accesibilidad</NuxtLink>
        </li>
        <li>
          <NuxtLink to="/atencion">Canales de atención y sedes</NuxtLink>
        </li>
        <li>
          <NuxtLink to="/pqrsd">Peticiones, quejas, reclamos y sugerencias</NuxtLink>
        </li>
      </ul>
    </aside>
  </div>
</template>

<style scoped>
/*
 * Lista de trámites de la portada. Sin viñetas y separada con una línea: son
 * enlaces a fichas, no una enumeración de texto corrido, y la línea ayuda a
 * seguir cada resultado cuando el título ocupa dos renglones en un teléfono.
 */
.lista-portada {
  list-style: none;
  padding: 0;
  margin: 0;
}

.lista-portada li {
  padding: 0.75rem 0;
  border-bottom: 0.0625rem solid #e5e7eb;
}

.lista-portada li:last-child {
  border-bottom: 0;
}

.enlace-portada {
  font-size: 1.0625rem;
  font-weight: 600;
  text-decoration: underline;
}

.descripcion-portada {
  margin: 0.25rem 0 0;
  color: #333;
  max-width: 68ch;
}
</style>
