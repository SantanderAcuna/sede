<script setup lang="ts">
/**
 * Participa — página de la sección.
 *
 * Enumera las **seis subcategorías** que fija el §4.1.2.3 del Anexo 2 de la
 * Resolución 2893 de 2020. El menú ya las despliega, y esta página las repite en
 * el cuerpo a propósito: quien llegue por un buscador o por un enlace directo
 * tiene que poder ver qué contiene la sección sin abrir el menú.
 */
import { SUBCATEGORIAS_PARTICIPA } from '~/config/sitemap'

useHead({ title: 'Participa · Sede Electrónica' })

/**
 * Las seis subcategorías de participación.
 *
 * **No se declaran aquí.** Salen de `config/sitemap.ts`, la misma lista que
 * construye el menú y el `sitemap.xml`: la que había en este archivo decía
 * «Participación para la identificación de problemas…» donde la configuración
 * decía «Identificación de problemas…», y una de las dos iba a quedarse atrás sin
 * que nadie lo notara (D-31).
 */
const SUBCATEGORIAS = SUBCATEGORIAS_PARTICIPA.map((subcategoria) => ({
  ruta: subcategoria.ruta,
  titulo: subcategoria.etiqueta,
}))
</script>

<template>
  <div class="container py-5">
    <h1>Participa</h1>

    <p class="lead">
      Espacios y mecanismos con los que la ciudadanía interviene en las decisiones de la
      Alcaldía Distrital de Santa Marta.
    </p>

    <h2 class="h4 mt-5 mb-3">Mecanismos de participación</h2>

    <ul class="list-unstyled">
      <li v-for="subcategoria in SUBCATEGORIAS" :key="subcategoria.ruta" class="mb-2">
        <NuxtLink :to="subcategoria.ruta">{{ subcategoria.titulo }}</NuxtLink>
      </li>
    </ul>

    <h2 class="h4 mt-5 mb-3">Portales</h2>
    <p class="mb-0">
      <NuxtLink to="/portales">Portales de programas transversales</NuxtLink>
    </p>

    <p class="mt-4 mb-0">
      <NuxtLink class="btn btn-outline-primary" to="/">Volver a la portada</NuxtLink>
    </p>
  </div>
</template>
