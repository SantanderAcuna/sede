<script setup lang="ts">
/**
 * Subcategorías de Participa.
 *
 * El menú obligatorio de la Sede desdobla Participa en **seis subcategorías**,
 * con los nombres que fija el §4.1.2.3 del Anexo 2 de la Resolución 2893 de
 * 2020. Esta página las resuelve todas contra una sola lista: el slug que no
 * está en ella da un 404 de verdad, no una página vacía.
 *
 * Se declara la lista en vez de crear seis archivos porque los seis comparten
 * contenido y estructura; seis copias serían seis sitios donde equivocarse.
 */
const SUBCATEGORIAS = {
  'identificacion-de-problemas': {
    titulo: 'Participación para la identificación de problemas y diagnóstico de necesidades',
    proposito:
      'Espacios para que la ciudadanía señale los problemas del Distrito y ayude a diagnosticar las necesidades que la Entidad debe atender.',
  },
  'presupuesto-participativo': {
    titulo: 'Planeación y/o presupuesto participativo',
    proposito:
      'Participación de la ciudadanía en la planeación del Distrito y en la decisión sobre el presupuesto participativo.',
  },
  'consulta-ciudadana': {
    titulo: 'Participación y consulta ciudadana de proyectos, normas, políticas o programas',
    proposito:
      'Consultas públicas sobre los proyectos, las normas, las políticas y los programas que la Entidad somete a consideración.',
  },
  'innovacion-abierta': {
    titulo: 'Colaboración e innovación abierta',
    proposito:
      'Espacios de colaboración con la ciudadanía, la academia y el sector privado para innovar en los servicios públicos.',
  },
  'rendicion-de-cuentas': {
    titulo: 'Rendición de cuentas',
    proposito:
      'Audiencias públicas participativas, informes de gestión y los espacios donde la Entidad rinde cuentas de lo que hizo.',
  },
  'control-ciudadano': {
    titulo: 'Control ciudadano',
    proposito:
      'Mecanismos para que la ciudadanía vigile la gestión pública y ejerza control sobre las decisiones y los recursos del Distrito.',
  },
} as const

type SlugParticipa = keyof typeof SUBCATEGORIAS

const ruta = useRoute()

const slug = computed(() => String(ruta.params.slug ?? ''))
const subcategoria = computed(() =>
  Object.prototype.hasOwnProperty.call(SUBCATEGORIAS, slug.value)
    ? SUBCATEGORIAS[slug.value as SlugParticipa]
    : undefined,
)

/**
 * Un slug que no está en la lista es una dirección inventada: se responde 404
 * en lugar de mostrar una página vacía, que parecería contenido pendiente.
 */
if (subcategoria.value === undefined) {
  throw createError({ statusCode: 404, statusMessage: 'Not Found', fatal: true })
}

useHead({
  title: subcategoria.value ? `${subcategoria.value.titulo} · Sede Electrónica` : 'Participa',
})
</script>

<template>
  <SeccionEnPreparacion
    v-if="subcategoria"
    :titulo="subcategoria.titulo"
    :proposito="subcategoria.proposito"
  />
</template>
