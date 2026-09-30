<script setup lang="ts">
/**
 * Catálogo de trámites y servicios.
 *
 * La ruta está fijada desde el primer día por el mapa de rutas (sección 9.2 del
 * plan), pero el catálogo todavía no existe: los trámites llegan con la
 * migración de contenidos y con la integración del sistema de radicación.
 *
 * **Por qué no se rellena.** Un trámite inventado no es un hueco en una maqueta:
 * es un procedimiento con requisitos, costo y plazo que nadie ha aprobado, y el
 * ciudadano decide sobre esa información. Antes de publicar uno falso es
 * preferible decir que la sección está en preparación, que es lo que hace esta
 * página.
 *
 * Se apoya en `SeccionEnPreparacion` para que todas las secciones sin contenido
 * se lean y se naveguen igual.
 */
useHead({ title: 'Trámites y servicios · Sede Electrónica' })
</script>

<template>
  <SeccionEnPreparacion
    titulo="Trámites y servicios"
    proposito="Catálogo de los trámites y servicios de la Alcaldía Distrital de Santa Marta, con la ficha de cada uno: qué es, quién puede solicitarlo, requisitos, costo, tiempo de respuesta y el canal para iniciarlo o radicarlo en línea."
  />
</template>
