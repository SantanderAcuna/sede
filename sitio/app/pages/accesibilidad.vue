<script setup lang="ts">
/**
 * Declaración de Accesibilidad — conforme al Anexo 1 num. 9.3 de la
 * Resolución 1519 de 2020 del Ministerio de Tecnologías de la Información
 * y las Comunicaciones.
 *
 * Esta declaración acredita el cumplimiento de las Pautas de Accesibilidad para
 * el Contenido Web (WCAG) 2.1 en su nivel AA.
 *
 * **Los 12 elementos mínimos del Anexo 1 num. 9.3:**
 *  1. Entidad                            → alineado desde el pie
 *  2. Sede Electrónica (URL)            → https://www.santamarta.gov.co
 *  3. Norma de referencia               → Res. 1519/2020 + WCAG 2.1 AA
 *  4. Fecha de evaluación               → [PENDIENTE: primera auditoría]
 *  5. Alcance (URLs o sitemap)        → [PENDIENTE: primera auditoría]
 *  6. Herramientas utilizadas + versión  → [PENDIENTE: primera auditoría]
 *  7. Auditor                           → [PENDIENTE: primera auditoría]
 *  8. Estado de cumplimiento            → [PENDIENTE: primera auditoría]
 *  9. Criterios no aplicables          → [PENDIENTE: primera auditoría]
 * 10. Excepciones documentadas           → [PENDIENTE: primera auditoría]
 * 11. Hallazgos pendientes             → [PENDIENTE: primera auditoría]
 * 12. Fecha próxima revisión (≤12 m)   → [PENDIENTE: primera auditoría]
 *
 * Esta página se mantiene conforme mientras la auditoría no se haya ejecutado.
 * Una vez ejecutada, se completa con los datos reales de la auditoría.
 */
useHead({
  title: 'Declaración de accesibilidad · Sede Electrónica',
})

// — Datos de la entidad (alineados con PiePaginaGovco.vue) —
const entidad = 'Alcaldía Distrital de Santa Marta'
const nit = 'NIT 891.780.009-4'
const sedeUrl = 'https://www.santamarta.gov.co'

// — Norma de referencia —
const norma = {
  nombre: 'Resolución 1519 de 2020',
  entidad: 'Ministerio de Tecnologías de la Información y las Comunicaciones',
  anexo: 'Anexo 1 — Accesibilidad web',
  url: 'https://www.mintic.gov.co/portal/715/articles-123158_Resolucion1519_2020.pdf',
}
const wgac = {
  nombre: 'WCAG 2.1',
  nivel: 'AA',
  url: 'https://www.w3.org/TR/WCAG21/',
}

// — Qué ofrece el sitio hoy (comprobable sin auditoría) —
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
  {
    titulo: 'Formularios accesibles',
    detalle:
      'Cada campo tiene su etiqueta asociada, mensajes de error descriptivos y el foco se mueve al primer campo inválido.',
  },
  {
    titulo: 'Cookies con consentimiento',
    detalle:
      'El banner de cookies permite aceptar, rechazar o personalizar las categorías antes de activar cookies no esenciales.',
  },
]

// — Canales para reportar barreras (alineados con el pie) —
const telefonos = [
  { etiqueta: 'Teléfono conmutador', numero: '(+57) 605 420 9600' },
  { etiqueta: 'Línea gratuita nacional', numero: '018000 955 532' },
  { etiqueta: 'Línea anticorrupción', numero: '(+57) 605 435 1719' },
]
const correos = [
  {
    etiqueta: 'Correo de atención al ciudadano',
    direccion: 'atencionalciudadano@santamarta.gov.co',
  },
  {
    etiqueta: 'Correo de notificaciones judiciales',
    direccion: 'notificacionesalcaldiadistrital@santamarta.gov.co',
  },
]
</script>

<template>
  <div class="container py-5">
    <h1>Declaración de accesibilidad</h1>

    <div class="row">
      <div class="col-lg-8">

        <!-- Introducción -->
        <p class="lead">
          Esta Sede Electrónica es titularidad de la
          <strong>{{ entidad }}</strong> (<em>{{ nit }}</em>), y se encuentra disponible
          en <strong>{{ sedeUrl }}</strong>.
        </p>

        <p>
          La sede se rige por la <strong>{{ norma.nombre }}</strong>
          de {{ norma.entidad }}, en su <strong>{{ norma.anexo }}</strong>, y por las
          <strong>{{ wgac.nombre }}</strong>, nivel <strong>{{ wgac.nivel }}</strong>.
          El nivel AA es exigible en Colombia desde el 1 de enero de 2022 en virtud del
          artículo 14 del Decreto Ley 2106 de 2019.
        </p>

        <!-- Sección 1: Qué ofrece el sitio -->
        <h2 class="h3 mt-5">Características de accesibilidad presentes</h2>

        <p>
          Las siguientes características son verificables en cualquier página de la sede,
          sin necesidad de herramientas especiales:
        </p>

        <ul>
          <li v-for="p in prestaciones" :key="p.titulo">
            <strong>{{ p.titulo }}.</strong> {{ p.detalle }}
          </li>
        </ul>

        <!-- Sección 2: Declaración de conformidad (los 12 elementos) -->
        <h2 class="h3 mt-5">Declaración de conformidad</h2>

        <p>
          Conforme al numeral 9.3 del Anexo 1 de la {{ norma.nombre }}, la siguiente
          tabla establece los 12 elementos mínimos de la declaración de accesibilidad
          y su estado en esta sede:
        </p>

        <!-- Tabla de los 12 elementos -->
        <div class="tabla-conformidad table-responsive">
          <table class="table table-bordered table-sm">
            <caption class="visually-hidden">
              Elementos mínimos de la declaración de conformidad
            </caption>
            <thead>
              <tr>
                <th scope="col" style="width: 2.5rem;">#</th>
                <th scope="col">Elemento</th>
                <th scope="col">Estado</th>
              </tr>
            </thead>
            <tbody>
              <!-- 1. Entidad -->
              <tr>
                <td>1</td>
                <td><strong>Entidad responsable</strong></td>
                <td class="estado-si">
                  <span class="badge bg-success">Completo</span>
                  {{ entidad }}, {{ nit }}
                </td>
              </tr>
              <!-- 2. Sede electrónica (URL) -->
              <tr>
                <td>2</td>
                <td><strong>Sede electrónica (URL)</strong></td>
                <td class="estado-si">
                  <span class="badge bg-success">Completo</span>
                  {{ sedeUrl }}
                </td>
              </tr>
              <!-- 3. Norma de referencia -->
              <tr>
                <td>3</td>
                <td><strong>Norma de referencia</strong></td>
                <td class="estado-si">
                  <span class="badge bg-success">Completo</span>
                  {{ norma.nombre }} · {{ wgac.nombre }} {{ wgac.nivel }}
                </td>
              </tr>
              <!-- 4. Fecha de evaluación -->
              <tr>
                <td>4</td>
                <td><strong>Fecha de evaluación</strong></td>
                <td class="estado-pendiente">
                  <span class="badge bg-warning text-dark">Pendiente</span>
                  Primera auditoría de accesibilidad pendiente de ejecución.
                  Los datos se actualizarán una vez realizada.
                </td>
              </tr>
              <!-- 5. Alcance (URLs o sitemap) -->
              <tr>
                <td>5</td>
                <td><strong>Alcance (URLs o sitemap)</strong></td>
                <td class="estado-pendiente">
                  <span class="badge bg-warning text-dark">Pendiente</span>
                  Se especificará el alcance de la evaluación, incluyendo las páginas
                  cubiertas y el mapa del sitio.
                </td>
              </tr>
              <!-- 6. Herramientas utilizadas con versión -->
              <tr>
                <td>6</td>
                <td><strong>Herramientas utilizadas con versión</strong></td>
                <td class="estado-pendiente">
                  <span class="badge bg-warning text-dark">Pendiente</span>
                  Se especificarán las herramientas empleadas en la auditoría
                  (p. ej. axe-core, Lighthouse, WAVE) con sus versiones.
                </td>
              </tr>
              <!-- 7. Auditor -->
              <tr>
                <td>7</td>
                <td><strong>Auditor</strong></td>
                <td class="estado-pendiente">
                  <span class="badge bg-warning text-dark">Pendiente</span>
                  Nombre, cargo y credentials del profesional o equipo que realizó
                  la auditoría.
                </td>
              </tr>
              <!-- 8. Estado de cumplimiento -->
              <tr>
                <td>8</td>
                <td><strong>Estado de cumplimiento (completo o parcial)</strong></td>
                <td class="estado-pendiente">
                  <span class="badge bg-warning text-dark">Pendiente</span>
                  Se declarará si el cumplimiento es total o parcial, indicando
                  qué secciones no cumplen y por qué.
                </td>
              </tr>
              <!-- 9. Criterios no aplicables -->
              <tr>
                <td>9</td>
                <td><strong>Criterios no aplicables y excepciones</strong></td>
                <td class="estado-pendiente">
                  <span class="badge bg-warning text-dark">Pendiente</span>
                  Se listarán los criterios WCAG que, por la naturaleza del contenido,
                  no resultan aplicables.
                </td>
              </tr>
              <!-- 10. Excepciones documentadas -->
              <tr>
                <td>10</td>
                <td><strong>Excepciones documentadas con mitigación</strong></td>
                <td class="estado-pendiente">
                  <span class="badge bg-warning text-dark">Pendiente</span>
                  Cualquier excepción al cumplimiento irá acompañada de su medida
                  de mitigación.
                </td>
              </tr>
              <!-- 11. Hallazgos pendientes -->
              <tr>
                <td>11</td>
                <td><strong>Hallazgos pendientes con severidad, plan y fecha</strong></td>
                <td class="estado-pendiente">
                  <span class="badge bg-warning text-dark">Pendiente</span>
                  Se registrarán los hallazgos de accesibilidad pendientes de corregir,
                  su nivel de severidad y el plan de remediación.
                </td>
              </tr>
              <!-- 12. Fecha próxima revisión -->
              <tr>
                <td>12</td>
                <td><strong>Fecha de próxima revisión (máximo 12 meses)</strong></td>
                <td class="estado-pendiente">
                  <span class="badge bg-warning text-dark">Pendiente</span>
                  Conforme a la normativa, la próxima revisión se realizará dentro
                  de los 12 meses siguientes a la última evaluación.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Nota sobre el estado actual -->
        <div class="alert alert-info mt-4" role="note">
          <p class="mb-0">
            <strong>Estado actual:</strong> la presente declaración estructura los 12
            elementos exigidos por el Anexo 1 de la {{ norma.nombre }}. Los campos
            pendientes se completarán una vez se ejecute la primera auditoría de
            accesibilidad. Esta sede <strong>no declara un nivel de conformidad
            verificado</strong> hasta que la auditoría esté ejecutada, firmada y
            publicada.
          </p>
        </div>

        <!-- Sección 3: Cómo reportar una barrera -->
        <h2 class="h3 mt-5">Cómo reportar una barrera de accesibilidad</h2>

        <p>
          Si encuentra una barrera —un contenido que no puede leer, un control que no
          puede usar con el teclado o con un producto de apoyo, un texto con contraste
          insuficiente, una imagen sin descripción— repórtelo a la Entidad. Su aviso
          es lo que permite corregirla.
        </p>

        <h3 class="h6 mt-4">Canales de contacto</h3>

        <ul>
          <li
            v-for="correo in correos"
            :key="correo.direccion"
          >
            {{ correo.etiqueta }}:
            <a
              :href="`mailto:${correo.direccion}`"
              :aria-label="`${correo.direccion} (abre el programa de correo)`"
            >{{ correo.direccion }}</a>
          </li>
          <li
            v-for="telefono in telefonos"
            :key="telefono.numero"
          >
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

        <p class="mt-4">
          <NuxtLink class="btn btn-outline-primary" to="/">Volver a la portada</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/*
 * Tabla de conformidad con los 12 elementos.
 * Los elementos completados se muestran con fondo verde suave;
 * los pendientes con fondo amarillo suave.
 */
.table-conformidad {
  margin-top: 1.5rem;
}

.table-conformidad .table {
  font-size: 0.9375rem;
}

.table-conformidad th {
  background-color: var(--govcolor-cobalt, #0943b5);
  color: #fff;
  font-weight: 600;
}

.table-conformidad td {
  vertical-align: middle;
}

.estado-si {
  background-color: #f0fdf4;
  color: #166534;
}

.estado-pendiente {
  background-color: #fefce8;
  color: #854d0e;
}

.badge {
  font-size: 0.8125rem;
  font-weight: 600;
  margin-right: 0.5rem;
}

/*
 * Enlace de correo: permitir que se parta en varias líneas
 * si es largo, sin provocar reflujo horizontal (WCAG 1.4.10).
 */
.correo {
  overflow-wrap: anywhere;
}

/* Alerta info */
.alert-info {
  background-color: #eff6ff;
  border: 0.0625rem solid #bfdbfe;
  color: #1e40af;
  border-radius: 0.375rem;
  padding: 1rem 1.25rem;
}

.alert-info p {
  font-size: 0.9375rem;
}
</style>
