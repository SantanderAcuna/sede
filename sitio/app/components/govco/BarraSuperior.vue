<script setup lang="ts">
/**
 * Barra superior del Estado (Kit UI, componente transversal 2).
 *
 * Franja azul cobalto con el logotipo de GOV.CO, que enlaza al Portal Único.
 * Es obligatoria en todas las páginas y va por encima de la cabecera.
 *
 * **Sin botón de cambio de idioma, y es deliberado.** El Kit lo ofrece como
 * opcional, y el sitio es monolingüe: un conmutador que no cambia nada engaña
 * más de lo que ayuda. El día que haya un segundo idioma se añade aquí, con su
 * `aria-label` y su preferencia persistida (CAG-06).
 */
</script>

<template>
  <div class="barra-superior-govco">
    <a
      href="https://www.gov.co/"
      target="_blank"
      rel="noopener"
      aria-label="Portal del Estado Colombiano - GOV.CO"
    />
  </div>
</template>
