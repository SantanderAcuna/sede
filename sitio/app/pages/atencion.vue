<script setup lang="ts">
/**
 * Canales de atención y sedes.
 *
 * **De dónde salen estos datos.** No se estrenan aquí: son los mismos que el pie
 * de página del sitio ya publica, verificados contra el portal oficial de la
 * Entidad el 2026-09-30 y adoptados en el ADR-0006. La página existe porque quien
 * busca «cómo contactar a la Alcaldía» no tiene por qué adivinar que la respuesta
 * está al final de cada página.
 *
 * **Fuente única, hoy duplicada a propósito.** Los valores viven en los
 * predeterminados de `components/govco/PiePaginaGovco.vue`, que esta tarea no
 * toca. Si la Entidad cambia un canal hay que cambiarlo en los dos archivos: es
 * la contrapartida de no haber extraído todavía los datos de la Entidad a un
 * módulo compartido. Queda anotado para que no se pierda.
 *
 * **No se añade ningún canal que no esté ya publicado.** Un teléfono de relleno
 * en una página de atención al ciudadano no es un hueco: es una llamada perdida.
 *
 * Los trámites en línea —radicación, PQRSD y seguimiento— todavía no existen.
 * Se enlazan a sus páginas, que lo dicen con claridad, en lugar de callarlos: el
 * ciudadano tiene derecho a saber qué puede hacer hoy y qué no.
 */
interface Telefono {
  /** Etiqueta del canal, con el nombre que le da el pie. */
  etiqueta: string
  /** Número tal como lo publica la Entidad. */
  numero: string
}

interface Correo {
  etiqueta: string
  /** Dirección de correo; es también el texto visible del enlace. */
  direccion: string
}

const sede = {
  nombre: 'Sede principal',
  direccion: 'Calle 14 n.º 2-49, Palacio Municipal. Santa Marta, Magdalena, Colombia',
  codigoPostal: '470004',
  horario: 'Lunes a viernes, de 8:00 a. m. a 12:00 m. y de 2:00 p. m. a 6:00 p. m.',
}

const telefonos: Telefono[] = [
  { etiqueta: 'Teléfono conmutador', numero: '(+57) 605 420 9600' },
  { etiqueta: 'Línea gratuita nacional', numero: '018000 955 532' },
  { etiqueta: 'Línea anticorrupción', numero: '(+57) 605 4351719' },
]

const correos: Correo[] = [
  {
    etiqueta: 'Correo de atención al ciudadano',
    direccion: 'atencionalciudadano@santamarta.gov.co',
  },
  {
    etiqueta: 'Correo de notificaciones judiciales',
    direccion: 'notificacionesalcaldiadistrital@santamarta.gov.co',
  },
]

/** Los servicios en línea que la Sede ya anuncia, cada uno con su estado real. */
const serviciosEnLinea = [
  {
    titulo: 'Trámites y servicios',
    ruta: '/tramites',
    estado: 'El catálogo está en preparación: todavía no se puede consultar ni iniciar un trámite en línea.',
  },
  {
    titulo: 'PQRSD',
    ruta: '/pqrsd',
    estado: 'El formulario está en preparación: todavía no se puede radicar una PQRSD por este medio.',
  },
  {
    titulo: 'Seguimiento de una solicitud',
    ruta: '/seguimiento',
    estado: 'La consulta por número de radicado está en preparación.',
  },
]

useHead({ title: 'Canales de atención y sedes · Sede Electrónica' })
</script>

<template>
  <div class="container py-5">
    <h1>Canales de atención y sedes</h1>

    <div class="row">
      <div class="col-lg-8">
        <p class="lead">
          Estos son los canales por los que la Alcaldía Distrital de Santa Marta
          atiende a la ciudadanía. Son los mismos datos que publica el pie de página
          de este sitio.
        </p>
      </div>
    </div>

    <h2 class="h3 mt-5">Sede principal</h2>

    <dl class="row">
      <dt class="col-sm-4">Dirección</dt>
      <dd class="col-sm-8">{{ sede.direccion }}</dd>

      <dt class="col-sm-4">Código postal</dt>
      <dd class="col-sm-8">{{ sede.codigoPostal }}</dd>

      <dt class="col-sm-4">Horario de atención</dt>
      <dd class="col-sm-8">{{ sede.horario }}</dd>
    </dl>

    <h2 class="h3 mt-5">Canales telefónicos</h2>

    <dl class="row">
      <template v-for="telefono in telefonos" :key="telefono.etiqueta">
        <dt class="col-sm-4">{{ telefono.etiqueta }}</dt>
        <dd class="col-sm-8">{{ telefono.numero }}</dd>
      </template>
    </dl>

    <h2 class="h3 mt-5">Canales electrónicos</h2>

    <dl class="row">
      <template v-for="correo in correos" :key="correo.direccion">
        <dt class="col-sm-4">{{ correo.etiqueta }}</dt>
        <dd class="col-sm-8">
          <!--
            El nombre accesible empieza por el texto visible de la dirección, como
            exige WCAG 2.5.3: quien use control por voz tiene que poder decir lo
            que lee.
          -->
          <a
            :href="`mailto:${correo.direccion}`"
            :aria-label="`${correo.direccion} (abre el programa de correo)`"
          >{{ correo.direccion }}</a>
        </dd>
      </template>
    </dl>

    <div class="row">
      <div class="col-lg-8">
        <h2 class="h3 mt-5">Servicios en línea</h2>

        <p>
          La Sede Electrónica está en construcción. Estos son los servicios que
          publicará y el estado en que se encuentran hoy:
        </p>

        <dl class="row">
          <template v-for="servicio in serviciosEnLinea" :key="servicio.ruta">
            <dt class="col-sm-4">
              <NuxtLink :to="servicio.ruta">{{ servicio.titulo }}</NuxtLink>
            </dt>
            <dd class="col-sm-8">{{ servicio.estado }}</dd>
          </template>
        </dl>

        <p>
          Mientras estos servicios no estén disponibles, la atención se presta por
          los canales telefónicos y electrónicos de arriba.
        </p>

        <h2 class="h3 mt-5">Barreras de accesibilidad</h2>

        <p>
          Si encuentra una barrera que le impida usar este sitio, puede reportarla
          por los canales de atención de arriba. El compromiso y el procedimiento
          están en la
          <NuxtLink to="/accesibilidad">declaración de accesibilidad</NuxtLink>.
        </p>

        <p class="mt-4 mb-0">
          <NuxtLink class="btn btn-outline-primary" to="/">Volver a la portada</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>
