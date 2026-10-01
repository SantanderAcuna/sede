<script setup lang="ts">
/**
 * Mapa del sitio.
 *
 * Se construye a partir de la configuración centralizada de rutas
 * (`config/sitemap.ts`) y se mantiene sincronizado con el menú de navegación
 * automáticamente (RF-B1-010).
 *
 * Las secciones que aún no publican contenido se incluyen con la marca
 * "(en preparación)" para que el ciudadano sepa que existen pero aún no
 * están activas.
 */
import { configRutas, todasLasRutas } from '~/config/sitemap'

interface Entrada {
  texto: string
  ruta: string
  publicada: boolean
}

interface Grupo {
  titulo: string
  ruta?: string
  entradas: Entrada[]
}

/**
 * Construye los grupos del mapa del sitio a partir de la configuración
 * centralizada de rutas. Las secciones con sub-enlaces se muestran como
 * grupo con un enlace al padre seguido de los hijos.
 */
const grupos = computed<Grupo[]>(() => {
  const resultado: Grupo[] = []

  // Grupo de rutas sueltas (secciones principales)
  const principales = configRutas.rutasSueltas
    .filter((r) => r.ruta !== '/') // el inicio va en Portada
    .sort((a, b) => a.orden - b.orden)

  const rutasPrincipales: Grupo = {
    titulo: 'Secciones de la Sede',
    entradas: principales.map((r) => ({
      texto: r.etiqueta,
      ruta: r.ruta,
      publicada: r.publicada,
    })),
  }
  resultado.push(rutasPrincipales)

  // Grupos de Participa
  for (const seccion of configRutas.secciones) {
    const entradas: Entrada[] = seccion.enlaces.map((e) => ({
      texto: e.etiqueta,
      ruta: e.ruta,
      publicada: e.publicada,
    }))

    resultado.push({
      titulo: seccion.titulo,
      ruta: seccion.ruta,
      entradas,
    })
  }

  /*
   * Grupo de políticas. Se toman de la misma lista que el resto del sitio —y no
   * de una copia local con su estado inventado—: las cinco existen pero ninguna
   * publica todavía su documento, así que se muestran como «en preparación», que
   * es la verdad. Antes se marcaban aquí como publicadas mientras el pie
   * advertía lo contrario (R-03).
   */
  resultado.push({
    titulo: 'Políticas del sitio',
    entradas: todasLasRutas()
      .filter((ruta) => ruta.ruta.startsWith('/politicas/'))
      .map((ruta) => ({
        texto: ruta.etiqueta,
        ruta: ruta.ruta,
        publicada: ruta.publicada,
      })),
  })

  return resultado
})

useHead({ title: 'Mapa del sitio · Sede Electrónica' })
</script>

<template>
  <div class="container py-5">
    <h1>Mapa del sitio</h1>

    <div class="row">
      <div class="col-lg-8">
        <p class="lead">
          Todas las secciones y páginas de esta Sede Electrónica, organizadas
          según la estructura de navegación.
        </p>

        <p>
          Las páginas marcadas <em>(en preparación)</em> ya existen y explican qué
          publicarán cuando su contenido esté integrado; no son enlaces rotos,
          pero tampoco publican todavía la información que anuncian.
        </p>
      </div>
    </div>

    <nav aria-label="Mapa del sitio">
      <section
        v-for="grupo in grupos"
        :key="grupo.titulo"
        class="mt-5"
      >
        <!-- El título del grupo puede ser un enlace si tiene página propia -->
        <h2 class="h3">
          <NuxtLink v-if="grupo.ruta" :to="grupo.ruta">
            {{ grupo.titulo }}
          </NuxtLink>
          <span v-else>{{ grupo.titulo }}</span>
        </h2>

        <ul>
          <li
            v-for="entrada in grupo.entradas"
            :key="entrada.ruta"
          >
            <NuxtLink :to="entrada.ruta">{{ entrada.texto }}</NuxtLink>
            <span v-if="!entrada.publicada" class="estado-preparacion">
              (en preparación)
            </span>
          </li>
        </ul>
      </section>
    </nav>

    <p class="mt-5">
      <NuxtLink class="btn btn-outline-primary" to="/">
        Volver a la portada
      </NuxtLink>
    </p>
  </div>
</template>

<style scoped>
/*
 * El estado de cada entrada. Se usa el Matterhorn del Kit
 * (`--govcolor-matterhorn`, 8,59:1 sobre blanco).
 */
.estado-preparacion {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-family: 'Verdana-Regular', system-ui, sans-serif;
  font-size: 15px;
}
</style>
