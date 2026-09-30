<script setup lang="ts">
/**
 * Declaración de accesibilidad.
 *
 * De las páginas creadas hoy, ésta es la única con contenido que se puede
 * redactar sin inventar nada: la norma aplicable está fijada, y el canal para
 * reportar una barrera es el que la Entidad ya publica en su pie de página.
 *
 * **Lo que esta página NO dice.** No declara un nivel de conformidad verificado
 * ni un resultado de auditoría, porque esa auditoría todavía no se ha publicado.
 * El plan fija qué debe llevar la declaración de conformidad —alcance,
 * herramientas y versiones, responsable, criterios no aplicables, excepciones,
 * hallazgos conocidos con su plan y fecha de la próxima revisión— y ese bloque se
 * añadirá aquí cuando exista. Afirmar hoy que el sitio «cumple AA» sería una
 * afirmación sin respaldo, y en una declaración de accesibilidad eso es
 * exactamente lo que no se puede hacer.
 *
 * **Los datos de contacto** se repiten desde `components/govco/PiePaginaGovco.vue`
 * —la fuente única que esta tarea no toca—: el canal para reportar barreras tiene
 * que estar en la propia declaración, no a un enlace de distancia.
 */
interface Correo {
  etiqueta: string
  direccion: string
}

const correos: Correo[] = [
  {
    etiqueta: 'Correo de atención al ciudadano',
    direccion: 'atencionalciudadano@santamarta.gov.co',
  },
]

const telefonos = [
  { etiqueta: 'Teléfono conmutador', numero: '(+57) 605 420 9600' },
  { etiqueta: 'Línea gratuita nacional', numero: '018000 955 532' },
]

/** Lo que el sitio ofrece hoy, comprobable en cualquier página. */
const prestaciones = [
  {
    titulo: 'Salto al contenido principal',
    detalle:
      'El primer enlace de cada página lleva directamente al contenido, sin recorrer la cabecera ni el menú.',
  },
  {
    titulo: 'Ajuste del tamaño de letra',
    detalle:
      'La barra de accesibilidad permite aumentar o disminuir el tamaño del texto hasta cinco pasos, sin perder contenido.',
  },
  {
    titulo: 'Modo de alto contraste',
    detalle:
      'Un modo de contraste reforzado, con fondo negro, texto blanco y foco amarillo visible.',
  },
  {
    titulo: 'Contenido servido en HTML',
    detalle:
      'Las páginas se generan en el servidor: se pueden leer y navegar aunque el JavaScript no llegue a cargarse.',
  },
  {
    titulo: 'Estructura y navegación',
    detalle:
      'Un solo encabezado de nivel 1 por página, jerarquía de encabezados sin saltos, regiones identificadas y navegación completa por teclado.',
  },
]

useHead({ title: 'Declaración de accesibilidad · Sede Electrónica' })
</script>

<template>
  <div class="container py-5">
    <h1>Declaración de accesibilidad</h1>

    <div class="row">
      <div class="col-lg-8">
        <p class="lead">
          Esta Sede Electrónica se rige por la <strong>Resolución 1519 de 2020</strong>
          del Ministerio de Tecnologías de la Información y las Comunicaciones, en su
          <strong>Anexo 1</strong>, y por las <strong>Pautas de Accesibilidad para el
          Contenido Web (WCAG) 2.1, en su nivel AA</strong>.
        </p>

        <p>
          El nivel AA es exigible en Colombia desde el 1 de enero de 2022. No es una
          recomendación de calidad: el artículo 14 del Decreto Ley 2106 de 2019 hace
          de la accesibilidad un atributo de la sede electrónica, junto con la
          seguridad, la disponibilidad y la interoperabilidad.
        </p>

        <h2 class="h3 mt-5">Qué ofrece este sitio</h2>

        <ul>
          <li v-for="prestacion in prestaciones" :key="prestacion.titulo">
            <strong>{{ prestacion.titulo }}.</strong> {{ prestacion.detalle }}
          </li>
        </ul>

        <h2 class="h3 mt-5">Estado de esta declaración</h2>

        <p>
          Esta página declara la norma que rige el sitio y el canal para reportar
          barreras. Todavía no declara un nivel de conformidad verificado: el informe
          de auditoría —su alcance, las herramientas y versiones empleadas, quién la
          realizó, los criterios que no aplican, las excepciones y los hallazgos
          conocidos con su plan de corrección— se publicará aquí, con la fecha de la
          próxima revisión.
        </p>

        <p class="mb-0">
          Mientras ese informe no esté publicado, esta declaración no acredita por sí
          sola el cumplimiento de un nivel de conformidad.
        </p>

        <h2 class="h3 mt-5">Cómo reportar una barrera de accesibilidad</h2>

        <p>
          Si encuentra una barrera —un contenido que no puede leer, un control que no
          puede usar con el teclado o con un producto de apoyo, un texto con contraste
          insuficiente, una imagen sin descripción— repórtela a la Entidad. Su aviso
          es lo que permite corregirla.
        </p>

        <ul>
          <li v-for="correo in correos" :key="correo.direccion">
            {{ correo.etiqueta }}:
            <!--
              El nombre accesible empieza por el texto visible de la dirección,
              como exige WCAG 2.5.3: quien use control por voz tiene que poder
              decir lo que lee.
            -->
            <a
              :href="`mailto:${correo.direccion}`"
              :aria-label="`${correo.direccion} (abre el programa de correo)`"
            >{{ correo.direccion }}</a>
          </li>
          <li v-for="telefono in telefonos" :key="telefono.etiqueta">
            {{ telefono.etiqueta }}: {{ telefono.numero }}
          </li>
        </ul>

        <p>
          Para atenderlo hace falta saber <strong>qué página</strong> estaba usando,
          <strong>qué intentaba hacer</strong> y <strong>con qué</strong> lo hacía
          —navegador, producto de apoyo y versión—. Si la barrera impide usar un
          formulario, indíquelo también: es el caso que más urge resolver.
        </p>

        <p>
          Los demás canales de la Entidad están en
          <NuxtLink to="/atencion">Canales de atención y sedes</NuxtLink>.
        </p>

        <p class="mt-4 mb-0">
          <NuxtLink class="btn btn-outline-primary" to="/">Volver a la portada</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>
