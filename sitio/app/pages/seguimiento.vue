<script setup lang="ts">
/**
 * Seguimiento de una solicitud por número de radicado.
 *
 * La consulta necesita el expediente electrónico, que todavía no existe. Se
 * publica sin campos de consulta a propósito: un formulario que devuelva
 * cualquier respuesta —o un error genérico— haría dudar al ciudadano de si el
 * radicado que tiene en la mano es válido, cuando el problema es que la consulta
 * aún no está construida.
 */
useHead({ title: 'Seguimiento de una solicitud · Sede Electrónica' })
</script>

<template>
  <SeccionEnPreparacion
    titulo="Seguimiento de una solicitud"
    proposito="Consulta del estado de una solicitud radicada ante la Entidad a partir de su número de radicado, con las actuaciones surtidas y los documentos asociados al expediente."
  />
</template>
