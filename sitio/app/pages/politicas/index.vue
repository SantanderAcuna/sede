<script setup lang="ts">
/**
 * Índice de las políticas del pie (SEG-006): las cinco obligatorias y las que la Entidad haya publicado.
 *
 * Existe porque la miga de pan de cada política —`Inicio › Políticas › Uso de
 * cookies`— enlaza a `/politicas`, y esa dirección daba 404 (H-22, AC-21.1). Una
 * miga que lleva a un error es peor que no tenerla.
 *
 * Las cinco obligatorias salen de `POLITICAS`, la fuente única (D-31): no se repiten aquí ni los
 * nombres ni las direcciones. Las propias salen de la API, ya publicadas.
 */
import { POLITICAS } from '~/config/sitemap'
import { useMetadatosComparticion } from '~/composables/useMetadatosComparticion'
import { usePoliticasPropias } from '~/composables/usePoliticasPropias'

const propias = await usePoliticasPropias()

useHead({ title: 'Políticas del sitio · Sede Electrónica' })
useMetadatosComparticion({
  titulo: () => 'Políticas del sitio',
  descripcion: () =>
    'Términos de uso, seguridad y privacidad, tratamiento de datos personales, cookies y derechos de autor de la Sede Electrónica.',
})
</script>

<template>
  <div class="container py-5">
    <h1>Políticas del sitio</h1>

    <div class="row">
      <div class="col-lg-8">
        <p class="lead">
          Las condiciones con las que la Sede Electrónica trata su información, la de
          quien la usa y sus contenidos.
        </p>

        <ul>
          <li v-for="politica in POLITICAS" :key="politica.slug">
            <NuxtLink :to="`/politicas/${politica.slug}`">{{ politica.etiqueta }}</NuxtLink>
          </li>
          <li v-for="politica in propias" :key="politica.slug" data-politica-propia>
            <NuxtLink :to="`/politicas/${politica.slug}`">{{ politica.nombre }}</NuxtLink>
          </li>
        </ul>

        <p class="mt-4 mb-0">
          <NuxtLink class="btn btn-outline-primary" to="/">Volver a la portada</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>
