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

const enrutador = useRouter()

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

function alBuscar(termino: string) {
  enrutador.push({ path: '/buscar', query: { q: termino } })
}
</script>

<template>
  <div class="container py-5">
    <h1>Sede Electrónica</h1>
    <p class="lead">
      Punto de acceso electrónico de la Alcaldía Distrital de Santa Marta, conforme al artículo
      14 del Decreto Ley 2106 de 2019.
    </p>

    <BuscadorGovco class="mb-5" @buscar="alBuscar" />

    <CarruselGovco :diapositivas="diapositivas" />

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
      <p class="mb-0">
        Si encuentra un problema de acceso o de accesibilidad en esta sede, repórtelo a la
        Entidad para que se corrija.
      </p>
    </aside>
  </div>
</template>
