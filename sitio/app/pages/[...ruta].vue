<script setup lang="ts">
/**
 * Cualquier dirección que no exista tiene que devolver un 404 DE VERDAD.
 *
 * Sin una página comodín, Nuxt renderiza lo mismo con estado 200 para CUALQUIER
 * ruta. Comprobado en el despliegue: `/esto-no-existe-12345/` y
 * `/cualquier-cosa/trámite/falso` respondían 200 con la misma página que `/`.
 *
 * En una sede electrónica eso no es un detalle de posicionamiento:
 *
 *   · Crea contenido duplicado INFINITO, y la obligación O-01 exige un dominio
 *     canónico único. Un buscador puede indexar miles de direcciones inventadas
 *     que sirven el mismo contenido.
 *   · Un ciudadano que se equivoque al escribir la dirección de un trámite ve una
 *     página que parece correcta, en lugar de saber que no existe.
 *
 * `fatal: true` hace que se renderice la página de error con su estado, en vez de
 * intentar continuar.
 */
throw createError({
  statusCode: 404,
  // La frase de estado viaja en la LÍNEA de estado del protocolo, que sólo
  // admite ASCII: con acento salía «Pgina no encontrada» en las cabeceras. El
  // texto que lee el ciudadano está en error.vue, donde sí lleva tildes.
  statusMessage: 'Not Found',
  fatal: true,
})
</script>

<template>
  <!-- Nunca se llega aquí: el error corta el renderizado y lo toma error.vue. -->
</template>
