<script setup lang="ts">
import type { NuxtError } from '#app'

/**
 * Página de error del sitio público.
 *
 * Nuxt devuelve al cliente el estado que lleva el error, así que una dirección
 * inventada responde 404 de verdad: un buscador no la indexa y un ciudadano sabe
 * que se equivocó de dirección en lugar de creer que el trámite existe.
 *
 * **RF-B1-007 exige ≥3 opciones de navegación** en la 404: menú, buscador y
 * secciones populares. Este página las cumple: enlaces a la portada, al mapa del
 * sitio, al buscador y a las tres secciones principales.
 *
 * **La disposición se monta aquí a mano.** Nuxt no aplica `layouts/` a este
 * archivo —sustituye a la aplicación entera cuando hay un error—, así que sin
 * este `<NuxtLayout>` el 404 sale sin barra superior, sin cabecera, sin menú
 * y sin pie. La disposición se monta explícitamente para que el ciudadano no
 * quede sin navegación cuando llega a una dirección inexistente.
 */
const props = defineProps<{ error: NuxtError }>()

const esNoEncontrada = computed(() => props.error.statusCode === 404)

/**
 * Secciones principales de la sede — usadas como opciones de navegación
 * en la página de error, conforme a RF-B1-007.
 */
const seccionesPopulares = [
  {
    etiqueta: 'Transparencia y acceso a la información pública',
    ruta: '/transparencia',
    icono: 'icono-transparencia',
  },
  {
    etiqueta: 'Servicios a la Ciudadanía',
    ruta: '/servicios',
    icono: 'icono-servicios',
  },
  {
    etiqueta: 'Participa',
    ruta: '/participa',
    icono: 'icono-participa',
  },
]

useHead({
  title: esNoEncontrada.value
    ? 'Página no encontrada · Sede Electrónica'
    : 'Error · Sede Electrónica',
  // Una página de error no aporta nada a un buscador y no debe indexarse.
  meta: [{ name: 'robots', content: 'noindex, follow' }],
})
</script>

<template>
  <NuxtLayout>
    <div class="container py-5">
      <!-- Encabezado del error -->
      <div class="encabezado-error">
        <div class="icono-error" aria-hidden="true">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>

        <h1 v-if="esNoEncontrada">Página no encontrada</h1>
        <h1 v-else>No se pudo completar la operación</h1>

        <p v-if="esNoEncontrada" class="lead">
          La dirección que abrió no corresponde a ninguna página de la Sede Electrónica.
          Puede que el enlace esté mal escrito, que la página se haya movido o que
          nunca haya existido.
        </p>
        <p v-else class="lead">
          Ha ocurrido un error al atender su solicitud. Vuelva a intentarlo en unos minutos.
        </p>
      </div>

      <!-- Código de error — visible para quien deba reportarlo -->
      <p class="codigo-error">
        Código de estado: <strong>{{ error.statusCode }}</strong>
      </p>

      <!-- ——— Opciones de navegación ——— -->
      <!--
        RF-B1-007 exige ≥3 opciones: menú, buscador y secciones populares.
        Se cumplen con: (1) portada, (2) mapa del sitio, (3) buscador y
        (4) las tres secciones principales de la sede.
      -->
      <section aria-labelledby="titulo-opciones" class="seccion-opciones mt-4">
        <h2 id="titulo-opciones" class="h5">¿Qué puede hacer ahora?</h2>

        <div class="row g-3 mt-2">
          <!-- Opción 1: Volver a la portada -->
          <div class="col-md-4">
            <NuxtLink to="/" class="tarjeta-opcion">
              <span class="icono-opcion" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span class="texto-opcion">
                <strong>Volver a la portada</strong>
                <span>Ir al inicio de la sede electrónica</span>
              </span>
            </NuxtLink>
          </div>

          <!-- Opción 2: Buscar en la sede -->
          <div class="col-md-4">
            <NuxtLink to="/buscar" class="tarjeta-opcion">
              <span class="icono-opcion" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/>
                  <line x1="21" y1="21" x2="16.65" y2="16.65" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="texto-opcion">
                <strong>Buscar en la sede</strong>
                <span>Encontrar trámites, servicios o información</span>
              </span>
            </NuxtLink>
          </div>

          <!-- Opción 3: Mapa del sitio -->
          <div class="col-md-4">
            <NuxtLink to="/mapa-del-sitio" class="tarjeta-opcion">
              <span class="icono-opcion" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <line x1="8" y1="2" x2="8" y2="18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <line x1="16" y1="6" x2="16" y2="22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span class="texto-opcion">
                <strong>Mapa del sitio</strong>
                <span>Ver todas las secciones disponibles</span>
              </span>
            </NuxtLink>
          </div>
        </div>

        <!-- Secciones populares -->
        <h3 class="h6 mt-4 mb-3">Secciones principales</h3>
        <div class="row g-2">
          <div
            v-for="seccion in seccionesPopulares"
            :key="seccion.ruta"
            class="col-md-4"
          >
            <NuxtLink :to="seccion.ruta" class="enlace-seccion">
              {{ seccion.etiqueta }}
            </NuxtLink>
          </div>
        </div>
      </section>

      <!-- Aviso sobre páginas en preparación -->
      <p class="aviso-adicional mt-4">
        Si llegó aquí desde un enlace dentro de la sede, es posible que esa
        sección esté en preparación. Use el mapa del sitio o el buscador para
        encontrar lo que necesita.
      </p>
    </div>
  </NuxtLayout>
</template>

<style scoped>
.encabezado-error {
  text-align: center;
  margin-bottom: 1.5rem;
}

.icono-error {
  color: var(--govcolor-cobalt, #0943b5);
  margin-bottom: 1rem;
}

.encabezado-error h1 {
  font-size: 1.75rem;
  margin-bottom: 0.5rem;
}

.encabezado-error .lead {
  max-width: 48ch;
  margin: 0 auto;
  color: #555;
}

.codigo-error {
  text-align: center;
  font-size: 0.875rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
  margin-bottom: 1.5rem;
}

/* Tarjetas de opción de navegación */
.tarjeta-opcion {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
  padding: 1rem 1.125rem;
  border: 0.0625rem solid #e0e0e0;
  border-radius: 0.5rem;
  text-decoration: none;
  color: inherit;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  min-height: 5rem;
}

.tarjeta-opcion:hover {
  border-color: var(--govcolor-cobalt, #0943b5);
  box-shadow: 0 0.125rem 0.5rem rgba(9 67 181 / 0.12);
}

.tarjeta-opcion:focus-visible {
  outline: 0.188rem solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 0.125rem;
}

.icono-opcion {
  flex: 0 0 auto;
  color: var(--govcolor-cobalt, #0943b5);
  margin-top: 0.125rem;
}

.texto-opcion {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.texto-opcion strong {
  font-size: 0.9375rem;
  color: #111;
}

.texto-opcion span {
  font-size: 0.8125rem;
  color: #666;
}

/* Enlaces de sección popular */
.enlace-seccion {
  display: block;
  padding: 0.5rem 0.875rem;
  border: 0.0625rem solid var(--govcolor-cobalt, #0943b5);
  border-radius: 0.375rem;
  color: var(--govcolor-cobalt, #0943b5);
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  text-align: center;
  transition: background-color 0.15s ease;
}

.enlace-seccion:hover {
  background-color: var(--govcolor-solitude, #e5ecf8);
}

.enlace-seccion:focus-visible {
  outline: 0.188rem solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 0.125rem;
}

.aviso-adicional {
  font-size: 0.875rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
  text-align: center;
  max-width: 56ch;
  margin: 1.5rem auto 0;
  padding: 0.75rem 1rem;
  background-color: #f8f9fa;
  border-radius: 0.375rem;
  border: 0.0625rem solid #e9ecef;
}
</style>
