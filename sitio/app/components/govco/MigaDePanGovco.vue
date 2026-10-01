<script setup lang="ts">
/**
 * Miga de pan (Kit UI, componente general 22).
 *
 * Criterio **CAG-11**, bloqueante: tiene que estar en **todas las secciones
 * menos la portada**, y la versión *invertida* sólo se usa sobre fondo oscuro.
 * Por eso el componente no se pinta solo cuando el recorrido tiene un solo
 * nivel: en la portada no hay nada que orientar y una miga que sólo dice
 * «Inicio» ocupa espacio sin informar.
 *
 * Se reproduce la estructura del Kit —`nav`, `ul.breadcrumb-govco`, un
 * `li.breadcrumb-item-govco` por nivel— con las clases reales de `all.css`. El
 * último nivel va marcado con `aria-current="page"`, que es lo que le dice a un
 * lector de pantalla dónde está la persona; sin él la lista se lee como si todos
 * los niveles fueran destinos posibles.
 */
import { computed } from 'vue'

export interface NivelMigaDePan {
  etiqueta: string
  /** Ausente en el nivel actual: no se enlaza a donde ya se está. */
  ruta?: string
}

const props = withDefaults(
  defineProps<{
    niveles: NivelMigaDePan[]
    /** Sólo para fondos oscuros, como marca el Kit. */
    invertida?: boolean
  }>(),
  { invertida: false },
)

/** Con un solo nivel no hay recorrido que mostrar. */
const visible = computed(() => props.niveles.length > 1)

const etiquetaAccesible = computed(() => {
  const niveles = props.niveles.length
  return `Miga de pan de ${niveles} ${niveles === 1 ? 'nivel' : 'niveles'}`
})
</script>

<template>
  <nav v-if="visible" :aria-label="etiquetaAccesible" class="breadcrumb-nav-govco">
    <div class="container">
      <ul class="breadcrumb-govco" :class="{ inverted: invertida }">
        <li
          v-for="(nivel, indice) in niveles"
          :key="nivel.ruta ?? nivel.etiqueta"
          class="breadcrumb-item-govco"
          :class="{ active: indice === niveles.length - 1, invested: invertida }"
        >
          <!--
            El último nivel es la página actual: se muestra como texto y no como
            enlace, porque un enlace a la página en la que ya se está no lleva a
            ninguna parte y confunde a quien navega con teclado.
          -->
          <NuxtLink v-if="nivel.ruta && indice < niveles.length - 1" :to="nivel.ruta">
            {{ nivel.etiqueta }}
          </NuxtLink>
          <span v-else aria-current="page">{{ nivel.etiqueta }}</span>
        </li>
      </ul>
    </div>
  </nav>
</template>

<style scoped>
/*
 * El Kit deja la lista con `padding: 0 46px`, pensada para ir a sangre en el
 * borde de la página. Dentro del contenedor de Bootstrap ese relleno sobra y
 * desalinea la miga respecto al resto del contenido, así que se neutraliza.
 */
.breadcrumb-govco {
  padding: 0;
}
</style>
