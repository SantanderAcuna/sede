<script setup lang="ts">
/**
 * Barra superior del Estado (Kit UI, componente transversal 2).
 *
 * Franja azul cobalto con el logotipo de GOV.CO, que enlaza a la portada del
 * Portal Único (`https://www.gov.co/home/`, RF-B1-001). Es obligatoria en todas
 * las páginas y va por encima de la cabecera.
 *
 * **Sin botón de «Iniciar sesión» (D-6, ADR-0002).** Aquí vivía un icono que
 * llevaba a `/admin/acceso`, la entrada de los funcionarios, desde una barra que
 * ve toda la ciudadanía. El acceso del ciudadano a sus trámites se resolverá con
 * Servicios Ciudadanos Digitales; hasta entonces un icono de usuario en la barra
 * pública prometía una cuenta que no existe. Los funcionarios entran por la URL
 * directa.
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
      href="https://www.gov.co/home/"
      target="_blank"
      rel="noopener"
      aria-label="Portal del Estado Colombiano - GOV.CO"
    />
  </div>
</template>
