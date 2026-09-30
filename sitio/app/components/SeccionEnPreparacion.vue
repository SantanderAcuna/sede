<script setup lang="ts">
/**
 * Página de sección todavía sin contenido.
 *
 * Existe para que las secciones que el criterio FUN-013 obliga a mostrar —y sus
 * enlaces— **no lleven a ninguna parte**. Una sede electrónica con enlaces rotos
 * incumple más que una que dice con claridad que el contenido está en camino.
 *
 * Dice lo que va a haber, no lo que hay: no se rellena con texto de ejemplo, que
 * en un sitio institucional se lee como información oficial.
 */
defineProps<{
  /** Título de la sección, tal como lo nombra el expediente. */
  titulo: string
  /** Qué publicará esta sección cuando esté lista. */
  proposito: string
}>()
</script>

<template>
  <div class="container py-5">
    <!--
      Un solo `h1` por página. La cabecera del sitio ya usa encabezados de
      navegación, pero no de contenido: esta es la que nombra la página.
    -->
    <h1>{{ titulo }}</h1>

    <div class="row">
      <div class="col-lg-8">
        <p class="lead">{{ proposito }}</p>

        <div class="alerta-preparacion" role="status">
          <p class="mb-0">
            Esta sección está en preparación. Su contenido se publicará cuando esté
            integrado con los sistemas de la Entidad.
          </p>
        </div>

        <p class="mt-4 mb-0">
          <a class="btn btn-outline-primary" href="/">Volver a la portada</a>
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/*
  Aviso de sección en preparación. Se usa el amarillo institucional del Kit
  (`--govcolor-vis-vis`) con el Matterhorn para el texto, que da 7,6:1 sobre ese
  fondo: por encima del 4,5:1 que exige WCAG 2.1 AA, y sin el amarillo saturado
  del aviso fuerte, que aquí sería excesivo para un simple estado.
*/
.alerta-preparacion {
  margin-top: 1.5rem;
  padding: 1rem 1.25rem;
  border-left: 4px solid var(--govcolor-golden-brown, #9d7700);
  background-color: var(--govcolor-vis-vis, #fee697);
  color: var(--govcolor-matterhorn, #4c4c4c);
}
</style>
