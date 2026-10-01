<script setup lang="ts">
/**
 * Resultados de búsqueda (RF-B1-005, RF-B1-006, RF-B1-053, RF-B3-043).
 *
 * Hasta ahora esta página recibía el término y contestaba que no había índice:
 * era honesta, pero el buscador de la cabecera —que está en **todas** las
 * páginas y es una de las dos formas de localizar información que exige WCAG
 * 2.4.5— no buscaba nada. Aquí se conecta con lo que la sede sí puede buscar hoy:
 *
 *  1. **El catálogo de trámites**, contra `GET /tramites?buscar=`, que el contrato
 *     declara y que busca en el nombre y la descripción. La búsqueda la hace el
 *     servidor y no el navegador: traerse el catálogo entero para filtrarlo aquí
 *     haría el trabajo dos veces, y la segunda con la copia peor —la que no ve lo
 *     que no se ha traído—.
 *  2. **Las secciones del sitio**, sobre la lista de rutas que publican contenido
 *     (`config/sitemap.ts`). El índice se actualiza solo: cuando una sección pasa
 *     a publicada, entra en la búsqueda sin que nadie la añada.
 *
 * **Lo que la búsqueda NO puede dar todavía, y se dice.** RF-B1-006 pide que cada
 * resultado muestre fecha, categoría, título, extracto, autor y miniatura. El
 * contrato de hoy sólo trae nombre, descripción y categoría del trámite: no hay
 * fecha de publicación, ni autor, ni miniatura para ningún contenido. Publicar
 * los tres campos que faltan inventándolos convertiría un resultado en una ficha
 * falsa, así que se muestran los que existen y la ausencia queda documentada en
 * la auditoría (D-03). Tampoco hay sugerencias mientras se escribe ni corrección
 * ortográfica: eso necesita un servicio de sugerencias que aún no existe, y un
 * desplegable que no sugiere nada molesta más de lo que ayuda.
 */
import type { components } from '~~/types/openapi'

import { todasLasRutas } from '~/config/sitemap'

/** Un trámite del contrato, tal como viaja por la API. */
type TramiteApi = components['schemas']['TramiteItem']

/**
 * Lo que esta página lee del sobre de `GET /tramites`.
 *
 * No se usa `TramiteCollection` por el mismo defecto de la generación que anota
 * `tramites/index.vue`: `ApiEnvelope.data` está declarado como
 * `object | array | null`, así que componerlo deja los elementos en `unknown[]` y
 * obligaría a forzarlos con `as`, justo en la frontera donde no se quiere.
 */
interface RespuestaTramites {
  data: TramiteApi[]
  meta?: { total?: number }
}

const ruta = useRoute()
const enrutador = useRouter()

/** El término buscado, tal como llegó. Puede estar ausente si se entra directo. */
const termino = computed(() => {
  const valor = ruta.query.q
  return typeof valor === 'string' ? valor.trim() : ''
})

const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')
const traer = useRequestFetch()

/** Cuántos resultados se piden del catálogo. */
const TAMANO = 10

/** Normaliza para comparar: minúsculas y sin tildes. */
function normalizar(texto: string): string {
  return texto
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

/**
 * Índice de las secciones que publican contenido.
 *
 * Se toma de la configuración de rutas —la misma que alimenta el menú, el mapa
 * del sitio y el `sitemap.xml`— y se filtra por `publicada`: buscar no debe
 * llevar a una página que sólo dice que está en preparación.
 */
const secciones = todasLasRutas()
  .filter((rutaPublica) => rutaPublica.publicada && rutaPublica.ruta !== '/buscar')
  .map((rutaPublica) => ({
    etiqueta: rutaPublica.etiqueta,
    ruta: rutaPublica.ruta,
    comparable: normalizar(`${rutaPublica.etiqueta} ${rutaPublica.ruta}`),
  }))

/** Las secciones cuyo nombre o ruta contiene el término. */
const seccionesEncontradas = computed(() => {
  const buscado = normalizar(termino.value)
  if (buscado === '') return []
  return secciones.filter((seccion) => seccion.comparable.includes(buscado))
})

/**
 * Los trámites que el servidor devuelve para el término.
 *
 * `useAsyncData` y no una petición suelta: así el resultado se calcula en el
 * servidor y viaja en el HTML —el buscador tiene que funcionar aunque el
 * JavaScript no llegue—, y se vuelve a pedir cuando cambia el término.
 */
const {
  data: tramites,
  status,
  error,
} = await useAsyncData<RespuestaTramites | null>(
  'busqueda-tramites',
  async () => {
    if (termino.value === '') return null
    return await traer<RespuestaTramites>(`${urlApi}/tramites`, {
      query: { buscar: termino.value, per_page: TAMANO },
    })
  },
  { watch: [termino] },
)

/** Los trámites encontrados, ya en la forma que usa la plantilla. */
const tramitesEncontrados = computed(() =>
  (tramites.value?.data ?? []).map((tramite) => ({
    id: tramite.id,
    nombre: tramite.nombre,
    slug: tramite.slug,
    resumen: tramite.resumen ?? null,
    categoria: tramite.categoria?.nombre ?? null,
  })),
)

/**
 * El servicio respondió con un error. No es lo mismo que «no hay resultados»:
 * decirle a un ciudadano que su trámite no existe cuando lo que pasa es que el
 * servidor no contesta es una afirmación falsa sobre la Entidad.
 */
const servicioNoDisponible = computed(() => error.value !== null)

const totalResultados = computed(
  () => tramitesEncontrados.value.length + seccionesEncontradas.value.length,
)

const buscando = computed(() => status.value === 'pending')

useHead({
  title: termino.value
    ? `Búsqueda: ${termino.value} · Sede Electrónica`
    : 'Buscar · Sede Electrónica',
  // Una página de resultados no aporta nada a un buscador externo y puede
  // generar direcciones infinitas a partir de cualquier término.
  meta: [{ name: 'robots', content: 'noindex, follow' }],
})

function alBuscar(nuevo: string) {
  enrutador.push({ path: '/buscar', query: { q: nuevo } })
}
</script>

<template>
  <div class="container py-5">
    <h1>Buscar en la Sede</h1>

    <BuscadorGovco :valor-inicial="termino" class="mb-4" @buscar="alBuscar" />

    <!-- Sin término: se explica qué se puede buscar. -->
    <div v-if="!termino" class="row">
      <div class="col-lg-8">
        <p class="mb-0">
          Escriba lo que busca en el campo de arriba. Puede buscar un trámite, un
          servicio o una sección de la sede.
        </p>
      </div>
    </div>

    <template v-else>
      <!-- El recuento se anuncia sin robar el foco (RF-B3-043). -->
      <p class="resultado-resumen" role="status">
        <template v-if="buscando">Buscando «{{ termino }}»…</template>
        <template v-else-if="totalResultados === 0">
          No se encontró nada para «{{ termino }}».
        </template>
        <template v-else>
          {{ totalResultados }}
          {{ totalResultados === 1 ? 'resultado' : 'resultados' }} para
          «{{ termino }}».
        </template>
      </p>

      <section
        v-if="tramitesEncontrados.length > 0"
        aria-labelledby="titulo-tramites"
        class="mt-4"
      >
        <h2 id="titulo-tramites" class="h4">Trámites y servicios</h2>

        <ul class="lista-resultados">
          <li v-for="tramite in tramitesEncontrados" :key="tramite.id">
            <NuxtLink :to="`/tramites/${tramite.slug}`" class="resultado-enlace">
              {{ tramite.nombre }}
            </NuxtLink>
            <p v-if="tramite.categoria" class="resultado-meta">{{ tramite.categoria }}</p>
            <p v-if="tramite.resumen" class="resultado-extracto">{{ tramite.resumen }}</p>
          </li>
        </ul>

        <p class="mt-3 mb-0">
          <NuxtLink to="/tramites">Ver todo el catálogo de trámites y servicios</NuxtLink>
        </p>
      </section>

      <section
        v-if="seccionesEncontradas.length > 0"
        aria-labelledby="titulo-secciones"
        class="mt-4"
      >
        <h2 id="titulo-secciones" class="h4">Secciones de la sede</h2>

        <ul class="lista-resultados">
          <li v-for="seccion in seccionesEncontradas" :key="seccion.ruta">
            <NuxtLink :to="seccion.ruta" class="resultado-enlace">
              {{ seccion.etiqueta }}
            </NuxtLink>
          </li>
        </ul>
      </section>

      <!--
        El vacío se explica: distinguir «no hay resultados» de «el servicio no
        responde» y decir qué hacer en cada caso es lo que evita que el ciudadano
        concluya que su trámite no existe.
      -->
      <div v-if="!buscando && totalResultados === 0" class="row">
        <div class="col-lg-8">
          <p v-if="servicioNoDisponible" class="mb-2">
            El catálogo de trámites no respondió, así que la búsqueda sólo pudo
            cubrir las secciones de la sede. Vuelva a intentarlo en unos minutos.
          </p>
          <p v-else class="mb-2">
            Compruebe cómo está escrito el término, pruebe con una palabra más
            general o consulte el mapa del sitio, que lista todas las secciones.
          </p>
          <ul class="mb-0">
            <li><NuxtLink to="/mapa-del-sitio">Mapa del sitio</NuxtLink></li>
            <li><NuxtLink to="/tramites">Catálogo de trámites y servicios</NuxtLink></li>
            <li><NuxtLink to="/atencion">Canales de atención</NuxtLink></li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
/*
 * Recuento de resultados. Es texto informativo: se publica con el Matterhorn del
 * Kit (8,59:1 sobre blanco) y no con un gris claro, que no llegaría al 4,5:1 que
 * exige RNF-B1-017.
 */
.resultado-resumen {
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.lista-resultados {
  list-style: none;
  padding: 0;
  margin: 0;
}

.lista-resultados li {
  padding: 0.875rem 0;
  border-bottom: 0.0625rem solid #e5e7eb;
}

.lista-resultados li:last-child {
  border-bottom: 0;
}

/*
 * El título del resultado es el enlace, y va subrayado siempre y no sólo al pasar
 * el ratón: en una lista de resultados, saber qué es pulsable sin recorrer cada
 * línea con el puntero es parte de la usabilidad del buscador.
 */
.resultado-enlace {
  font-size: 1.0625rem;
  font-weight: 600;
  text-decoration: underline;
}

.resultado-meta {
  margin: 0.125rem 0 0;
  font-size: 0.8125rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.resultado-extracto {
  margin: 0.25rem 0 0;
  font-size: 0.9375rem;
  color: #333;
  /* Entre 60 y 80 caracteres por línea (RNF-B1-031). */
  max-width: 68ch;
}
</style>
