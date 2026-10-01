<script setup lang="ts">
/**
 * Mapa del sitio.
 *
 * No es una lista decorativa: se construye **sólo con las rutas que existen de
 * verdad** y dice cuáles de ellas ya publican contenido y cuáles están en
 * preparación. Un mapa del sitio que anuncie páginas inexistentes manda al
 * ciudadano a un 404 desde la misma página que debería orientarlo.
 *
 * Las secciones que aún no tienen contenido no se omiten: existen, explican qué
 * publicarán y enlazan a la portada, así que figurar aquí es exacto. Lo que sí se
 * omite es la verificación de documentos (`/verificar/[codigo]`), porque cada
 * dirección de esa familia exige el código de un documento concreto y no hay una
 * entrada que enlazar; se deja dicho en el texto de la página.
 *
 * Este mapa es también la lista que alimenta el `sitemap.xml` cuando se genere
 * desde el contenido publicado, y no desde una lista fija escrita a mano.
 */
interface Entrada {
  /** Texto visible del enlace, igual al título de la página de destino. */
  texto: string
  /** Ruta pública dentro de este sitio. */
  ruta: string
  /** Si su contenido ya está publicado o todavía se está integrando. */
  publicada: boolean
}

interface Grupo {
  titulo: string
  entradas: Entrada[]
}

const grupos: Grupo[] = [
  {
    titulo: 'Portada y secciones principales',
    entradas: [
      { texto: 'Sede Electrónica', ruta: '/', publicada: true },
      {
        texto: 'Transparencia y acceso a la información pública',
        ruta: '/transparencia',
        // Publicada de verdad desde que la sección tiene sus nueve categorías y
        // 333 documentos. Estaba marcada como no publicada cuando era un stub, y
        // dejarlo así haría que el propio mapa del sitio mintiera sobre el sitio.
        publicada: true,
      },
      { texto: 'Servicios a la Ciudadanía', ruta: '/servicios', publicada: false },
      { texto: 'Participa', ruta: '/participa', publicada: false },
      { texto: 'Noticias', ruta: '/noticias', publicada: false },
      {
        texto: 'Portales de programas transversales',
        ruta: '/portales',
        publicada: false,
      },
    ],
  },
  {
    titulo: 'Trámites y solicitudes',
    entradas: [
      { texto: 'Trámites y servicios', ruta: '/tramites', publicada: false },
      {
        texto: 'Peticiones, quejas, reclamos, sugerencias y denuncias (PQRSD)',
        ruta: '/pqrsd',
        publicada: false,
      },
      {
        texto: 'Seguimiento de una solicitud',
        ruta: '/seguimiento',
        publicada: false,
      },
    ],
  },
  {
    titulo: 'Atención a la ciudadanía',
    entradas: [
      { texto: 'Canales de atención y sedes', ruta: '/atencion', publicada: true },
      {
        texto: 'Declaración de accesibilidad',
        ruta: '/accesibilidad',
        publicada: true,
      },
      { texto: 'Buscar en la Sede', ruta: '/buscar', publicada: false },
    ],
  },
  {
    titulo: 'Políticas del sitio',
    entradas: [
      {
        texto: 'Términos y condiciones de uso',
        ruta: '/politicas/terminos-y-condiciones-de-uso',
        publicada: false,
      },
      {
        texto: 'Seguridad y privacidad',
        ruta: '/politicas/seguridad-y-privacidad',
        publicada: false,
      },
      {
        texto: 'Protección y tratamiento de datos personales',
        ruta: '/politicas/proteccion-y-tratamiento-de-datos-personales',
        publicada: false,
      },
      {
        texto: 'Uso de cookies',
        ruta: '/politicas/uso-de-cookies',
        publicada: false,
      },
      {
        texto: 'Derechos de autor y uso sobre contenidos',
        ruta: '/politicas/derechos-de-autor-y-uso-sobre-contenidos',
        publicada: false,
      },
    ],
  },
  {
    titulo: 'Esta página',
    entradas: [{ texto: 'Mapa del sitio', ruta: '/mapa-del-sitio', publicada: true }],
  },
]

useHead({ title: 'Mapa del sitio · Sede Electrónica' })
</script>

<template>
  <div class="container py-5">
    <h1>Mapa del sitio</h1>

    <div class="row">
      <div class="col-lg-8">
        <p class="lead">
          Todas las direcciones públicas de esta Sede Electrónica, agrupadas por
          sección.
        </p>

        <p>
          Las páginas marcadas <em>(en preparación)</em> ya existen y explican qué
          publicarán cuando su contenido esté integrado; no son enlaces rotos, pero
          tampoco publican todavía la información que anuncian. El buscador está en
          esa misma situación: se puede usar, aunque su índice de contenido se
          construye a medida que se publican los trámites y los documentos de la
          Entidad.
        </p>

        <p>
          La verificación pública de documentos no figura en este mapa porque cada
          dirección de esa familia lleva el código de un documento concreto: se abre
          desde el código impreso en el documento y no desde aquí.
        </p>
      </div>
    </div>

    <nav aria-label="Secciones del sitio">
      <section v-for="grupo in grupos" :key="grupo.titulo" class="mt-5">
        <h2 class="h3">{{ grupo.titulo }}</h2>

        <ul>
          <li v-for="entrada in grupo.entradas" :key="entrada.ruta">
            <NuxtLink :to="entrada.ruta">{{ entrada.texto }}</NuxtLink>
            <!--
              El estado va en su propio elemento y no con `v-if` sobre el `li`, que
              lleva el `v-for`: van juntos sólo cuando están en el mismo elemento.
            -->
            <span v-if="!entrada.publicada" class="estado-preparacion">(en preparación)</span>
          </li>
        </ul>
      </section>
    </nav>

    <p class="mt-5 mb-0">
      <NuxtLink class="btn btn-outline-primary" to="/">Volver a la portada</NuxtLink>
    </p>
  </div>
</template>

<style scoped>
/*
  El estado de cada entrada. Se usa el Matterhorn del Kit
  (`--govcolor-matterhorn`, 8,59:1 sobre blanco), no un gris más claro: es texto
  informativo y tiene que leerse, así que no puede bajar de 4,5:1.
*/
.estado-preparacion {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-family: 'Verdana-Regular', system-ui, sans-serif;
  font-size: 15px;
}
</style>
